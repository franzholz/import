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
 * Additional Field Provider for the Import Task
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage import
 */


class ImportTaskAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {
    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    }

    public function getAdditionalFields (
        array &$taskInfo,
        $task,
        \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
    ) {
        $result = array();
        $cmd = $parentObject->CMD;

        if (empty($taskInfo['additionalscheduler_numberDays'])) {
            if ($cmd == 'edit') {
                $taskInfo['additionalscheduler_numberDays'] = $task->numberDays;
            } else {
                $taskInfo['additionalscheduler_numberDays'] = '';
            }
        }
        $additionalFields = array();
        $fieldID = 'task_numberDays';
        $fieldCode = '<input type="text" name="tx_scheduler[additionalscheduler_numberDays]" id="' . $fieldID . '" value="' . htmlspecialchars($taskInfo['additionalscheduler_numberDays']) . '" size="50" />';
        $additionalFields[$fieldID] = array(
            'code'     => $fieldCode,
            'label'    => 'LLL:EXT:additional_scheduler/Resources/Private/Language/locallang.xlf:numberDays',
            'cshKey'   => 'additional_scheduler',
            'cshLabel' => $fieldID
        );

        $field = 'tables';
        $fieldID = IMPORT_EXT . '_' . $field;
        $mainFieldDefaultName = 'tx_scheduler[' . IMPORT_EXT . ']';
        $tableFieldName = 'tx_scheduler[' . IMPORT_EXT . '][' . $field . ']';

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
        $localDefinitionArray = \JambageCom\Import\Api\ImportApi::getLocalDefinitionArray();

        if (
            isset($slotDefinitionArray) &&
            is_array($slotDefinitionArray)
        ) {
            $definitionArray = array_merge($localDefinitionArray, $slotDefinitionArray);
        } else {
            $definitionArray = $localDefinitionArray;
        }

        $label = '<label id="' . $fieldID . '" form="tx_scheduler_form">zu importierende Tabellen</label>';
        $fieldCode = '';
        $checkbox = array();
        $defaultTable = 'pages';
        $selectedTables = array();

        if (
            empty($taskInfo[IMPORT_EXT]) ||
            empty($taskInfo[IMPORT_EXT]) ||
            empty($taskInfo[IMPORT_EXT][$field])
        ) {
            if ($cmd == 'edit') {
                $taskInfo[IMPORT_EXT][$foreignExtension][$field] = $task->{$field};
            } else {
                $taskInfo[IMPORT_EXT][$foreignExtension][$field] = array($defaultTable);
            }
        } else {
            $selectedTables = $taskInfo[IMPORT_EXT][$field];
        }

        foreach ($definitionArray as $definition) {
            if (
                !isset($definition['ext']) ||
                !isset($definition['tables']) ||
                !is_array($definition['tables'])
            ) {
                continue;
            }
            $foreignExtension = $definition['ext'];
            if (
                $foreignExtension != IMPORT_EXT &&
                !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($foreignExtension)
            ) {
                continue;
            }

            foreach ($definition['tables'] as $definitionArray) {
                if (
                    !isset($definitionArray['table']) ||
                    !isset($definitionArray['title'])
                ) {
                    continue;
                }
                $theTable = $definitionArray['table'];
                $checked = '';
                if (in_array($theTable, $selectedTables)) {
                    $checked = ' checked ';
                }

                $checkbox[] = '<input type="checkbox" name="' . $tableFieldName . '[]" value="' . htmlspecialchars($theTable) . '"' . $checked . '> ' . htmlspecialchars($definitionArray['title']) . ' </label>';
            }
        }

        $fieldCode = $label;

        if (!empty($checkbox)) {
            foreach ($checkbox as $line) {
                $innerHtml .= '<li>' . $line . '</li>';
            }
        }

        $fieldCode .= '<fieldset><ul>' . $innerHtml . '</ul></fieldset>';

        $result[$fieldID] = array(
            'code'     => $fieldCode,
            'label'    => 'LLL:EXT:' . IMPORT_EXT . '/Resources/Private/Language/locallang.xlf:importTables', // Todo: use the $foreignExtension label here
            'cshKey'   => IMPORT_CSHKEY,
            'cshLabel' => $fieldID
        );

        return $result;
    }


    public function validateAdditionalFields (
        array &$submittedData,
        \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
    ) {
        $result = true;

        return $result;
    }

    public function saveAdditionalFields (
        array $submittedData,
        \TYPO3\CMS\Scheduler\Task\AbstractTask $task
    ) {
        $field = 'tables';

        if (
            empty($submittedData) ||
            empty($submittedData[IMPORT_EXT]) ||
            empty($submittedData[IMPORT_EXT][$field])
        ) {
            $task->tableArray = array();
        } else {
            $task->tableArray = $submittedData[IMPORT_EXT][$field];
        }
    }
}

