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


class Api {
    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Constructor
     */
    public function __construct ()
    {
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
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
}

