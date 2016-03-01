<?php

$tempColumns = [
    'tx_popup_configuration'      => [
        'exclude' => 1,
        'label'   => 'LLL:EXT:popup/Resources/Private/Language/locallang.xml:pages.tx_popup_configuration',
        'config'  => [
            'type'    => 'input',
            'wizards' => [
                'link' => [
                    'type'         => 'popup',
                    'title'        => 'Popup Wizard',
                    'icon'         => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('popup') . 'ext_icon.png',
                    'JSopenParams' => 'height=560,width=240,status=0,menubar=0,scrollbars=0',
                    'module'       => array(
                        'name' => 'wizard_popup',
                    ),
                ],
            ],
        ],
    ],
    'tx_popup_auto'               => [
        'exclude' => 1,
        'label'   => 'LLL:EXT:popup/Resources/Private/Language/locallang.php:pages.tx_popup_auto',
        'config'  => [
            'type'     => 'input',
            'size'     => '35',
            'checkbox' => '',
            'eval'     => 'trim',
            'wizards'  => [
                'link' => [
                    'type'         => 'popup',
                    'title'        => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel',
                    'icon'         => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                    'module'       => [
                        'name' => 'wizard_link',
                    ],
                    'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
                ],
            ],
        ],
    ],
    'tx_popup_auto_configuration' => [
        'exclude' => 1,
        'label'   => 'LLL:EXT:popup/Resources/Private/Language/locallang.xml:pages.tx_popup_auto_configuration',
        'config'  => [
            'type'    => 'input',
            'wizards' => [
                'link' => [
                    'type'         => 'popup',
                    'title'        => 'Popup Wizard',
                    'icon'         => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('popup') . 'ext_icon.png',
                    'JSopenParams' => 'height=740,width=240,status=0,menubar=0,scrollbars=0',
                    'module'       => array(
                        'name'          => 'wizard_popup',
                        'urlParameters' => [
                            'advanced' => 1,
                        ],
                    ),
                ],
            ],
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages',
    'tx_popup_configuration;;;;1-1-1,tx_popup_auto;;69');
$GLOBALS['TCA']['pages']['palettes']['69']['showitem'] = 'tx_popup_auto_configuration';