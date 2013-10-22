<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

Tx_Extbase_Utility_Extension::registerPlugin(
	$_EXTKEY,
	'DocCheckAuthentication',
	'LLL:EXT:ap_docchecklogin/Resources/Private/Language/locallang_backend.xml:pluginName'
);

t3lib_div::loadTCA('tt_content');

$pluginSignature = 'apdocchecklogin_doccheckauthentication';

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';

t3lib_extMgm::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/setup.xml');
