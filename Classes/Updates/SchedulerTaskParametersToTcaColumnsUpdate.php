<?php

declare(strict_types=1);

namespace Formatsoft\FormatT3tools\Updates;

/**
 * This file is part of the "format_t3tools" Extension for TYPO3 CMS.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Doctrine\DBAL\ArrayParameterType;
use Formatsoft\FormatT3tools\Task\DbcheckTask;
use Formatsoft\FormatT3tools\Task\LogsizecheckTask;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Moves the configuration of both scheduler tasks into the prefixed TCA columns
 * of tx_scheduler_task. Three legacy storages are taken into account:
 *
 * 1. "parameters" (JSON) with the unprefixed keys - that is where TYPO3's own
 *    "schedulerDatabaseStorageMigration" wizard put the configuration of the tasks
 *    of format_t3tools <= 14.0, which were registered via SC_OPTIONS back then.
 * 2. The unprefixed columns "notificationEmail", "maxDbSize" and "maxLogSize" - used
 *    by the first TCA based version of the tasks. They are dropped from the TCA now,
 *    so the database analyzer offers to remove them once this wizard has been run.
 * 3. "serialized_task_object" - only relevant for installations on which TYPO3's own
 *    wizard has never been run.
 *
 * TYPO3's own wizard would be able to do the mapping of (1), but it is already marked
 * as done on every installation that has been upgraded to TYPO3 14 before, so it never
 * runs again. Hence this wizard.
 */
#[UpgradeWizard('formatT3toolsSchedulerTaskParametersToTcaColumns')]
class SchedulerTaskParametersToTcaColumnsUpdate implements UpgradeWizardInterface
{
    protected const TABLE_NAME = 'tx_scheduler_task';

    /**
     * Task type => [TCA column => legacy parameter/column name]
     *
     * @var array<class-string, array<string, string>>
     */
    protected const COLUMNS_BY_TASK_TYPE = [
        DbcheckTask::class => [
            'tx_formatt3tools_notification_email' => 'notificationEmail',
            'tx_formatt3tools_max_db_size' => 'maxDbSize',
        ],
        LogsizecheckTask::class => [
            'tx_formatt3tools_notification_email' => 'notificationEmail',
            'tx_formatt3tools_max_log_size' => 'maxLogSize',
        ],
    ];

    /**
     * Columns holding an email address; everything else is treated as an integer.
     *
     * @var list<string>
     */
    protected const STRING_COLUMNS = ['tx_formatt3tools_notification_email'];

    public function getTitle(): string
    {
        return 'format_t3tools: Migrate scheduler task parameters to TCA columns';
    }

    public function getDescription(): string
    {
        return 'Transfers the notification email address and the size limit of the tasks '
            . '"Check database size" and "Check size of the log files" from the legacy task '
            . 'parameters into the dedicated, prefixed database columns.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return (bool)$this->getPreparedQueryBuilder()->count('uid')->executeQuery()->fetchOne();
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $rows = $this->getPreparedQueryBuilder()->select('*')->executeQuery()->fetchAllAssociative();

        foreach ($rows as $row) {
            $parameters = $this->extractTaskParameters($row);

            // Both legacy storages are always reset: for a native task type they are not
            // evaluated by the backend form anymore, but TaskSerializer would still let
            // "parameters" win over the columns - and a leftover would keep this wizard
            // from ever being finished. The unprefixed columns are not touched, they are
            // gone from the TCA and the database analyzer offers to drop them.
            $fieldsToUpdate = [
                'parameters' => null,
                'serialized_task_object' => null,
            ];

            foreach (self::COLUMNS_BY_TASK_TYPE[$row['tasktype']] ?? [] as $column => $legacyName) {
                // Do not overwrite a value that has already been entered manually in the backend.
                if (!$this->isEmptyValue($row[$column] ?? null)) {
                    continue;
                }
                $value = $parameters[$legacyName] ?? $parameters[$column] ?? null;
                if ($value === null) {
                    continue;
                }
                $fieldsToUpdate[$column] = in_array($column, self::STRING_COLUMNS, true)
                    ? (string)$value
                    : max(0, (int)$value);
            }

            $connection->update(self::TABLE_NAME, $fieldsToUpdate, ['uid' => (int)$row['uid']]);
        }

        return true;
    }

