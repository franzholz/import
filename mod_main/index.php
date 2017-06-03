<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Franz Holzinger <franz@ttproducts.de>
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


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:import/mod_main/locallang.xml');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'Import' for the 'import' extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package	TYPO3
 * @subpackage	tx_import
 */
class  tx_import_module1 extends t3lib_SCbase {
	private $hookObjectsArray;
	private $hookFuncArray;
	public $pageinfo;				// Current ids page record

	public function __construct () {
		$this->hookObjectsArray = array();

		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/import/mod_main/index.php']['addClass']))	{
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/import/mod_main/index.php']['addClass'] as $classRef)	{
				$this->hookObjectsArray[] = &t3lib_div::getUserObj($classRef);
			}
		}
	}

	/**
		* Initializes the Module
		* @return	void
		*/
	function init () {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	**/
	function menuConfig () {
		global $LANG;
		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $LANG->getLL('info'),
			)
		);
		$this->hookFuncArray = array();

		$menuIndex = 2;
			// Hooks:
		foreach ($this->hookObjectsArray as $k => $hookObj)	{
			if (method_exists($hookObj,'menuConfig'))	{
				$oldMenuIndex = $menuIndex;
				$hookObj->menuConfig($this->MOD_MENU, $menuIndex, $this);
				for ($i=$oldMenuIndex; $i<$menuIndex; ++$i)	{
					$this->hookFuncArray[$i]=$k;
				}
			}
		}
		parent::menuConfig();
	}

	/**
		* Main function of the module. Write the content to $this->content
		* If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
		*
		* @return	[type]		...
		*/
	function main () {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id)) {

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL) {
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);


			// Render content:
			$this->moduleContent();


			// ShortCut
			if ($BE_USER->mayMakeShortcut()) {
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
		* Prints out the module HTML
		*
		* @return	void
		*/
	function printContent () {

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
		* Generates the module content
		*
		* @return	void
		*/
	function moduleContent () {
		switch((string)$this->MOD_SETTINGS['function']) {
			case 1:
				$content = '<div align="center"><strong>Import Extension</strong></div><br />
					Extend this extension to support more import functions for more file types and tables!
					<hr />
					<br />This is the GET/POST vars sent to the script:<br />'.
					'GET:' . t3lib_utility_Debug::viewArray($_GET) . '<br />'.
					'POST:' . t3lib_utility_Debug::viewArray($_POST) . '<br />'.
					'';
				$this->content .= $this->doc->section('Information:' ,$content ,0, 1);
			break;
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/import/mod_main/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/import/mod_main/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('tx_import_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>