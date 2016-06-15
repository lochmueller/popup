<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$extendClasses = [
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\GraphicalMenuContentObject'  => 'FRUIT\\Popup\\Xclass\\GraphicalMenuContentObject',
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\ImageMenuContentObject'      => 'FRUIT\\Popup\\Xclass\\ImageMenuContentObject',
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\JavaScriptMenuContentObject' => 'FRUIT\\Popup\\Xclass\\JavaScriptMenuContentObject',
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\TextMenuContentObject'       => 'FRUIT\\Popup\\Xclass\\TextMenuContentObject',
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'             => 'FRUIT\\Popup\\Xclass\\ContentObjectRenderer',
];
foreach ($extendClasses as $source => $target) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$source] = [
        'className' => $target,
    ];
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', '
    tt_content.text.20.parseFunc.tags.linkpop < tt_content.text.20.parseFunc.tags.link
    tt_content.text.20.parseFunc.tags.linkpop.typolink.userFunc = tx_popup->textParse
    tt_content.text.20.parseFunc.tags.linkpop.typolink.parameter.data = parameters : allParams
', 43);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('FRUIT.popup', 'Popup', [
    'Popup' => 'index',
]);
