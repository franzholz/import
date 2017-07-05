<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

define('IMPORT_EXT', $_EXTKEY);

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \JambageCom\Import\Controller\ImportTablesWizardModuleFunctionController::class,
                                                    // Signal class name
    'menu',                                 // Signal name
    \JambageCom\Import\Slots\ExampleSlots::class,   // Slot class name
    'getMenu'                               // Slot name
);


$signalSlotDispatcher->connect(
    \JambageCom\Import\Controller\ImportTablesWizardModuleFunctionController::class,
                                                    // Signal class name
    'import',                                       // Signal name
    \JambageCom\Import\Slots\ExampleSlots::class,   // Slot class name
    'importTables'                               // Slot name
);


$signalSlotDispatcher->connect(
    \JambageCom\Import\Api\Api::class,
                                                    // Signal class name
    'importTableFile',                              // Signal name
    \JambageCom\Import\Slots\ExampleSlots::class,   // Slot class name
    'processImport'                                  // Slot name
);



// TODO: scheduler

// $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_import_Task'] = array(
// 	'extension'        => $_EXTKEY,
// 	'title'            => 'LLL:EXT:' . $_EXTKEY . '/tasks/locallang.xml:importTask.name',
// 	'description'      => 'LLL:EXT:' . $_EXTKEY . '/tasks/locallang.xml:importTask.description',
// 	'additionalFields' => 'tx_import_Task_AdditionalFieldProvider'
// );
//
