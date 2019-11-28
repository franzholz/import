<?php

########################################################################
# Extension Manager/Repository config file for ext: "import"
########################################################################

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Import of data into database tables',
    'description' => 'This helps you to import data from text files into the tables of TYPO3 or its extensions.',
    'category' => 'module',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author_company' => '',
    'version' => '0.3.0',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5.0-7.99.99',
            'typo3' => '7.6.0-9.99.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
            'typo3db_legacy' => '1.0.0-1.1.99',
        ),
    ),
);

