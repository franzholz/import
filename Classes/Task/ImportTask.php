<?php

namespace JambageCom\Import\Task;

/***************************************************************
*  Copyright notice
*
*  (c) 2019 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Import Task
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage import
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    /**
    * Array of tables to import
    *
    * @var array $tableArray
    */
    public $tableArray;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    }

    public function execute ()
    {
        $result = false;
        $slotDefinitionArray = array();
        $slotResult =
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'definition',
                array(
                    $this,
                    $slotDefinitionArray
                )
            );
       $slotDefinitionArray = $slotResult['1'];

        if (
            isset($this->tableArray) &&
            is_array($this->tableArray) &&
            !empty($this->tableArray)
        ) {
            $result = true;
            $localDefinitionArray = \JambageCom\Import\Api\ImportApi::getLocalDefinitionArray();
            $definitionArray = array_merge($localDefinitionArray, $slotDefinitionArray);
// 
            foreach ($definitionArray as $definition) {
                if (
                    !isset($definition['tables']) ||
                    !is_array($definition['tables'])
                ) {
                    continue;
                }

                if (isset($definition['ext'])) {

                    $foreignExtension = $definition['ext'];
                    if (
                        $foreignExtension != IMPORT_EXT &&
                        !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($foreignExtension)
                    ) {
                        return false;
                    }
                }

                $extensionTables = array();
                foreach ($definition['tables'] as $tableDefinition) {
                    $extensionTables[] = $tableDefinition['table'];
                }
                $theTableArray = array();

                foreach ($this->tableArray as $table) {
                    if (in_array($table, $extensionTables)) {
                        $theTableArray[] = $table;
                    }
                }

                if (!empty($theTableArray)) {
                    if (isset($definition['class'])) {

                        $foreignClass = $definition['class'];
                        if (
                            class_exists($foreignClass) &&
                            method_exists($foreignClass, 'execute')
                        ) {
                            $foreignObject = GeneralUtility::makeInstance($foreignClass);
                            $result = $foreignObject->execute($theTableArray);
                        } else {
                            $result = false;
                            break;
                        }
                    } else {
                        \JambageCom\Import\Api\ImportApi::execute($theTableArray);
                    }
                }
            }
        }

        return $result;
    }


    public function getAdditionalInformation ()
    {
    }
}
