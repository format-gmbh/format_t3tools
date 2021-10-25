<?php
$EM_CONF[$_EXTKEY] = [
	'title' => 'Tools for your TYPO3 installation',
	'description' => 'This TYPO3 extension checks the size of all database tables and/or the size of all log files at regular intervals. If a certain size is exceeded, a mail can be sent. There is a separate scheduler task for each check.',
	'category' => 'be',
	'version' => '2.2.3',
	'author' => 'Andreas Kessel',
	'author_email' => 'typo3-dev@formatsoft.de',
	'author_company' => 'format Software Gmbh (www.formatsoft.de)',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'constraints' => [
		'depends' => [
			'typo3' => '9.5.17-10.4.99',
            'scheduler' => ''
		],
        'conflicts' => [],
        'suggests' => [],
	],
];
