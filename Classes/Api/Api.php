<?php
namespace JambageCom\Import\Api;

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

/**
 * Part of the import extension.
 *
 * API functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage import
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Import\Api\ImportFal;


class Api {
    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;
    protected $time;

    /**
     * Constructor
     */
    public function __construct ()
    {
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $time = time();
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
            $time += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
        }
        $this->setTime($time);
        $standardFields = [
            'pid', 'tstamp', 'crdate', 'cruser_id', 'hidden', 'starttime', 'endtime', 'sorting'
        ];
        $this->setStandardFields($standardFields);
    }

    protected function setTime($time)
    {
        $this->time = $time;
    }

    protected function getTime()
    {
        return $this->time;
    }

    protected function setStandardFields($standardFields)
    {
        $this->standardFields = $standardFields;
    }

    protected function getStandardFields()
    {
        return $this->standardFields;
    }

    public function importTableFile (
        $tableName,
        $fileName,
        $pid,
        $separator = ',',
        $enclosure = '"',
        $mode = 0,
        $firstLineFieldnames = false
    )
    {
        $result = false;

        if (
            $tableName != '' &&
            $fileName  != ''
        ) {
            $file = fopen($fileName, 'r');
            $header = true;
            $headerRow = array();
            $keysRow = array();
            $count = 0;

            while(
                !feof($file) &&
                ($row = fgetcsv($file, 0, $separator, $enclosure))
            ) {
                $count++;
                if (
                    $firstLineFieldnames &&
                    $header
                ) {
                    $keysRow = $row;
                    $headerRow = array_flip($keysRow);
                    $header = false;
                    continue;
                }
                if ($keysRow) {
                    $row = array_combine($keysRow, $row);
                }

                $slotResult =
                    $this->signalSlotDispatcher->dispatch(
                        __CLASS__,
                        'importTableFile',
                        array(
                            $tableName,
                            $row,
                            $pid,
                            $count,
                            $mode
                        )
                    );

                if (isset($headerRow['uid'])) {
                    $where_clause = 'uid=' . intval($row['uid']);
                    $currentRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                        'uid',
                        $tableName,
                        $where_clause
                    );

                    if ($currentRow) {
                        continue;
                    }
                }
                // TODO: insert the row into the table
            }

            fclose($file);
            $result = true;
        }

        return $result;
    }


    protected function convertToDestination(
        array &$falRow,
        array $sourceRow,
        array $recordRelations,
        $destinationTableName
    ) {
        $result = array();
        foreach ($recordRelations as $relationKey => $relationValue) {
            if (strpos($relationKey, 'import-') === 0) { // do not use the configuration part
                continue;
            }
            $result[$relationKey] = $sourceRow[$relationValue];
        }
    
        $standardFields = $this->getStandardFields();
        foreach ($sourceRow as $field => $value) {
            if (
                !isset($result[$field]) &&
                in_array($field, $standardFields)
            ) {
                $result[$field] = $value;
            }
        }
        $result['tstamp'] = $this->getTime();
        foreach ($result as $field => $value) {
            if (
                $GLOBALS['TCA'][$destinationTableName]['columns'][$field]['config']['foreign_table'] == 'sys_file_reference' ||
                in_array($field, array('pid', 'tstamp', 'crdate'))
            ) {
                // FAL records will be added after the insertion of the record
                $falRow[$field] = $value;
            }
        
            if (
                $field != 'pid' &&
                (
                    !isset($GLOBALS['TCA'][$destinationTableName]['columns'][$field]) ||
                    isset($falRow[$field])
                )
            ) {
                unset($result[$field]);
            }
        }
        
        $falRow['crdate'] = $falRow['tstamp'];

        return $result;
    }
    
    public function addFal (
        $destinationTableName,
        $destinationFalRow,
        $sourceRow,
        $imageFolder
    ) {
        $falApi = GeneralUtility::makeInstance(ImportFal::class);

        foreach ($destinationFalRow as $field => $value) {
            if (
                $imageFolder != '' &&
                strlen($value) &&
                $GLOBALS['TCA'][$destinationTableName]['columns'][$field]['config']['foreign_table'] == 'sys_file_reference'
            ) {
                $falRow = array();
                $falRow['uid'] = $destinationFalRow['uid'];
                $falRow['pid'] = $destinationFalRow['pid'];
                $falRow[$field] = $sourceRow[$field];
                $files = GeneralUtility::trimExplode(',', $falRow[$field]);

                $falApi->add(
                    $destinationTableName,
                    $falRow,
                    $field,
                    $files,
                    $GLOBALS['TCA']['tt_address']['columns'][$field]['config'],
                    $imageFolder
                );
            }
        }
    }

    public function importTableFromTable (
        $recordRelationFile,
        $categoryRelationFile,
        $mode = 0
    )
    {
        if (
            $recordRelationFile  != ''
        ) {
            $xml = file_get_contents($recordRelationFile);
            $recordRelations = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xml);
            if (!is_array($recordRelations)) {
                throw new \RuntimeException($recordRelationFile . ': ' . $recordRelations);
            }
            
            $sourceTableName = $recordRelations['import-source'];
            $sourceMMTableName = $recordRelations['import-source-mm-category'];
            $destinationMMTableName = $recordRelations['import-destination-mm-category'];
            $destinationMMTableField = $recordRelations['import-destination-mm-category-field'];
            $destinationTableName = $recordRelations['import-destination'];
            $destinationExtension = $recordRelations['import-destination-extension'];

            $categoryRelations = null;
            $sourceCategoryTable = null;
            $destinationCategoryTable = null;

            if ($categoryRelationFile != '') {
                $xml = file_get_contents($categoryRelationFile);
                $categoryRelations = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xml);
                if (!is_array($categoryRelations)) {
                    throw new \RuntimeException($categoryRelationFile . ': ' . $categoryRelations);
                }

                $sourceCategoryTable = $categoryRelations['import-source'];
                $destinationCategoryTable = $categoryRelations['import-destination'];
            }

            if (
                $sourceTableName != '' &&
                $destinationTableName != ''
            ) {
                $where_clause = 'deleted=0'; 
                $allDestinationCategoryRows = [];

                if (
                    $sourceMMTableName != '' &&
                    !empty($categoryRelations)
                ) { // process the categories
                    $sourceCategoryRows =
                        $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                            '*',
                            $sourceCategoryTable,
                            $where_clause
                        );
                    $count = 0;
                    $imageFolder = '';
                    foreach ($sourceCategoryRows as $sourceCategoryRow) {
                        $count++;
                        if ($count < 0) // changed during test development
                            break;
                        $falRow = array(); // TODO: FAL for categories
                        $destinationCategoryRow =
                            $this->convertToDestination(
                                $falRow,
                                $sourceCategoryRow,
                                $categoryRelations,
                                $destinationCategoryTable,
                                $imageFolder
                            );

                        $where = $where_clause;
                        $standardFields = $this->getStandardFields();
                        foreach ($destinationCategoryRow as $field => $value) {
                            if (in_array($field, $standardFields)) {
                                continue;
                            }
                            $where .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $destinationCategoryTable);
                        }

                        $checkDestinationCategoryRow = // verify that this category has not yet been imported
                            $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                                '*',
                                $destinationCategoryTable,
                                $where
                            );
                        
                        if ($checkDestinationCategoryRow) {
                            $allDestinationCategoryRows[$sourceCategoryRow['uid']] = $checkDestinationCategoryRow;
                        } else {
                            $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                                $destinationCategoryTable,
                                $destinationCategoryRow
                            );
                            $insertUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
                            $destinationCategoryRow['uid'] = $insertUid;
                            $allDestinationCategoryRows[$sourceCategoryRow['uid']] = $destinationCategoryRow;

                        }
                    }
                }

                $res =
                    $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        '*',
                        $sourceTableName,
                        $where_clause
                    );
                $count = 0;
                while(
                    ($count > -1) &&
                    ($sourceRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
                ) { // the $count comparison is changed during testing and development
                    $count++;
                    $falRow = array();
                    $destinationRow =
                        $this->convertToDestination(
                            $falRow,
                            $sourceRow,
                            $recordRelations,
                            $destinationTableName
                        );

                    $where = $where_clause;
                    $standardFields = $this->getStandardFields();
                    foreach ($destinationRow as $field => $value) {
                        if (in_array($field, $standardFields)) {
                            continue;
                        }
                        $where .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $destinationTableName);
                    }

                    $checkDestinationRow = // verify that this record has not yet been imported
                        $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                            '*',
                            $destinationTableName,
                            $where
                        );

                    if (!$checkDestinationRow) {

                        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                            $destinationTableName,
                            $destinationRow
                        );
                        $destinationUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
                        
                        if ($destinationUid) {
                            $imageFolder = $recordRelations['import-source-image-folder'];

                            // add FAL records
                            $falRow['uid'] = $destinationUid;
                            $this->addFal(
                                $destinationTableName,
                                $falRow,
                                $sourceRow,
                                $imageFolder
                            );
                        }

                        if (
                            $destinationUid &&
                            $sourceMMTableName != '' &&
                            $destinationMMTableName != ''
                        ) {
                            $sourceUid = $sourceRow['uid'];
                            $where = 'uid_local=' . intval($sourceUid);
                            $sourceCategoryRows =
                                $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                    'uid_foreign,tablenames',
                                    $sourceMMTableName,
                                    $where
                                );
                            $categoryArray = [];
                            if ($sourceCategoryRows) {
                                foreach($sourceCategoryRows as $sourceCategoryRow) {
                                    $categoryArray[] = $sourceCategoryRow['uid_foreign'];
                                }
                           }
                           $destinationCategoryArray = [];
                           if ($categoryArray) {
                                foreach ($categoryArray as $category) {
                                    $destinationCategoryArray[] = $allDestinationCategoryRows[$category]['uid'];
                                }
                           }
                           
                           if (!empty($destinationCategoryArray)) {
                                $destinationMMRow = [];
                                $destinationMMRow['uid_foreign'] = $destinationUid;
                                $destinationMMRow['tablenames'] = $destinationTableName;
                                $destinationMMRow['fieldname'] = $destinationMMTableField;

                                foreach ($destinationCategoryArray as $category) {
                                    $destinationMMRow['uid_local'] = $category;

                                    $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                                        $destinationMMTableName,
                                        $destinationMMRow
                                    );
                                }
                           }                            
                        }
                    }
                }
            }
        }
    }
}

