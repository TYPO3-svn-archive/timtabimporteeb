<?php
//
//	$Id$
//

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

//registering for several hooks
$TYPO3_CONF_VARS['EXTCONF']['timtab']['importers'][$_EXTKEY]['name']  = 'EXT:ee_blog Import';
$TYPO3_CONF_VARS['EXTCONF']['timtab']['importers'][$_EXTKEY]['class'] = 'tx_timtabimporteeb';
?>