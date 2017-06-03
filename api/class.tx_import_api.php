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
*  the Free Software Foundation; either version 2 of the License or
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
 * API functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage import
 *
 */



class tx_import_api {

	static public function getHookArray () {
		$hookObjectsArray = array();

		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/import/modfunc1/class.tx_import_modfunc1.php']['addClass']) &&
			is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/import/modfunc1/class.tx_import_modfunc1.php']['addClass'])
		) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/import/modfunc1/class.tx_import_modfunc1.php']['addClass'] as $classRef) {
				$addObj = t3lib_div::getUserObj($classRef);
				$addObj->initLang();
				$hookObjectsArray[] = $addObj;
			}
		}

		return $hookObjectsArray;
	}

	static public function getPid (
		$hookObjectsArray
	) {
		$pidArray = array();

		foreach($hookObjectsArray as &$hookObject) {
			if (method_exists($hookObject, 'getPid')) {
				$pidArray[] = $hookObject->getPid();
			}
		}

		$pidArray = array_unique($pidArray);
		$pid = implode(',', $pidArray);
		return $pid;
	}

	static public function getMenuAndFiles (
		$hookObjectsArray,
		&$globalTableFileArray,
		&$menuItems
	) {
		$globalTableFileArray = array();
		$menuItems = array();

		foreach($hookObjectsArray as $hookObject) {
			$tableFileArray = $hookObject->getTableFileArray();
			$extKey = $hookObject->getExtKey();

			if (isset($tableFileArray) && is_array($tableFileArray)) {
				foreach ($tableFileArray as $tableFile) {
					$tableFile['extKey'] = $extKey;
					$globalTableFileArray[] = $tableFile;
					$menuItem = $GLOBALS['LANG']->getLL($tableFile['table']);
					$menuItems[] = $menuItem;
				}
			}
		}
	}
}

