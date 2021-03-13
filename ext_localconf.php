<?php
defined('TYPO3_MODE') || die('Access denied.');

define('IMPORT_EXT', 'import');

if (TYPO3_MODE == 'BE') {
    call_user_func(function () {
        $extensionConfiguration = array();

        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
            )->get(IMPORT_EXT);
        } else { // before TYPO3 9
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][IMPORT_EXT]);
        }

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][IMPORT_EXT]) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][IMPORT_EXT])
        ) {
            $tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][IMPORT_EXT];
        }

        if (isset($extensionConfiguration) && is_array($extensionConfiguration)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][IMPORT_EXT] = $extensionConfiguration;
            if (isset($tmpArray) && is_array($tmpArray)) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][IMPORT_EXT] =
                    array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][IMPORT_EXT], $tmpArray);
            }
        } else if (!isset($tmpArray)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][IMPORT_EXT] = array();
        }

        define('IMPORT_CSHKEY', '_MOD_system_txschedulerM1_' . IMPORT_EXT); // key for the Context Sensitive Help

        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            \JambageCom\Import\Controller\ImportTablesWizardModuleFunctionController::class,
                                                            // Signal class name
            'menu',                                         // Signal name
            \JambageCom\Import\Slots\ExampleFunctionSlots::class,   // Slot class name
            'getMenu'                                       // Slot name
        );

        $signalSlotDispatcher->connect(
            \JambageCom\Import\Controller\ImportTablesWizardModuleFunctionController::class,
                                                            // Signal class name
            'import',                                       // Signal name
            \JambageCom\Import\Slots\ExampleFunctionSlots::class,   // Slot class name
            'importTables'                                  // Slot name
        );

        $signalSlotDispatcher->connect(
            \JambageCom\Import\Api\Api::class,
                                                            // Signal class name
            'importTableFile',                              // Signal name
            \JambageCom\Import\Slots\ExampleFunctionSlots::class,   // Slot class name
            'processImport'                                 // Slot name
        );

        $signalSlotDispatcher->connect(
            \JambageCom\Import\Task\ImportTask::class,
                                                    // Signal class name
            'definition',                           // Signal name
            \JambageCom\Import\Slots\ExampleSchedulerSlots::class,   // Slot class name
            'addDefinitionArray'                    // Slot name
        );

        $signalSlotDispatcher->connect(
            \JambageCom\Import\Task\ImportTaskAdditionalFieldProvider::class,
                                                    // Signal class name
            'definition',                           // Signal name
            \JambageCom\Import\Slots\ExampleSchedulerSlots::class,   // Slot class name
            'addDefinitionArray'                    // Slot name
        );

        $signalSlotDispatcher->connect(
            \JambageCom\Import\Api\Api::class,
                                                    // Signal class name
            'check',                                // Signal name
            \JambageCom\Import\Slots\ExampleRecordSlots::class,   // Slot class name
            'checkConvertedRecord'                    // Slot name
        );

        $signalSlotDispatcher->connect(
            \JambageCom\Import\Api\Api::class,
                                                    // Signal class name
            'convert',                              // Signal name
            \JambageCom\Import\Slots\ExampleRecordSlots::class,   // Slot class name
            'converteRecord'                        // Slot name
        );

        // Add the import task to the scheduler
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\JambageCom\Import\Task\ImportTask::class] = array(
            'extension' => IMPORT_EXT,
            'title' => 'LLL:EXT:' . IMPORT_EXT . '/Resources/Private/Language/locallang.xlf:importTask.name',
            'description' => 'LLL:EXT:' . IMPORT_EXT . '/Resources/Private/Language/locallang.xlf:importTask.description',
            'additionalFields' => \JambageCom\Import\Task\ImportTaskAdditionalFieldProvider::class
        );

    });
}

