<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'DocCheckAuthentication',
	array(
		'DocCheckAuthentication' => 'main, loggedIn, loginForm',
	),
	// non-cacheable actions
	array(
		'DocCheckAuthentication' => 'main, loggedIn',
	)
);

// Add the service
t3lib_extMgm::addService($_EXTKEY, 'auth', 'Tx_ApDocchecklogin_DocCheckAuthenticationService', array(
	'title' => 'DocCheck Authentication Service',
	'description' => 'Authenticates users through the DocCheck Authentication Service',
	'subtype' => 'getUserFE,authUserFE',
	'available' => TRUE,
	'priority' => 60,
	'quality' => 60,
	'os' => '',
	'exec' => '',
	'classFile' => t3lib_extMgm::extPath($_EXTKEY).'Classes/DocCheckAuthenticationService.php',
	'className' => 'Tx_ApDocchecklogin_DocCheckAuthenticationService'
));