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
 * Class "tx_import_Task" provides a task that imports files into the tables of TYPO3 extensions
 * Other TYPO3 extensions can add their tables by using the hook method.
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package		TYPO3
 * @subpackage	tx_import
 */
class tx_import_Task extends tx_scheduler_Task {

	/**
	 * Array of filenames to import
	 *
	 * @var array $filenameArray
	 */
	public $filenameArray;

	/**
	 * page id
	 *
	 * @var integer $pid
	 */
	public $pid;

	/**
	 * Function executed from the Scheduler.
	 * imports all files
	 *
	 * @return boolean
	 */
	public function execute () {

// 	require_once(t3lib_extMgm::extPath('import_tt_products') . 'api/class.tx_importttproducts_api.php');
// $errorFile = tx_fhdebug::getErrorLogFilename();
// error_log('tx_import_Task::execute', 3, $errorFile);

		$result = FALSE;
debugBegin();

debug($this->pid, 'tx_import_Task execute Start $this->pid');
		if (isset($this->filenameArray) && is_array($this->filenameArray)) {
			foreach ($this->filenameArray as $tableFileArray) {
debug($tableFileArray, 'tx_import_Task execute $tableFileArray');
				$tablename = $tableFileArray['table'];
debug($tablename, 'tx_import_Task execute $tablename');
				$filename = $tableFileArray['file'];
debug ($filename, 'tx_import_Task execute $filename');
				$extKey = $tableFileArray['extKey'];
debug ($extKey, 'tx_import_Task execute $extKey');
				$result = TRUE;

				if (t3lib_extMgm::isLoaded($extKey)) {
					if ($extKey == 'import_tt_products') {
						$result =
							tx_importttproducts_api::import(
								$tablename,
								$filename,
								$this->pid,
								$failure
							);
					} else if ($extKey == 'import_locator') {
						$result =
							tx_importlocator_api::import(
								$tablename,
								$filename,
								$this->pid,
								$failure
							);
					} else {
						$result = FALSE;
					}
				}
debug($failure, 'tx_import_Task $failure nach import' );

				if ($failure instanceof Exception) {
						// Log failed execution
					$logMessage = $failure->getMessage() . ' Class: '
						. get_class($this) . ', UID: '
						. $this->taskUid . '. ' ;
					$this->scheduler->log(
						$logMessage,
						1,
						$failure->getCode()
					);

debug($logMessage, 'tx_import_Task $logMessage' );
				}

				if ($result === FALSE) {
debug($result, 'tx_import_Task $result break ***' );
					break;
				}
			}
		}
debug($result, 'tx_import_Task execute ENDE $result' );
debugEnd();

		return $result;
	}

	/**
	 * This method returns additional information
	 *
	 * @return string Information to display
	 */
	public function getAdditionalInformation () {
		return 'Page: ' . $this->pid;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/import/tasks/class.tx_import_task.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/import/tasks/class.tx_import_task.php']);
}

?>