<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {

	t3lib_extMgm::insertModuleFunction(
		'web_func',
		'tx_import_modfunc1',
		t3lib_extMgm::extPath('import') . 'modfunc1/class.tx_import_modfunc1.php',
		'LLL:EXT:import/locallang.xml:moduleFunction.tx_import_modfunc1',
		'wiz'
	);
}

?>