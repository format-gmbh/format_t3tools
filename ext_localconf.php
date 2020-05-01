<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE=='BE')	{
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Formatsoft\FormatT3tools\Task\DbcheckTask::class] = [
		'extension' => 'format_t3tools',
		'title' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.dbcheck.name',
		'description' => 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf:tasks.dbcheck.description',
		'additionalFields' => \Formatsoft\FormatT3tools\Task\DbcheckTaskAdditionalFieldProvider::class
	];
}