    /**
     * Collects the legacy configuration of a task, in ascending order of precedence:
     * serialized task object, "parameters" JSON, unprefixed columns.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function extractTaskParameters(array $row): array
    {
        $parameters = $this->extractFromSerializedTaskObject((string)($row['serialized_task_object'] ?? ''));

        $decodedParameters = json_decode((string)($row['parameters'] ?? ''), true);
        if (is_array($decodedParameters)) {
            $parameters = array_replace($parameters, $decodedParameters);
        }

        foreach (self::COLUMNS_BY_TASK_TYPE[$row['tasktype']] ?? [] as $legacyName) {
            // The unprefixed columns only exist as long as the database analyzer has not
            // removed them, so they are read from the row instead of being selected explicitly.
            if (array_key_exists($legacyName, $row) && !$this->isEmptyValue($row[$legacyName])) {
                $parameters[$legacyName] = $row[$legacyName];
            }
        }

        return $parameters;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractFromSerializedTaskObject(string $serializedTaskObject): array
    {
        if ($serializedTaskObject === '') {
            return [];
        }
        // unserialize() only warns and returns false on broken input, so silence that and
        // operate on the "false" result below.
        set_error_handler(static fn(): bool => true);
        try {
            $taskObject = unserialize($serializedTaskObject);
        } finally {
            restore_error_handler();
        }
        if ($taskObject instanceof AbstractTask) {
            return $taskObject->getTaskParameters();
        }
        if ($taskObject instanceof \__PHP_Incomplete_Class) {
            $parameters = [];
            foreach (get_mangled_object_vars($taskObject) as $key => $value) {
                $key = trim(trim(trim($key), "*\0"));
                if ($key !== '__PHP_Incomplete_Class_Name' && (is_scalar($value) || $value === null)) {
                    $parameters[$key] = $value;
                }
            }
            return $parameters;
        }

        return [];
    }

    protected function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === 0 || $value === '0';
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        // Deleted tasks are migrated as well, so that no leftover keeps the wizard from finishing.
        $queryBuilder->getRestrictions()->removeAll();

        $constraints = [
            $queryBuilder->expr()->and(
                $queryBuilder->expr()->isNotNull('parameters'),
                $queryBuilder->expr()->neq('parameters', $queryBuilder->createNamedParameter(''))
            ),
            $queryBuilder->expr()->and(
                $queryBuilder->expr()->isNotNull('serialized_task_object'),
                $queryBuilder->expr()->neq('serialized_task_object', $queryBuilder->createNamedParameter(''))
            ),
        ];
        // A task of the first TCA based version has its values in the unprefixed columns only.
        foreach ($this->getExistingLegacyColumns() as $legacyColumn) {
            $constraints[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->isNotNull($legacyColumn),
                $queryBuilder->expr()->neq($legacyColumn, $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->neq($legacyColumn, $queryBuilder->createNamedParameter('0'))
            );
        }

        $queryBuilder
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'tasktype',
                    $queryBuilder->createNamedParameter(array_keys(self::COLUMNS_BY_TASK_TYPE), ArrayParameterType::STRING)
                ),
                $queryBuilder->expr()->or(...$constraints)
            );

        return $queryBuilder;
    }

    /**
     * The unprefixed columns are no longer part of the TCA, so they may already have been
     * dropped by the database analyzer.
     *
     * @return list<string>
     */
    protected function getExistingLegacyColumns(): array
    {
        $schemaManager = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME)
            ->createSchemaManager();
        $table = $schemaManager->introspectSchema()->getTable(self::TABLE_NAME);

        $legacyColumns = [];
        foreach (self::COLUMNS_BY_TASK_TYPE as $columnMap) {
            foreach ($columnMap as $legacyName) {
                if (!in_array($legacyName, $legacyColumns, true) && $table->hasColumn($legacyName)) {
                    $legacyColumns[] = $legacyName;
                }
            }
        }

        return $legacyColumns;
    }
}
