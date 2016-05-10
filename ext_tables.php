<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'DocCheckAuthentication',
	'LLL:EXT:ap_docchecklogin/Resources/Private/Language/locallang_backend.xml:pluginName'
);

if (version_compare(TYPO3_branch, '6.1', '<')) {
    t3lib_div::loadTCA('tt_content');
} else if (version_compare(TYPO3_branch, '7.0', '<')) {
    \TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('tt_content');
}

$pluginSignature = 'apdocchecklogin_doccheckauthentication';

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/setup.xml');
