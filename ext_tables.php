<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages',
    'EXT:popup/Resources/Private/Language/locallang.xml');

// Register add wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath('wizard_popup', 'EXT:popup/wizard/');