<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger <franz@ttproducts.de>
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
 * Module extension (addition to function menu) 'Import scripts' for the 'import' extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package	TYPO3
 * @subpackage	import
 */
class tx_import_modfunc1 extends t3lib_extobjbase {

	/**
		* Returns the module menu
		*
		* @return	Array with menuitems
		*/
	public function modMenu () {
		global $LANG;

		return array (
			'tx_import_modfunc1_filename' => ''
		);
	}

	/**
		* Main method of the module
		*
		* @return	HTML
		*/
	public function main () {
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $SOBE, $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS, $TYPO3_DB;

		$hookObjectsArray = tx_import_api::getHookArray();
		$currId = $this->pObj->id;

		$theOutput .= $this->pObj->doc->spacer(5);
		if ($BE_USER->user['admin']) {
			$content = sprintf($LANG->getLL('pid_src'), $currId);
		} else {
			$content = $LANG->getLL('only_admin');
		}
		$theOutput .= $this->pObj->doc->section($LANG->getLL('title'), $content, 0, 1);

		$filename = $this->pObj->MOD_SETTINGS['tx_import_modfunc1_filename'];
		$pid = intval($this->pObj->MOD_SETTINGS['tx_import_modfunc1_pid']);

		if($filename && $tablename) {
			$content .= '<br />' . $LANG->getLL('imported');
		}

		if ($BE_USER->user['admin']) {
			if (count($hookObjectsArray)) {

				tx_import_api::getMenuAndFiles(
					$hookObjectsArray,
					$globalTableFileArray,
					$menuItems
				);

				$currMenu = ($_REQUEST['importSelect'] ? $_REQUEST['importSelect'] : 0);
				$tableFile = $globalTableFileArray[$currMenu];
				$menuOut =
					t3lib_BEfunc::getFuncMenu(
						array('id' => $currId),
						'importSelect',
						$currMenu,
						$menuItems
					);
				$menu = array();

				$content = $menuOut;
				$content .= '<br />' . $tableFile['table'] . '<br />';

				$filename = ($filename != '' ? $filename : $tableFile['file']);
				$content .= '<br />' . $LANG->getLL('filename') .
					t3lib_BEfunc::getFuncInput(
						$currId,
						'SET[tx_import_modfunc1_filename]',
						$filename,
						50
					);

				$content .= '<br />' . $LANG->getLL('page_id') .
					t3lib_BEfunc::getFuncInput(
						$currId,
						'SET[tx_import_modfunc1_pid]',
						$pid,
						5
					);

				$content .= '<br /><input type="submit" name="start" value="' . $LANG->getLL('start') . '">';
				if ($_REQUEST['start'] != '' && $filename != '') {
					foreach ($hookObjectsArray as &$hookObject) {
						$tableFileArray = $hookObject->getTableFileArray();
						if (isset($tableFileArray) && is_array($tableFileArray)) {
							foreach ($tableFileArray as $theTableFile) {
								if ($theTableFile['table'] == $tableFile['table']) {
									$importContent =
										$hookObject->import(
											$theTableFile['table'],
											$filename,
											$pid,
											$failure
										);

										if ($failure instanceof Exception) {
											$renderCharset = '';
											if (is_object($GLOBALS['TSFE'])) {
												$renderCharset = $GLOBALS['TSFE']->renderCharset;
											} else if (is_object($GLOBALS['LANG'])) {
												$renderCharset = $GLOBALS['LANG']->charSet;
											} else {
												$renderCharset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
											}

											$message = $failure->getMessage();
											$importContent = $csConvObj->conv($message, 'utf-8', $renderCharset);
										}

									$content .= '<br />' . $importContent;
								}
							}
						}
					}
				}
			} else {
				$content = '<br />' . $LANG->getLL('no_import_extension');
			}
			$menu[] = $content;
			$theOutput .= $this->pObj->doc->spacer(5);
			$theOutput .=
				$this->pObj->doc->section(
					$LANG->getLL('tablename'),
					implode(' - ', $menu),
					0,
					1
				);

		} // if ($BE_USER->user['admin'])

		return $theOutput;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/import/modfunc1/class.tx_import_modfunc1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/import/modfunc1/class.tx_import_modfunc1.php']);
}

