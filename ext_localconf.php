<?php
defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Formatsoft\FormatT3tools\Task\DbcheckTask::class] = [
    'extension' => 'format_t3tools',
    'title' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.dbcheck.name',
    'description' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.dbcheck.description',
    'additionalFields' => \Formatsoft\FormatT3tools\Task\DbcheckTaskAdditionalFieldProvider::class
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Formatsoft\FormatT3tools\Task\LogsizecheckTask::class] = [
    'extension' => 'format_t3tools',
    'title' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.logsizecheck.name',
    'description' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.logsizecheck.description',
    'additionalFields' => \Formatsoft\FormatT3tools\Task\LogsizecheckTaskAdditionalFieldProvider::class
];
