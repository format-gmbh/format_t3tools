<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "format_t3tools".
 *
 *
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'Tools for your TYPO3 installation',
	'description' => 'Currently only one tool: regularly checks the size of the TYPO3 database. On exceeding a certain size, a mail is sent. Please, ask for free support in TYPO3 mailing lists or contact the maintainer for paid support.',
	'category' => 'be',
	'version' => '1.4.2',
	'author' => 'Andreas Kessel',
	'author_email' => 'typo3-dev@formatsoft.de',
	'author_company' => 'format Software Gmbh (www.formatsoft.de)',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'constraints' => [
		'depends' => [
			'typo3' => '8.7.0-9.5.99',
		],
		'conflicts' => [],
		'suggests' => [],
	],
	'_md5_values_when_last_written' => '',
	'suggests' => [],
];
