<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_import_Task'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/tasks/locallang.xml:importTask.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/tasks/locallang.xml:importTask.description',
	'additionalFields' => 'tx_import_Task_AdditionalFieldProvider'
);


?>