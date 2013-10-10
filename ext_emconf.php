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
	'description' => 'Integrate DocCheck Login',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.0.1',
	'dependencies' => '',
	'conflicts' => '',
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
			0 => 'kb_md5fepw',
			1 => 'tgr_doccheck'
		),
		'depends' => 
		array (
			'' => '',
		),
		'suggests' => 
		array (
		),
	),
);

?>