<?php
declare(strict_types=1);

namespace Formatsoft\FormatT3tools\Updates;

use Formatsoft\FormatT3tools\Task\DbcheckTask;
use Formatsoft\FormatT3tools\Task\LogsizecheckTask;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('formatT3tools_schedulerTaskParameterMigration')]
class SchedulerTaskParameterMigration implements UpgradeWizardInterface
{

    public function getTitle(): string
    {
        return 'format_t3tools: Migrate scheduler task parameters to TCA columns';
    }

    public function getDescription(): string
    {
        return 'Transfers notificationEmail and size limit from the serialized task object into the dedicated TCA columns for DbcheckTask and LogsizecheckTask.';
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_scheduler_task');

        $count = (int)$connection->executeQuery(
            "SELECT COUNT(*) FROM tx_scheduler_task
             WHERE tasktype IN (?, ?)
               AND (notificationEmail = '' OR notificationEmail IS NULL)
               AND serialized_task_object != ''",
            [DbcheckTask::class, LogsizecheckTask::class]
        )->fetchOne();

        return $count > 0;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_scheduler_task');

        $rows = $connection->executeQuery(
            "SELECT uid, tasktype, serialized_task_object FROM tx_scheduler_task
             WHERE tasktype IN (?, ?)
               AND (notificationEmail = '' OR notificationEmail IS NULL)
               AND serialized_task_object != ''",
            [DbcheckTask::class, LogsizecheckTask::class]
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $taskObject = unserialize((string)$row['serialized_task_object'], ['allowed_classes' => true]);

            if ($taskObject instanceof DbcheckTask) {
                $connection->update('tx_scheduler_task', [
                    'notificationEmail' => $taskObject->getNotificationEmail(),
                    'maxDbSize' => $taskObject->getMaxDbSize(),
                ], ['uid' => (int)$row['uid']]);
            } elseif ($taskObject instanceof LogsizecheckTask) {
                $connection->update('tx_scheduler_task', [
                    'notificationEmail' => $taskObject->getNotificationEmail() ?? '',
                    'maxLogSize' => $taskObject->getMaxLogSize(),
                ], ['uid' => (int)$row['uid']]);
            }
        }

        return true;
    }

    public function getPrerequisites(): array
    {
        return [];
    }
}
