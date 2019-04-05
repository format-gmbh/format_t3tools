<?php 
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('format_t3tools'); 
return array(
	'tx_formatt3tools_dbcheck'                 => $extensionPath . 'tasks/class.tx_formatt3tools_dbcheck.php',
	'tx_formatt3tools_dbcheckadditionalfields' => $extensionPath . 'tasks/class.tx_formatt3tools_dbcheckadditionalfields.php',
);
