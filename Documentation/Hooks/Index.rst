.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _hooks:

Hooks / SignalSlots
===================

This Extension offers the Signal Slot "beforeRedirect", which is called before a successfully logged-in
user will be redirected.

Usage from within your extension's ext_localconf.php
::
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->connect(
 	  'Antwerpes\\ApDocchecklogin\\Controller\\DocCheckAuthenticationController',
	  \Antwerpes\ApDocchecklogin\Controller\DocCheckAuthenticationController::SIGNAL_BEFORE_REDIRECT,
 	  'Vendor\\The\\Implementing\\Class',
 	  'methodToBeCalled'
	);
	
Hook (old style)
----------------

If you prefer the old-fashioned hook style, use the following within your ext_tables.php
::
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ap_docchecklogin']
	  [\Antwerpes\ApDocchecklogin\Controller\DocCheckAuthenticationController::SIGNAL_BEFORE_REDIRECT][]
	    = 'EXT:' . $_EXTKEY . '/Classes/Hooks/HookImplementingClass.php:\\Vendor\\YourExt\\Hooks\\HookImplementingClass->hookImplementingMethod';