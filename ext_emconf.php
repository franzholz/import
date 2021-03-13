<?php

########################################################################
# Extension Manager/Repository config file for ext: "import"
########################################################################

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Import of data into database tables',
    'description' => 'This helps you to import data from text files or tables into the tables of TYPO3 or extensions.',
    'category' => 'module',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author_company' => '',
    'version' => '0.5.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.7.0-9.5.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
            'func' => '7.6.0-9.99.99',
            'scheduler' => '7.6.0-9.99.99',
            "typo3db_legacy" => ''
        ),
    ),
);

