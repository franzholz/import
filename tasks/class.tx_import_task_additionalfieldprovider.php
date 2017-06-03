<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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
 * Aditional fields provider class for usage with the import task
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package		TYPO3
 * @subpackage	tx_import
 */
class tx_import_Task_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * This method is used to define new fields for adding or editing a task
	 * In this case, it adds the import files
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param tx_importttproducts_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array	Array containing all the information pertaining to the additional fields
	 *					The array is multidimensional, keyed to the task class name and each field's id
	 *					For each field it provides an associative sub-array with the following:
	 *						['code']		=> The HTML code for the field
	 *						['label']		=> The label of the field (possibly localized)
	 *						['cshKey']		=> The CSH key for the field
	 *						['cshLabel']	=> The code of the CSH label
	 */
	public function getAdditionalFields (
		array &$taskInfo,
		$task,
		tx_scheduler_Module $parentObject
	) {
debugBegin();
debug ($taskInfo, 'getAdditionalFields Start $taskInfo');
debug ($task, 'getAdditionalFields Start $task ');
debug ($task->filenameArray, '$task->filenameArray');
		$hookObjectsArray = tx_import_api::getHookArray();

			// Write the code for the field
		$mainFieldID = 'tx_scheduler_import';
		$mainFieldName = 'tx_scheduler[import]';
		$additionalFields = array();

			// Initialize extra field value
		if (empty($taskInfo['import']['pid'])) {
			if ($parentObject->CMD == 'add') {
				$pid = tx_import_api::getPid(
					$hookObjectsArray
				);
debug ($pid, 'getAdditionalFields $pid');
					// In case of new task and if field is empty, set default page id
				$taskInfo['import']['pid'] = $pid;
			} elseif ($parentObject->CMD == 'edit') {
					// In case of edit, set to internal value if no data was submitted already
				$taskInfo['import']['pid'] = $task->pid;
			} else {
					// Otherwise set an empty value, as it will not be used anyway
				$taskInfo['import']['pid'] = 0;
			}
		}

		$fieldID = $mainFieldID . '_pid';
		$fieldCode = '<input type="text" name="' . $mainFieldName . '[pid]" id="' . $fieldID . '" value="' .  	$taskInfo['import']['pid'] . '" size="10" />';
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:import/tasks/locallang.xml:label.pid',
			'cshKey'   => '_MOD_tools_tximportM1',
			'cshLabel' => $fieldID
		);

		tx_import_api::getMenuAndFiles(
			$hookObjectsArray,
			$globalTableFileArray,
			$menuItems
		);
debug ($globalTableFileArray, 'getAdditionalFields $globalTableFileArray ');
debug ($menuItems, 'getAdditionalFields $menuItems ');

		if (
			isset($globalTableFileArray) &&
			is_array($globalTableFileArray) &&
			isset($menuItems) &&
			is_array($menuItems)
		) {
debug ($taskInfo['import'], '$taskInfo[\'import\']');
			foreach ($globalTableFileArray as $k => $tableArray) {
				$tablename = $tableArray['table'];

					// Initialize extra field value
				if (empty($taskInfo['import'][$k])) {
					if ($parentObject->CMD == 'add') {
							// In case of new task and if field is empty, set default import filename
						$taskInfo['import'][$k] = $tableArray['file'];
					} elseif ($parentObject->CMD == 'edit') {
							// In case of edit, set to internal value if no data was submitted already
						$taskInfo['import'][$k] = $task->filenameArray[$k]['file'];
					} else {
							// Otherwise set an empty value, as it will not be used anyway
						$taskInfo['import'][$k] = '';
					}
				}
				$fieldID = $mainFieldID . '_' . $k;
				$fieldCode = '<input type="text" name="' . $mainFieldName . '[' . $k . ']" id="' . $fieldID . '" value="' . htmlspecialchars($taskInfo['import'][$k]) . '" size="60" />';

				$additionalFields[$fieldID] = array(
					'code'     => $fieldCode,
					'label'    => $menuItems[$k],
					'cshKey'   => '_MOD_tools_tximportM1',
					'cshLabel' => $fieldID
				);
			}
		}

