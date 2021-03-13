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
 * FAL functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage import
 *
 */

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;



class ImportFal {
    const TARGET_DIRECTORY   = 'user_upload/';

    /**
    * Returns the "AND NOT deleted" clause for the tablename given IF $GLOBALS['TCA'] configuration points to such a field.
    *
    * @param	string		Tablename
    * @return	string
    * @see enableFields()
    */
    static public function deleteClause ($table) {
        if (!strcmp($table, 'pages')) { // Hardcode for pages because TCA might not be loaded yet (early frontend initialization)
            $result = ' AND deleted=0';
        } else {
            $result = $GLOBALS['TCA'][$table]['ctrl']['delete'] ? ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['delete'] . '=0' : '';
        }
        return $result;
    }

    public function add (
        $tablename,
        array $row,
        $falFieldname,
        array $files,
        array $config,
        $falFolder
    ) {
        if (!empty($files) && $falFolder != '') {
        
            /** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
            $storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
            $storage = $storageRepository->findByUid(1);
            $targetDirectory = self::TARGET_DIRECTORY;
            $targetFolder = null;
            if (!$storage->hasFolder('/' . $targetDirectory)) {
                $targetFolder = $storage->createFolder('/' . $targetDirectory);
            } else {
                $targetFolder = $storage->getFolder('/' . $targetDirectory);
            }

            $sysfileRowArray = array();
            $falTable = 'sys_file_reference';
            $where_clause = 'uid_foreign=' . intval($row['uid']) . ' AND tablenames=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tablename, $falTable) . ' AND fieldname=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($falFieldname, $falTable);
            $where_clause .= $this->deleteClause('sys_file_reference');

            $sysfileRowArray =
                $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                    '*',
                    $falTable,
                    $where_clause,
                    '',
                    'sorting',
                    '',
                    'uid_local'
                );

            $imageCount = count($files);
            $needsCountUpdate = true;
            foreach ($files as $imageKey => $image) {
                $imageFile = $targetDirectory . $image;
                $fileIdentifier = '1:' . $imageFile;

                // Check if the file is already known by FAL, if not add it
                $targetFileName = 'fileadmin/' . $imageFile;

                if (!file_exists(PATH_site . $targetFileName)) {
                    $fullSourceFileName = PATH_site . $falFolder . '/' . $image;
                    // Move the file to the storage and index it (indexing creates the sys_file entry)
                    $file = $storage->addFile($fullSourceFileName, $targetFolder, '', 'cancel');
                }

                $fac = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory'); // create instance to storage repository

                $file = $fac->getFileObjectFromCombinedIdentifier($fileIdentifier);
                if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
                    $fileUid = $file->getUid();
                    if (
                        empty($sysfileRowArray) ||
                        !isset($sysfileRowArray[$fileUid])
                    ) {
                        $data = array();
                        $data['sys_file_reference']['NEW1234'] = array(
                            'uid_local' => $fileUid,
                            'uid_foreign' => $row['uid'], // uid of your table record
                            'tablenames' => $tablename,
                            'fieldname' => $falFieldname,
                            'pid' => $row['pid'], // parent id of the parent page
                            'table_local' => 'sys_file',
                        );

                        if (!isset($sysfileRowArray[$fileUid])) {
                            $data[$tablename][$row['uid']] = array($falFieldname => 'NEW1234');
                        }

                        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
                        $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler'); // create TCE instance
                        $tce->start($data, array());
                        $tce->process_datamap();

                        if ($tce->errorLog) {
                            $content .= 'TCE->errorLog:' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($tce->errorLog);
                        } else {
                            // nothing
                        }
                    }
                }
                $imageCount++;
            } // foreach ($files)

            if ($needsCountUpdate) {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                    $tablename,
                    'uid=' . intval($row['uid']),
                    array($falFieldname => $imageCount)
                );
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

