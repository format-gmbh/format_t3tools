<?php
$EM_CONF[$_EXTKEY] = [
	'title' => 'Tools for your TYPO3 installation',
	'description' => 'Currently only one tool: regularly checks the size of the TYPO3 database. On exceeding a certain size, a mail is sent. Please, ask for free support in TYPO3 mailing lists or contact the maintainer for paid support.',
	'category' => 'be',
	'version' => '2.1.0',
	'author' => 'Andreas Kessel',
	'author_email' => 'typo3-dev@formatsoft.de',
	'author_company' => 'format Software Gmbh (www.formatsoft.de)',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'constraints' => [
		'depends' => [
			'typo3' => '9.5.16-10.4.99',
            'scheduler' => ''
		],
        'conflicts' => [],
        'suggests' => [],
	],
];
