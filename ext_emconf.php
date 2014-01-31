<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ap_docchecklogin".
 *
 * Auto generated 03-09-2013 12:40
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'DocCheck Login',
	'description' => 'Official DocCheck Login Extension for Typo3 6.x',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.0.5',
	'dependencies' => 'extbase,fluid',
	'conflicts' => 'tgr_doccheck,kb_md5fepw',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'antwerpes ag - see README.txt',
	'author_email' => 'opensource@antwerpes.de',
	'author_company' => 'antwerpes ag',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' => 
		array (
			'conflicts' =>
				array (
					'kb_md5fepw' => '',
					'tgr_doccheck' => ''
				),
			'depends' =>
				array (
					'php' => '5.3.0-0.0.0',
					'typo3' => '6.0.0-6.1.99',
					'extbase' => '0.0.0-0.0.0',
					'fluid' => '0.0.0-0.0.0'
				),
			'suggests' =>
				array (
				),
		),
);