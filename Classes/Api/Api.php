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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnec276tion;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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
                    $queryBuilder = $this->getQueryBuilder($tableName);
                    $queryBuilder->setRestrictions(GeneralUtility::makeInstance(
                        \TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer::class)
                    );
                        // get the single record
                    $statement = $queryBuilder
                        ->select('uid')
                        ->from($tableName)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter(
                                    intval($row['uid']),
                                    \PDO::PARAM_INT
                                )
                            )
                        )
                        ->execute();                    
                    $currentRow = $statement->fetch();

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

    private function compute ($input) {
        $phpcode = 'return (' . $input . ');';
        $result = eval($phpcode);
        return $result;
    }

    protected function convertToDestination(
        array &$falRow,
        array $sourceRow,
        array $recordRelations,
        $destinationTableName
    ) {
        $result = array();
        foreach ($recordRelations as $relationKey => $relationLine) {
            if (strpos($relationKey, 'import-') === 0) { // do not use the configuration part
                continue;
            }

            $relationExpressions = explode(' ', $relationLine);
            $importedValues = [];
            foreach ($relationExpressions as $relationExpression) {
                $value = '';
                $startPosition = null;
                $endPosition = null;
                $substringLength = 0;
                if ($relationExpression == '&&' || $relationExpression == '||') {
                    $importedValues[] = $relationExpression;
                    continue;
                }

                if (($position = strpos($relationExpression, ':')) > 0) {
                    $substringExpression = substr($relationExpression, $position + 1);
                    if ($substringExpression) {
                        $relationExpression = substr($relationExpression, 0, $position);
                        $substringParts = explode('-', $substringExpression);

                        if (count($substringParts) == 2) {
                            $startPosition = intval($substringParts['0']) - 1;
                            $endPosition = intval($substringParts['1']) - 1;
                            $substringLength = $endPosition - $startPosition + 1;
                        }
                    }
                }

                $negative = false;
                if (strpos($relationExpression, '!') === 0) {
                    $relationExpression = substr($relationExpression, 1);
                    $negative = true;
                }

                $value = $sourceRow[$relationExpression];

                if ($startPosition !== null && $substringLength > 0) {
                    $value = substr($value, $startPosition, $substringLength);
                }
                
                if ($negative) {
                    $value = intval(!$value);
                }

                $importedValues[] = $value;
            }
            $value = $importedValues['0'];
            if (count($importedValues) > 2) {
                $value = implode(' ', $importedValues);
                $value = $this->compute($value);
            }
            $result[$relationKey] .= $value;
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

        if (!isset($result['tstamp'])) {
            $result['tstamp'] = $this->getTime();
        }

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
                $field != 'crdate' &&
                $field != 'tstamp' &&
                (
                    !isset($GLOBALS['TCA'][$destinationTableName]['columns'][$field])
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
            $sourceMMTableName = '';
            if (isset($recordRelations['import-source-mm-category'])) {
                $sourceMMTableName = $recordRelations['import-source-mm-category'];
            }
            $destinationMMTableName = '';
            $destinationMMTableField = '';
            if (isset($recordRelations['import-destination-mm-category'])) {
                $destinationMMTableName = $recordRelations['import-destination-mm-category'];
                if (isset($recordRelations['import-destination-mm-category'])) {
                    $destinationMMTableField = $recordRelations['import-destination-mm-category-field'];
                }
            }
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
                $allDestinationCategoryRows = [];

                if (
                    $sourceMMTableName != '' &&
                    !empty($categoryRelations)
                ) { // process the categories
                    $tableName = $sourceCategoryTable;
                    $categoryQueryBuilder = $this->getQueryBuilder($tableName);
                    $categoryQueryBuilder->getRestrictions()->removeAll();
                        // get the single record
                    $sourceCategoryRows = $categoryQueryBuilder
                        ->select('*')
                        ->from($tableName)
                        ->where(
                            $categoryQueryBuilder->expr()->eq(
                                'deleted',
                                $categoryQueryBuilder->createNamedParameter(
                                    0,
                                    \PDO::PARAM_INT
                                )
                            )
                        )
                        ->execute()
                        ->fetchAll();

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

                        $tableName = $destinationCategoryTable;
                        $categoryQueryBuilder = $this->getQueryBuilder($tableName);
                        $categoryQueryBuilder->getRestrictions()->removeAll();
                            // get the single record
                        $categoryQueryBuilder
                            ->select('*')
                            ->from($tableName)
                            ->where(
                                $categoryQueryBuilder->expr()->eq(
                                    'deleted',
                                    $categoryQueryBuilder->createNamedParameter(
                                        0,
                                        \PDO::PARAM_INT
                                    )
                                )
                            ->execute()
                        );

                        $standardFields = $this->getStandardFields();
                        $expressionCount = 0;
                        $expressions = [];
                        foreach ($destinationCategoryRow as $field => $value) {
                            if (in_array($field, $standardFields)) {
                                continue;
                            }
                            $expressions[] = $categoryQueryBuilder->expr()->eq(
                                $field,
                                $categoryQueryBuilder->createNamedParameter($value, \PDO::PARAM_STR)
                            );
                            $expressionCount++;
                        }

                        if ($expressionCount > 1) {
                            $categoryQueryBuilder->andWhere(implode(',', $expressions));
                        }

                        $categoryStatement =
                            $categoryQueryBuilder
                                ->execute();
                        $checkDestinationCategoryRow = $categoryStatement->fetch();
                        
                        if ($checkDestinationCategoryRow) {
                            $allDestinationCategoryRows[$sourceCategoryRow['uid']] = $checkDestinationCategoryRow;
                        } else {
                            $insertQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
                            $affectedRows = $insertQueryBuilder
                                ->insert($destinationCategoryTable)
                                ->values(
                                    $destinationCategoryRow
                                )
                                ->execute();

                            $insertUid = (int) $insertQueryBuilder->getConnection()->lastInsertId($destinationCategoryTable);
                            $destinationCategoryRow['uid'] = $insertUid;
                            $allDestinationCategoryRows[$sourceCategoryRow['uid']] = $destinationCategoryRow;
                        }
                    }
                }

                $tableName = $sourceTableName;
                $queryBuilder = $this->getQueryBuilder($tableName);
                $queryBuilder->getRestrictions()->removeAll();
                    // get the single record
                $statement =
                    $queryBuilder
                    ->select('*')
                    ->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'deleted',
                            $queryBuilder->createNamedParameter(
                                0,
                                \PDO::PARAM_INT
                            )
                        )
                    )
                    ->execute();

                $count = 0;
                while(
                        // the $count comparison is changed during testing and development
                    ($count > -1) && // On a live system here must be  > -1 ! 
                    ($sourceRow = $statement->fetch())
                ) { 
                    $count++;
                    $falRow = array();
                    $destinationRow =
                        $this->convertToDestination(
                            $falRow,
                            $sourceRow,
                            $recordRelations,
                            $destinationTableName
                        );

                    $check = true;
                    $duplicateCheckNeeded = true;
                    $slotResult =
                        $this->signalSlotDispatcher->dispatch(
                            __CLASS__,
                            'check',
                            array(
                                $destinationTableName,
                                $destinationRow,
                                $check,
                                $duplicateCheckNeeded
                            )
                        );
                    $check = $slotResult['2'];
                    $duplicateCheckNeeded = $slotResult['3'];
                    if (!$check) {
                        continue;
                    }

                    $slotResult =
                        $this->signalSlotDispatcher->dispatch(
                            __CLASS__,
                            'convert',
                            array(
                                $destinationTableName,
                                $destinationRow,
                                $modifiedRow
                            )
                        );

                    if (
                        isset($slotResult['2']) &&
                        is_array($slotResult['2'])
                    ) {
                        $destinationRow = $slotResult['2'];
                    }
                    $checkDestinationRow = false;
                    
                    if ($duplicateCheckNeeded) {
                        $tableName = $destinationTableName;
                        $destinationQueryBuilder = $this->getQueryBuilder($tableName);
                        $destinationQueryBuilder->getRestrictions()->removeAll();
                            // get the single record
                        $destinationQueryBuilder
                            ->select('*')
                            ->from($tableName)
                            ->where(
                                $destinationQueryBuilder->expr()->eq(
                                    'deleted',
                                    $destinationQueryBuilder->createNamedParameter(
                                        0,
                                        \PDO::PARAM_INT
                                    )
                                )
                            );

                        $expressionCount = 0;
                        $standardFields = $this->getStandardFields();
                        $andX = $destinationQueryBuilder->expr()->andX();

                        foreach ($destinationRow as $field => $value) {
                            if (in_array($field, $standardFields)) {
                                continue;
                            }
                            $andX->add($destinationQueryBuilder->expr()->eq(
                                $field,
                                $destinationQueryBuilder->createNamedParameter($value, \PDO::PARAM_STR)
                            ));
                            $expressionCount++;
                        }

                        if ($expressionCount > 1) {
                            $destinationQueryBuilder->andWhere($andX);
                        }
                        $destinationStatement = $destinationQueryBuilder
                            ->execute();
                        $checkDestinationRow = $destinationStatement->fetch(); // verify that this record has not yet been imported
                    }

                    if (!$checkDestinationRow) {
                        foreach ($destinationRow as $field => $value) {
                            if (is_null($value)) {
                                $destinationRow[$field] = '';
                            }
                        }
                        $insertQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($destinationTableName);
                        $affectedRows = $insertQueryBuilder
                            ->insert($destinationTableName)
                            ->values(
                                $destinationRow
                            )
                            ->execute();

                        $destinationUid = (int) $insertQueryBuilder->getConnection()->lastInsertId($destinationTableName);
                        
                        if (
                            $destinationUid &&
                            isset($recordRelations['import-source-image-folder'])
                        ) {
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
                            $tableName = $sourceMMTableName;
                            $mmQueryBuilder = $this->getQueryBuilder($tableName);
                            $mmQueryBuilder->getRestrictions()->removeAll();
                                // get the single record
                            $mmStatement = $mmQueryBuilder
                                ->select('uid_foreign,tablenames')
                                ->from($tableName)
                                ->where(
                                    $mmQueryBuilder->expr()->eq(
                                        'deleted',
                                        $mmQueryBuilder->createNamedParameter(
                                            0,
                                            \PDO::PARAM_INT
                                        )
                                    )
                                )
                                ->andWhere(
                                    $mmQueryBuilder->expr()->eq(
                                        'uid_local',
                                        $mmQueryBuilder->createNamedParameter(
                                            intval($sourceUid),
                                            \PDO::PARAM_INT
                                        )
                                    )                                    
                                )
                                ->execute();

                            $sourceCategoryRows = $mmStatement->fetchAll();
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

                                    $insertQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($destinationMMTableName);
                                    $affectedRows = $insertQueryBuilder
                                        ->insert($destinationMMTableName)
                                        ->values(
                                            $destinationMMRow
                                        )
                                        ->execute();

                                    $destinationMMUid = (int) $insertQueryBuilder->getConnection()->lastInsertId($destinationMMTableName);
                                }
                           }                            
                        }
                    }
                }
            }
        }
    }

    /**
    * @param string $tableName
    * @return QueryBuilder
    */
    public function getQueryBuilder (string $tableName)
    {
        $result = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        return $result;
    }
}