debug ($taskInfo, 'getAdditionalFields ENDE $taskInfo');
debugEnd();
		return $additionalFields;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return TRUE
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_importttproducts_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields (
		array &$submittedData,
		tx_scheduler_Module $parentObject
	) {
		$result = TRUE;
		$message = '';

		$submittedData['import']['pid'] = intval($submittedData['import']['pid']);

		if ($submittedData['import']['pid'] <= 0) {
			$parentObject->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:import/tasks/locallang.xml:msg.invalidPid'),
				t3lib_FlashMessage::ERROR
			);
			$result = FALSE;
		}

		$hookObjectsArray = tx_import_api::getHookArray();
		tx_import_api::getMenuAndFiles(
			$hookObjectsArray,
			$globalTableFileArray,
			$menuItems
		);

		if (
			isset($globalTableFileArray) &&
			is_array($globalTableFileArray) &&
			isset($menuItems) &&
			is_array($menuItems)
		) {
			$message = '';
			foreach ($globalTableFileArray as $k => $tableArray) {
				$tablename = $tableArray['table'];

				if (
					!isset($submittedData['import'][$k]) ||
					!is_array($submittedData['import'][$k])
				) {
					continue;
				}
				$filename = $submittedData['import'][$k];

				if ($filename == '') {
					continue;
				}
				$absFilename = t3lib_div::getFileAbsFileName($filename);
				if (is_file($absFilename)) {
					$iFile = fopen($absFilename, 'rb');
				} else {
					$message = $GLOBALS['LANG']->sL('LLL:EXT:import/tasks/locallang.xml:msg.fileNotFound');
					$message = str_replace('|', $filename, $message);
					break;
				}

				if ($iFile == NULL) {
					$message = $GLOBALS['LANG']->sL('LLL:EXT:import/tasks/locallang.xml:msg.fileNotReadable');
					$message = str_replace('|', $filename, $message);
					break;
				} else {
					$resultClose = fclose($iFile);
					if (!$resultClose) {
						$message = $GLOBALS['LANG']->sL('LLL:EXT:import/tasks/locallang.xml:msg.fileCloseError');
						$message = str_replace('|', $filename, $message);
						break;
					}
				}

				// Dateityp bestimmen
				$basename = basename($filename);
				$posFileExtension = strrpos($basename, '.');
				$fileExtension = substr($basename, $posFileExtension + 1);
				$alloweFileExtensions = array('xml');

				if (!in_array($fileExtension, $alloweFileExtensions)) {
					$message = $GLOBALS['LANG']->sL('LLL:EXT:import/tasks/locallang.xml:msg.fileTypeError');
					$message = str_replace('|', $filename, $message);
					break;
				} else if ($fileExtension == 'xml') {
					$objDom = new domDocument();
					$resultLoad =
						$objDom->load(
							$absFilename,
							LIBXML_COMPACT
						);

					if (!$resultLoad) {
						$message = $GLOBALS['LANG']->sL('LLL:EXT:import/tasks/locallang.xml:msg.fileXmlNotReadable');
						$message = str_replace('|', $filename, $message);
						break;
					}
				}
			}

			if ($message != '') {
				$parentObject->addMessage(
					$message,
					t3lib_FlashMessage::ERROR
				);
				$result = FALSE;
			}
		}

		return $result;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param array $submittedData Array containing the data submitted by the user
	 * @param tx_importttproducts_Task $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields (
		array $submittedData,
		tx_scheduler_Task $task
	) {
debugBegin();
		$task->pid = intval($submittedData['import']['pid']);

		$hookObjectsArray = tx_import_api::getHookArray();
		tx_import_api::getMenuAndFiles(
			$hookObjectsArray,
			$globalTableFileArray,
			$menuItems
		);

		if (
			isset($globalTableFileArray) &&
			is_array($globalTableFileArray) &&
			isset($menuItems) &&
			is_array($menuItems)
		) {
			$filenameArray = array();
debug ($globalTableFileArray, 'saveAdditionalFields $globalTableFileArray');
			foreach ($globalTableFileArray as $k => $tableArray) {
				$tablename = $tableArray['table'];
				$extKey = $tableArray['extKey'];
				$filename = $submittedData['import'][$k];
				$tableFileArray['table'] = $tablename;
				$tableFileArray['extKey'] = $extKey;
				$tableFileArray['file'] = $filename;
				$filenameArray[] = $tableFileArray;
			}

			$task->filenameArray = $filenameArray;
		}
debugEnd();
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/import/tasks/class.tx_import_task_additionalfieldprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/import/tasks/class.tx_import_task_additionalfieldprovider.php']);
}

?>