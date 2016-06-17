<?php

namespace FRUIT\Popup\Controller;

use FRUIT\Popup\Popup;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
            return '';
        }
        // create the url
        $url = $this->configurationManager->getContentObject()
            ->getTypoLink_URL($link);
        if (!$url) {
            return '';
        }

        $ts = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $conf = $ts['plugin.']['tx_popup_pi1.'];

        // get session data
        if ($conf['advancedParams.']['once_per_session']) {
            $popups = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);
            if ($conf['advancedParams.']['once_per_link']) {
                if ($popups['links'][$link]) {
                    return '';
                } else {
                    $popups['links'][$link] = 1;
                }
            } else {
                if ($popups['pages'][$GLOBALS['TSFE']->id]) { // we have been on the current page
                    return '';
                } else {
                    $popups['pages'][$GLOBALS['TSFE']->id] = 1;
                }
            }
            $GLOBALS['TSFE']->fe_user->setKey('ses', $this->prefixId, $popups);
        }

        $cParams = [];
        foreach ($this->customParams as $name => $type) {
            $v = $conf['advancedParams.'][$name];
            $cParams[$name] = ($v === '1' || $v == 'yes' || $v == 'on') ? true : false;
        }

        $params = (array)$conf['allowedParams.'];
        if ($cParams['maximize']) {
            $params['left'] = 0;
            $params['top'] = 0;
            $params['width'] = '\' + screen.width + \'';
            $params['height'] = '\' + screen.height + \'';
        } elseif ($cParams['center'] && $params['width'] > 0 && $params['height'] > 0) {
            $params['left'] = '\' + ((screen.width - ' . $params['width'] . ') / 2) + \'';
            $params['top'] = '\' + ((screen.height - ' . $params['height'] . ') / 2) + \'';
            $params['screenX'] = '\' + ((screen.width - ' . $params['width'] . ') / 2) + \'';
            $params['screenY'] = '\' + ((screen.height - ' . $params['height'] . ') / 2) + \'';
        }

        $params = $this->convertArrayToJsParams($params);
        return $this->getHtml($url, $params, $cParams);
    }

    /**
     * @param array $array
     * @return string
     */
    protected function convertArrayToJsParams(array $array)
    {
        $tmp_params = [];
        while (list($key, $val) = each($array)) {
            if (isset($val) && $val != '') {
                $tmp_params[] = $key . '=' . $val;
            }
        }
        return implode(",", $tmp_params);
    }

    /**
     * @param $url
     * @param $params
     * @param $cParams
     * @return string
     */
    protected function getHtml($url, $params, $cParams)
    {
        $window = uniqid('_popup_');

        $content = '<script type="text/javascript">';
        $content .= "var $window = window.open('$url', 'Window', '$params');\n";
        // thanks to Tom Binder for the timeout
        if ($cParams['popunder']) {
            $content .= "if ($window) { window.setTimeout('$window.blur()',500); window.focus(); }";
        } else {
            $content .= "if ($window) { window.setTimeout('$window.focus()',500); }";
        }

        return $content . "\n</script>\n";
    }
}
