<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('ap_docchecklogin', 'Configuration/TypoScript/Static', 'DocCheck Login');

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Antwerpes.ApDocchecklogin',
            'DocCheckAuthentication',
            'LLL:EXT:ap_docchecklogin/Resources/Private/Language/locallang_backend.xml:pluginName'
        );
    }
);
