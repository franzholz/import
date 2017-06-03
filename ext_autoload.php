<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id$
 */

$extensionPath = t3lib_extMgm::extPath('import');

return array(
	'tx_import_hooks_base' => $extensionPath . 'hooks/class.tx_import_hooks_base.php',
	'tx_import_api' => $extensionPath . 'api/class.tx_import_api.php',
	'tx_import_task' => $extensionPath . 'tasks/class.tx_import_task.php',
	'tx_import_task_additionalfieldprovider' => $extensionPath . 'tasks/class.tx_import_task_additionalfieldprovider.php',
);

?>