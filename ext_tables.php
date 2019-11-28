<?php
defined('TYPO3_MODE') || die();

if (TYPO3_MODE == 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_func',
        \JambageCom\Import\Controller\ImportTablesWizardModuleFunctionController::class,
        null,
        'LLL:EXT:' . IMPORT_EXT . '/Resources/Private/Language/locallang.xlf:moduleFunction.import'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        '_MOD_web_func',
        'EXT:' . IMPORT_EXT . '/Resources/Private/Language/locallang_csh.xlf'
    );

    // Add context sensitive help (csh) to the backend module
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        IMPORT_CSHKEY,
        'EXT:' . IMPORT_EXT . '/Resources/Private/Language/locallang_csh_import.xlf'
    );
}
