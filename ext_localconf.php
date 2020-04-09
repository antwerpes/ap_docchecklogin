<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Antwerpes.' . $_EXTKEY,
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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'Antwerpes\\ApDocchecklogin\\DocCheckAuthenticationService', array(
    'title' => 'DocCheck Authentication Service',
    'description' => 'Authenticates users through the DocCheck Authentication Service',
    'subtype' => 'getUserFE,authUserFE',
    'available' => true,
    'priority' => 60,
    'quality' => 60,
    'os' => '',
    'exec' => '',
    'className' => 'Antwerpes\\ApDocchecklogin\\DocCheckAuthenticationService'
));

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'][] = 'Antwerpes\ApDocchecklogin\DocCheckAuthenticationService->bypassLoginForCrawling';
