<?php
if (TYPO3_MODE=='BE')	{
	$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_formatt3tools_dbcheck'] = array(
		'extension' => $_EXTKEY, // Selbsterklärend
		'title' => 'LLL:EXT:'.$_EXTKEY.'/tasks/locallang.xml:dbchecktask.meta.name',
		'description' => 'LLL:EXT:'.$_EXTKEY.'/tasks/locallang.xml:dbchecktask.meta.description',
		'additionalFields' => 'tx_formatt3tools_dbcheckadditionalfields'
	);
}
?>