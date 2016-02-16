<?php
if(!defined('TYPO3_MODE'))
	die('Access denied.');

$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_menu.php'] = t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_menu.php';
$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_content.php'] = t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_cObj.php';
$TYPO3_CONF_VARS['FE']['XCLASS']['media/scripts/gmenu_layers.php'] = t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_gmenu_layers.php';
$TYPO3_CONF_VARS['FE']['XCLASS']['media/scripts/gmenu_foldout.php'] = t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_gmenu_foldout.php';

t3lib_extMgm::addTypoScript($_EXTKEY,'setup','
	tt_content.text.20.parseFunc.tags.linkpop < tt_content.text.20.parseFunc.tags.link
	tt_content.text.20.parseFunc.tags.linkpop.typolink.userFunc = tx_popup->textParse
	tt_content.text.20.parseFunc.tags.linkpop.typolink.parameter.data = parameters : allParams
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_popup_pi1.php','_pi1','list_type',1);
?>