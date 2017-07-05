<?php
defined('TYPO3_MODE') or die();

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
}

