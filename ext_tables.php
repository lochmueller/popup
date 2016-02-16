<?php
if(!defined('TYPO3_MODE'))
	die('Access denied.');

$tempColumns = Array (
	'tx_popup_configuration' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:popup/locallang_db.xml:pages.tx_popup_configuration',
		'config' => array (
			'type' => 'input',
			'size' => '15',
			'checkbox' => '',
			'wizards' => array(
				'_PADDING' => 5,
				'link' => array(
					'type' => 'popup',
					'title' => 'Popup Wizard:',
					'icon' => t3lib_extMgm::extRelPath('popup').'ext_icon.png',
					'JSopenParams' => 'height=460,width=240,status=0,menubar=0,scrollbars=0',
					'script' => t3lib_extMgm::extRelPath('popup').'wizard/index.php'
				),
			),
		),
	),
	'tx_popup_auto' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:popup/locallang_db.php:pages.tx_popup_auto',
		'config' => Array (
			'type' => 'input',
			'size' => '35',
			'checkbox' => '',
			'eval' => 'trim',
			'wizards' => Array(
				'_PADDING' => 2,
				'link' => Array(
					'type' => 'popup',
					'title' => 'Link',
					'icon' => 'link_popup.gif',
					'script' => 'browse_links.php?mode=wizard',
					'JSopenParams' => 'height=340,width=500,status=0,menubar=0,scrollbars=1'
				),
			),
		),
	),
	'tx_popup_auto_configuration' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:popup/locallang_db.php:pages.tx_popup_auto_configuration',
		'config' => array (
			'type' => 'input',
			'size' => '15',
			'checkbox' => '',
			'wizards' => array(
				'_PADDING' => 5,
				'link' => array(
					'type' => 'popup',
					'title' => 'Popup Wizard:',
					'icon' => t3lib_extMgm::extRelPath('popup').'ext_icon.png',
					'JSopenParams' => 'height=640,width=240,status=0,menubar=0,scrollbars=0',
					'script' => t3lib_extMgm::extRelPath('popup').'wizard/index.php?advanced=1'
				),
			),
		),
	),
);

t3lib_extMgm::addLLrefForTCAdescr('pages','EXT:popup/locallang_db.xml');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/', 'Javascript Popup');

t3lib_div::loadTCA('pages');
t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages','tx_popup_configuration;;;;1-1-1,tx_popup_auto;;69');
global $TCA;
$TCA['pages']['palettes']['69']['showitem'] = 'tx_popup_auto_configuration';

?>