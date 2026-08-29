<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    ExtensionManagementUtility::addTCAcolumns(
        'tx_scheduler_task',
        [
            // Shared with the dbcheck task; defined in both overrides so that the
            // registration does not depend on the loading order of the TCA files.
            'tx_formatt3tools_notification_email' => [
                'label' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.validate.notificationEmail',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'required' => true,
                    'eval' => 'trim',
                    'placeholder' => 'support@myDomain.com',
                ],
            ],
            'tx_formatt3tools_max_log_size' => [
                'label' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.validate.maxLogSize',
                'config' => [
                    'type' => 'number',
                    'required' => true,
                    'default' => 1,
                    'range' => [
                        'lower' => 1,
                    ],
                ],
            ],
        ]
    );

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.logsizecheck.name',
            'description' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.logsizecheck.description',
            'value' => \Formatsoft\FormatT3tools\Task\LogsizecheckTask::class,
            'icon' => 'mimetypes-x-tx_scheduler_task_group',
            'group' => 'format_t3tools',
        ],
        '
            --div--;core.form.tabs:general,
                tasktype,
                task_group,
                description,
                tx_formatt3tools_notification_email,
                tx_formatt3tools_max_log_size,
            --div--;scheduler.messages:scheduler.form.palettes.timing,
                execution_details,
                nextexecution,
                --palette--;;lastexecution,
            --div--;core.form.tabs:access,
                disable,
            --div--;core.form.tabs:extended,',
        [],
        '',
        'tx_scheduler_task'
    );
}
