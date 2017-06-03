<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2012 Franz Holzinger <franz@ttproducts.de>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the import extension.
 *
 * hook base functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage import
 *
 *
 */


class tx_import_hooks_base {
	public $extKey; // must be overridden
	public $prefixId; // must be overridden
	public $LLFileArray;
	public $pid;
	public $modMenu;
	public $headerText;
	protected $cObj;
	public $vars = Array (	// This is the incoming array by name $this->prefixId merged between POST and GET, POST taking precedence. Eg. if the class name is 'tx_myext' then the content of this array will be whatever comes into &tx_myext[...]=...
		'import' => '',			// import extension
	);
	public $tableFileArray;

	/* $tableFileArray ... array of table and import file */
	public function init (&$tableFileArray, $LLFileArray, $pid) {
		$this->setTableFileArray($tableFileArray);
		$this->LLFileArray = $LLFileArray;
		$this->pid = intval($pid);
	}

	public function initLang () {
		global $LANG;

		if (isset($this->LLFileArray) && is_array($this->LLFileArray)) {
			foreach ($this->LLFileArray as $LLFile) {
				if (substr($LLFile, 0, 3) == 'EXT') {
					$LANG->includeLLFile($LLFile);
				} else {
					$LANG->includeLLFile('EXT:' . $this->extKey . '/' . $LLFile);
				}
			}
		}
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');	// Local cObj.
		$this->cObj->start(array());
		if ($this->prefixId) {
			$this->vars = t3lib_div::_GPmerged($this->prefixId);
		}
	}

	public function setTableFileArray (&$tableFileArray) {
		$this->tableFileArray = $tableFileArray;
	}

	public function getExtKey () {
		$result = FALSE;
		if ($this->extKey) {
			$result = $this->extKey;
		}
		return $result;
	}

	public function getPrefixId () {
		$result = FALSE;
		if ($this->prefixId) {
			$result = $this->prefixId;
		}
		return $result;
	}

	public function getPid () {
		return $this->pid;
	}

	public function &getTableFileArray () {
		return $this->tableFileArray;
	}

	public function import ($theTable, $theFile, $pid) {	// This must be overridden!
		$result = 'You must override the import function for table "' . $theTable . '" in class "' . get_class($this) . '"!';
		return $result;
	}

}


?>