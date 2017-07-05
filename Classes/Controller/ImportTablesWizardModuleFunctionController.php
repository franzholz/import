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
debug ($_REQUEST, '$_REQUEST +++');

        if (
            isset($slotResult) &&
            is_array($slotResult)
        ) {
        debug ($slotResult['1'], '$slotResult 1');
        debug ($slotResult['2'], '$slotResult 2');
            if (isset($slotResult['1'])) {
                $menu = $slotResult['1'];
            }
//             if (isset($slotResult['2'])) {
//                 $files = $slotResult['2'];
//             }
        }
        debug ($menu, '$menu');

        $execute = GeneralUtility::_GP('execute');
        debug ($execute, '$execute');
        debug ($this->pObj->id, 'pid +++');

        if ($execute) {
            $requiredTables = GeneralUtility::_GP('import-table');
            $importTables = array();
            // check for allowed tables
            foreach ($requiredTables as $table) {
                if (isset($menu[$table])) {
                    $importTables[] = $table;
                }
            }

        debug ($importTables, '$importTables +++');

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

            // CSH
            $assigns['cshItem'] = BackendUtility::cshItem('_MOD_web_func', 'tx_import');

            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:' . IMPORT_EXT . '/Resources/Private/Templates/ImportWizard.html'
            ));

            $assigns['menu'] = $menu;
            debug ($assigns, '$assigns +++');
        }

        $view->assignMultiple($assigns);
        $out = $view->render();

        debug ($out, '$out +++');

//         $out = '<p>Importiere in die Tabellen von TYPO3</p>';
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
