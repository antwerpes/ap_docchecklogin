<?php
defined('TYPO3_MODE') || die();

/***************
 * Add flexForms for content element configuration
 */
$pluginSignature = 'apdocchecklogin_doccheckauthentication';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
  $pluginSignature,
  'FILE:EXT:ap_docchecklogin/Configuration/FlexForms/setup.xml'
);
