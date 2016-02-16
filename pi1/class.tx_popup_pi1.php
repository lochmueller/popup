<?php
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Tim Lochmueller <tl@hdnet.de>
 *  2003-2006 Martin Kutschker <Martin.T.Kutschker@blackbox.net>
 *  2003: Traktor Wien (formerly Global Spanking Industries)
 *  2005: ACTIVE SOLUTION Software AG
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class tx_popup_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

    /**
     * Same as class name
     */
    public $prefixId = 'tx_popup_pi1';


    /**
     * Path to this script relative to the extension dir.
     */
    var $scriptRelPath = 'pi1/class.tx_popup_pi1.php';


    /**
     * The extension key.
     */
    var $extKey = 'popup';


    /**
     * Init the popup frontend plugin
     *
     * @return void
     */
    private function init()
    {
        $this->popup = GeneralUtility::makeInstance('FRUIT\\Popup\\Popup');
        $this->allowedParams = $this->popup->allowedParams;
        $this->customParams = $this->popup->advancedParams;
    } # function - init


    /**
     * Generate JS code for opening pop-up
     * Main Plugin function for T3
     *
     * @param $content    String
     * @param $conf        Array    Plugin configuration
     * @return The Plugin Output
     */
    function main($content, $conf)
    {
        // Init
        $this->init();


        $link = $this->cObj->data['tx_popup_auto'];
        if (!$link) {
            GeneralUtility::devLog('No link defined', $this->extKey);
            return '';
        }

        // get session data
        if ($conf['advancedParams.']['once_per_session']) {
            $popups = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);
            if ($conf['advancedParams.']['once_per_link']) {
                if ($popups['links'][$link]) {
                    GeneralUtility::devLog('Pop-up link "' . $link . '" already shown.', $this->extKey);
                    return '';
                } else {
                    $popups['links'][$link] = 1;
                }
            } else {
                if ($popups['pages'][$GLOBALS['TSFE']->id]) { // we have been on the current page
                    GeneralUtility::devLog('Pop-up already shown on this page.', $this->extKey);
                    return '';
                } else {
                    $popups['pages'][$GLOBALS['TSFE']->id] = 1;
                }
            }
            $GLOBALS['TSFE']->fe_user->setKey('ses', $this->prefixId, $popups);
        }

        // create the url
        $url = $this->cObj->getTypoLink_URL($link);
        if (!$url) {
            GeneralUtility::devLog('No valid pop-up (e.g. a hidden page):' . $link, $this->extKey);
            return '';
        }

        // get the JS window parameters directly (protect from errors in TS?)
        $params = $conf['allowedParams.'];

        // get the custom parameters
        $cParams = [];
        foreach ($this->customParams as $name => $type) {
            $v = $conf['advancedParams.'][$name];
            $cParams[$name] = ($v === '1' || $v == 'yes' || $v == 'on') ? true : false;
        }


        // thanks to Alex Widschwendter
        if ($cParams['maximize']) {
            $params['left'] = 0;
            $params['top'] = 0;
            $params['width'] = '\' + screen.width + \'';
            $params['height'] = '\' + screen.height + \'';
        } // thanks to Daniel Rampanelli
        elseif ($cParams['center'] && $params['width'] > 0 && $params['height'] > 0) {
            $params['left'] = '\' + ((screen.width - ' . $params['width'] . ') / 2) + \'';
            $params['top'] = '\' + ((screen.height - ' . $params['height'] . ') / 2) + \'';
            $params['screenX'] = '\' + ((screen.width - ' . $params['width'] . ') / 2) + \'';
            $params['screenY'] = '\' + ((screen.height - ' . $params['height'] . ') / 2) + \'';
        }

        while (list($key, $val) = each($params)) {
            if (isset($val) && $val != '') {
                $tmp_params[] = $key . '=' . $val;
            }
        }
        $params = implode(",", $tmp_params);


        // Build Javascript
        $content = '<script type="text/javascript">
		/*<![CDATA[*/
		<!--
		';
        $window = uniqid('_popup_');

        $content .= "var $window = window.open('$url', 'Window', '$params');\n";

        // thanks to Tom Binder for the timeout
        if ($cParams['popunder']) {
            $content .= "if ($window) { window.setTimeout('$window.blur()',500); window.focus(); }";
        } else {
            $content .= "if ($window) { window.setTimeout('$window.focus()',500); }";
        }

        $content .= "\n// -->\n/*]]>*/</script>\n";

        return $content;
    }
}