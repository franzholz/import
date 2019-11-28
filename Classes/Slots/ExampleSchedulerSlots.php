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
 * Class for example slots to import files into TYPO3 tables based on the Scheduler Module
 */
class ExampleSchedulerSlots implements \TYPO3\CMS\Core\SingletonInterface
{
    protected $tables = array('pages', 'tt_content');

    public function addDefinitionArray(
        $pObj,
        array $definitionArray
    )
    {
        $newDefinitionArray =
            array(
                'ext' => IMPORT_EXT,
                'class' => null,
                'tables' => array(
                    array(
                        'table' => 'pages',
                        'title' => 'Pages'
                    ),
                    array(
                        'table' => 'fe_users',
                        'title' => 'Front End Users'
                    ),
                )
            );

        $definitionArray[] = $newDefinitionArray;
        $result = array($pObj, $definitionArray);
        return $result;
    }
}


