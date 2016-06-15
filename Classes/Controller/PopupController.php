<?php

namespace FRUIT\Popup\Controller;

use FRUIT\Popup\Popup;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class PopupController
 *
 * @package FRUIT\Popup\Controller
 */
class PopupController extends ActionController
{

    /**
     * Same as class name
     */
    public $prefixId = 'tx_popup_pi1';

    /**
     * The extension key.
     */
    public $extKey = 'popup';

    /**
     * @var Popup
     */
    protected $popup;

    /**
     * @var array
     */
    protected $customParams = [];

    /**
     * Init the popup frontend plugin
     *
     * @return void
     */
    protected function init()
    {
        $this->popup = GeneralUtility::makeInstance('FRUIT\\Popup\\Popup');
        $this->allowedParams = $this->popup->allowedParams;
        $this->customParams = $this->popup->advancedParams;
    } # function - init

    /**
     * Generate JS code for opening pop-up
     * Main Plugin function for T3
     *
     * @return string
     */
    public function indexAction()
    {
        // Init
        $this->init();

        $link = $this->configurationManager->getContentObject()->data['tx_popup_auto'];
        if (!$link) {
            GeneralUtility::devLog('No link defined', $this->extKey);
            return '';
        }

        $ts = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $conf = $ts['plugin.']['tx_popup_pi1.'];

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
        $url = $this->configurationManager->getContentObject()
            ->getTypoLink_URL($link);
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

        $tmp_params = [];
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
