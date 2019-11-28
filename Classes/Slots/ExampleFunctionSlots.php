<?php
namespace JambageCom\Import\Slots;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Import\Api\Api;

/**
 * Class for example slots to import files into TYPO3 tables based on the Function Module
 */
class ExampleFunctionSlots implements \TYPO3\CMS\Core\SingletonInterface
{
    protected $tables = array('pages', 'tt_content');

    public function getTables () {
        return $this->tables;
    }

    /**
     * Adds entries to the menu selector of the import extension
     *
     * @return mixed[] Array with entries for the import menu
     */
    public function getMenu (
        $pObj,
        array $menu
    )
    {
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $menuItem = $GLOBALS['LANG']->getLL('menu.' . $table);
            $menu[$table] = $menuItem;
        }
        $result = array($pObj, $menu);
        return $result;
    }

    /**
     * imports into the tables pages or tt_content if they are part of the given tables
     *
     * @return mixed[] Array with entries for the import menu
     */
    public function importTables (
        $pObj,
        $pid,
        array $paramTables
    )
    {
            // Rendering of the output via fluid
        $api = GeneralUtility::makeInstance(Api::class);

        $tables = $this->getTables();
        foreach ($tables as $table) {
            if (in_array($table, $paramTables)) {
                $file =
                    GeneralUtility::getFileAbsFileName(
                        'EXT:' . IMPORT_EXT . '/Resources/Private/Files/' . $table . '.csv'
                    );
                $api->importTableFile($table, $file, $pid, ',', '"', 0, true);
            }
        }
    }

    /**
     * modifies the given row of a table during the import
     *
     * @return mixed[] Array with the parameters of the function call
     */
    public function processImport (
        $tableName,
        $row,
        $pid,
        $count,
        $mode
    )
    {
        $result = array(
            $tableName,
            $row,
            $pid,
            $count,
            $mode
        );
        return $result;
    }
}

