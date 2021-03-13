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



/**
 * Class for example slots to check and modify records before they get stored
 */
class ExampleRecordSlots implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Checks the given row of a table during the import if it shall be stored.
     *
     * @return mixed[] Array with the parameters of the function call
     */
    public function checkConvertedRecord (
        $tableName,
        $row,
        $check,
        $duplicateCheckNeeded
    )
    {
    // implement the check here
        if (
            $tableName == 'mytablename' &&
            isset($row['name'])
        ) {
            if ($row['name'] == 'unwanted person') {
                $check = false;
                $duplicateCheckNeeded = false;
            } else {
                $duplicateCheckNeeded = true;
            }
        }

        $result = [
            $tableName,
            $row,
            $check,
            $duplicateCheckNeeded
        ];
        return $result;
    }
    
    /**
     * modifies the given row of a table during the import
     *
     * @return mixed[] Array with the parameters of the function call
     */
    public function converteRecord (
        $tableName,
        $row,
        $convertedRow
    )
    {
        if (
            $tableName == 'mytablename'
        ) {
            $convertedRow = $row;
            $convertedRow['imported'] = 1;
        }

        $result = [
            $tableName,
            $row,
            $convertedRow
        ];
        return $result;
    }

}

