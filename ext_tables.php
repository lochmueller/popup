<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$tempColumns = [
    'tx_popup_configuration' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:popup/locallang_db.xml:pages.tx_popup_configuration',
        'config' => [
            'type' => 'input',
            'size' => '15',
            'checkbox' => '',
            'wizards' => [
                '_PADDING' => 5,
                'link' => [
                    'type' => 'popup',
                    'title' => 'Popup Wizard:',
                    'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('popup') . 'ext_icon.png',
                    'JSopenParams' => 'height=460,width=240,status=0,menubar=0,scrollbars=0',
                    'script' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('popup') . 'wizard/index.php'
                ],
            ],
        ],
    ],
    'tx_popup_auto' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:popup/locallang_db.php:pages.tx_popup_auto',
        'config' => [
            'type' => 'input',
            'size' => '35',
            'checkbox' => '',
            'eval' => 'trim',
            'wizards' => [
                '_PADDING' => 2,
                'link' => [
                    'type' => 'popup',
                    'title' => 'Link',
                    'icon' => 'link_popup.gif',
                    'script' => 'browse_links.php?mode=wizard',
                    'JSopenParams' => 'height=340,width=500,status=0,menubar=0,scrollbars=1'
                ],
            ],
        ],
    ],
    'tx_popup_auto_configuration' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:popup/locallang_db.php:pages.tx_popup_auto_configuration',
        'config' => [
            'type' => 'input',
            'size' => '15',
            'checkbox' => '',
            'wizards' => [
                '_PADDING' => 5,
                'link' => [
                    'type' => 'popup',
                    'title' => 'Popup Wizard:',
                    'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('popup') . 'ext_icon.png',
                    'JSopenParams' => 'height=640,width=240,status=0,menubar=0,scrollbars=0',
                    'script' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('popup') . 'wizard/index.php?advanced=1'
                ],
            ],
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages', 'EXT:popup/locallang_db.xml');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/', 'Javascript Popup');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages',
    'tx_popup_configuration;;;;1-1-1,tx_popup_auto;;69');
$GLOBALS['TCA']['pages']['palettes']['69']['showitem'] = 'tx_popup_auto_configuration';
