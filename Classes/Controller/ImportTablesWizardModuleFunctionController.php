<?php
namespace JambageCom\Import\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Creates the "Import tables" wizard
 */
class ImportTablesWizardModuleFunctionController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Constructor
     */
    public function __construct ()
    {
        $this->signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    }


    /**
     * Main function creating the content for the module.
     *
     * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
     */
    public function main()
    {
        $assigns = [];
        $menu  = [];
            // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $languageFile = 'EXT:' . IMPORT_EXT . '/Resources/Private/Language/locallang.xlf';
        $this->getLanguageService()->includeLLFile($languageFile);
        $assigns['LLPrefix'] = 'LLL:' . $languageFile . ':';

        $slotResult =
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'menu',
                array(
                    $this,
                    $menu
                )
            );

        if (
            isset($slotResult) &&
            is_array($slotResult)
        ) {
            if (isset($slotResult['1'])) {
                $menu = $slotResult['1'];
            }
        }
        $execute = GeneralUtility::_GP('execute');

        if ($execute) {
            $requiredTables = GeneralUtility::_GP('import-table');
            $importTables = array();
            // check for allowed tables
            foreach ($requiredTables as $table) {
                if (isset($menu[$table])) {
                    $importTables[] = $table;
                }
            }

            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:' . IMPORT_EXT . '/Resources/Private/Templates/ImportFinished.html'
            ));

            $slotResult =
                $this->signalSlotDispatcher->dispatch(
                    __CLASS__,
                    'import',
                    array(
                        $this,
                        $this->pObj->id,
                        $importTables
                    )
                );

            $assigns['information'] =
                $GLOBALS['LANG']->getLL('wizard.importedTables');
        } else {
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:' . IMPORT_EXT . '/Resources/Private/Templates/ImportWizard.html'
            ));

            // CSH
            $assigns['cshItem'] = BackendUtility::cshItem('_MOD_web_func', 'tx_import');

            $assigns['menu'] = $menu;
        }

        $view->assignMultiple($assigns);
        $out = $view->render();
        return $out;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
