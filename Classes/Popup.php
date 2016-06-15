<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Tim Lochmueller (tl@hdnet.de)
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

namespace FRUIT\Popup;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * base class for the popup extension
 *
 * @author     Tim Lochmueller <tl@hdnet.de>
 * @package    popup
 * @subpackage tx_popup
 */
class Popup
{

    /**
     * cObj for the RTE replacement
     *
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * init the convert functions
     */
    protected $convertParams = [];

    /**
     * Possible popup parameter
     */
    public $allowedParams = [
        'height' => 'integer',
        'width' => 'integer',
        'resizable' => 'boolean',
        'scrollbars' => 'boolean',
        'menubar' => 'boolean',
        'status' => 'boolean',
        'location' => 'boolean',
        'toolbar' => 'boolean',
        'dependent' => 'boolean',
        'top' => 'integer',
        'left' => 'integer',
    ];

    /**
     * Possible popup parameter
     */
    public $advancedParams = [
        'once_per_session' => 'boolean',
        'once_per_link' => 'boolean',
        'center' => 'boolean',
        'maximize' => 'boolean',
        'popunder' => 'boolean',
    ];

    /**
     * Static Funktion for hooking the menu link generation
     *
     * @param    array|int $pageOrPageID The Page row or the page ID
     * @param    array $LD A reference of the Link Data
     */
    public static function makeMenuLink($pageOrPageID, &$LD)
    {
        $popup = GeneralUtility::makeInstance('FRUIT\\Popup\\Popup');
        if ($target = $popup->getPopupTarget($pageOrPageID)) {
            $LD['target'] = $target;
        }
    } # function - makeMenuLink


    /**
     * Get the target of a page
     *
     * @param    array|integer $pageOrPageID The Page row or the page ID
     *
     * @return string
     */
    public function getPopupTarget($pageOrPageID)
    {
        if (MathUtility::canBeInterpretedAsInteger($pageOrPageID)) {
            $pageOrPageID = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'pages',
                'uid=' . $pageOrPageID,
                '',
                '',
                1
            ));
        } # if

        if (!is_array($pageOrPageID) || isset($pageOrPageID['tx_popup_configuration']) == false) {
            return '';
        }

        $config = $pageOrPageID['tx_popup_configuration'];
        if (!strlen(trim($config))) {
            return '';
        }
        return $config;
    } # function - getPopupTarget


    /**
     * Return the default configuration
     *
     * @param      $page
     * @param bool $advanced
     *
     * @return    array    The default configuration
     */
    public function getDefaultConfiguration($page, $advanced = false)
    {
        $config = $this->loadTypoScript($page);

        if (!isset($config['allowedParams.'])) {
            return [];
        }
        $c = $config['allowedParams.'];
        if ($advanced && isset($config['advancedParams.'])) {
            $c = array_merge($c, $config['advancedParams.']);
        }

        return $c;
    } # function - getDefaultConfiguration


    /**
     * Text parser for internal popup links
     *
     * @author Tim Lochmueller
     * @author before 2009 Mathias Schreiber, Rene Fritz
     */
    public function textParse($content, $conf)
    {

        // ################
        // Default vars
        // Noch auslagern und f�r beides benutzen
        // #########################

        $JSpopup['link'] = $content['url'];
        $JSpopup['ATagParams'] = $content['ATagParams'];
        $JSpopup['windowname'] = 'Easy Popup';

        $JSpopup['properties']['dependent'] = 'yes';
        $JSpopup['properties.']['height'] = '300';
        $JSpopup['properties.']['left'] = '10';
        $JSpopup['properties.']['location'] = 'yes';
        $JSpopup['properties.']['menubar'] = 'no';
        $JSpopup['properties.']['resizable'] = 'yes';
        $JSpopup['properties.']['scrollbars'] = 'yes';
        $JSpopup['properties.']['status'] = 'no';
        $JSpopup['properties.']['toolbar'] = 'no';
        $JSpopup['properties.']['top'] = '10';
        $JSpopup['properties.']['width'] = '300';


        $linkContent = '';
        if ($conf['link'] or is_array($conf['link.'])) {
            $conf['link'] = $this->cObj->stdWrap($conf['link'], $conf['link.']);
            $conf['ATagParams'] = $this->cObj->stdWrap($conf['ATagParams'], $conf['ATagParams.']);
            $conf['windowname'] = $this->cObj->stdWrap($conf['windowname'], $conf['windowname.']);

            $JSpopup = $this->arrayMergeRecursiveOverrule($JSpopup, $conf, true);

            $linkContent = $this->cObj->stdWrap($conf['linkContent'], $conf['linkContent.']);
        } else {
            if (isset($this->cObj->parameters['slim'])) {
                $JSpopup['properties.']['location'] = 'no';
                $JSpopup['properties.']['menubar'] = 'no';
                $JSpopup['properties.']['status'] = 'no';
                $JSpopup['properties.']['toolbar'] = 'no';
            }
            if (isset($this->cObj->parameters['fixed'])) {
                $JSpopup['properties.']['resizable'] = 'no';
                $JSpopup['properties.']['scrollbars'] = 'no';
            }
            $JSpopup['properties.'] = $this->arrayMergeRecursiveOverrule(
                $JSpopup['properties.'],
                $this->cObj->parameters,
                true
            );
        }

        $temp = [];
        while (list($key, $val) = each($JSpopup['properties.'])) {
            $temp[] = $key . '=' . $val;
        }

        $props = implode(',', $temp);

        if (!$JSpopup['link']) {
            return '';
        }

        $TAG = '<a href="' . $JSpopup['link'] . '" onClick="openPic(this.href,\'' . str_replace(
            ' ',
            '',
            $JSpopup['windowname']
        ) . '\',\'' . $props . '\'); return false;" class="linkpop"' . $JSpopup['ATagParams'] . '>';
        if ($linkContent) {
            $TAG .= $linkContent . '</a>';
        }

        return $TAG;
    } # function - textParse


    /**
     *  Merges two arrays recursively, overruling the values of the first array
     *  in case of identical keys, ie. keeping the values of the second.
     */
    public function arrayMergeRecursiveOverrule($arr0, $arr1, $notAddKeys = 0)
    {
        reset($arr1);
        while (list($key, $val) = each($arr1)) {
            if (is_array($arr0[$key])) {
                if (is_array($arr1[$key])) {
                    ArrayUtility::mergeRecursiveWithOverrule($arr0[$key], $arr1[$key], $notAddKeys);
                }
            } else {
                if ($notAddKeys) {
                    if (isset($arr0[$key])) {
                        $arr0[$key] = $val;
                    }
                } else {
                    $arr0[$key] = $val;
                }
            }
        }
        reset($arr0);
        return $arr0;
    }


    /**
     * Load TypoScript for the backend
     * This is important for the single configuration concept in the popup extension
     */
    public function loadTypoScript($pid, $pluginExtKey = 'tx_popup_pi1')
    {

        $sysPageObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $rootLine = $sysPageObj->getRootLine($pid);
        $TSObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
        $TSObj->tt_track = 0;
        $TSObj->init();
        $TSObj->runThroughTemplates($rootLine);
        $TSObj->generateConfig();
        return $TSObj->setup['plugin.'][$pluginExtKey . '.'];
    }


    /**
     * Start the convertion prcess
     * Init this process mit the parameter for the convertion
     * all iligal parameter will be removed
     */
    public function convertInit($params)
    {
        $this->convertParams = $params;
    }


    /**
     * Convert a TYPO3 Popup configuration String to an Array
     *
     * @param    string $configString The T3 configuration string (Like "width:height:param1=value,param2=value2")
     * @param    boolean $advanced If set, the advanced parameter will be included in the array
     *
     * @return    array
     */
    public function convertCfg2Array($configString, $advanced = false)
    {
        $params = $this->allowedParams;
        if ($advanced) {
            $params = array_merge($params, $this->advancedParams);
        }

        $parts = explode(':', $configString);
        if (sizeof($parts) != 2) {
            return false;
        }
        $size = explode('x', $parts[0]);
        if (sizeof($size) != 2) {
            return false;
        }

        $config = $this->convertJs2Array('width=' . $size[0] . ',height=' . $size[1] . ',' . $parts[1], $params);
        return $this->setAllFields($params, $config);
    }


    /**
     * Convert JavaScript Params to an array
     *
     * @param string $string
     * @param string $params Javascript param String (Like "param1=value,param2=value2")
     *
     * @return    array
     */
    public function convertJs2Array($string, $params)
    {
        $config_pre = explode(',', $string);
        $config = [];
        foreach ($config_pre as $check) {
            $p = explode('=', $check);
            switch ($params[$p[0]]) {
                case 'integer':
                    $config[$p[0]] = intval($p[1]);
                    break;
                case 'boolean':
                    $config[$p[0]] = ($p[1] == "yes") ? true : false;
                    break;
            }
        }
        return $config;
    }


    /**
     * Convert an array to a TYPO3 Popup configuration String
     *
     * @param array $array Data Array of the field (key value pairs)
     * @param boolean $advanced If set, the advanced parameter will be included in the array
     *
     * @return string
     */
    public function convertArray2Cfg($array, $advanced = false)
    {
        $params = $this->allowedParams;
        if ($advanced) {
            $params = array_merge($params, $this->advancedParams);
        }

        $array = $this->setAllFields($params, $array);

        $cfg = '';
        if (isset($array['height']) && isset($array['width'])) {
            $cfg = $array['width'] . 'x' . $array['height'] . ':';
            unset($array['width']);
            unset($array['height']);
        }

        foreach ($array as $key => $value) {
            switch ($params[$key]) {
                case 'integer':
                    $cfg .= $key . '=' . intval($value);
                    break;
                case 'boolean':
                    $cfg .= $key . '=' . (($value) ? 'yes' : 'no');
                    break;
            } # switch
            $cfg .= ',';
        } # froeach

        return $cfg;
    } # function - convertArray2Cfg


    /**
     * Convert an array to a JS configuration String
     */
    public function convertArray2Js($array)
    {
        $params = $this->allowedParams;
        $array = $this->setAllFields($params, $array);

        $out = '';
        foreach ($array as $key => $value) {
            $out .= ($out != '') ? ',' : '';

            switch ($params[$key]) {
                case 'integer':
                    $out .= $key . '=' . intval($value);
                    break;
                case 'boolean':
                    $out .= $key . '=' . (($value) ? 'yes' : 'no');
                    break;
            } # switch
        } # foreach

        return $out;
    } # function - convertArray2Js


    /**
     * Convert a TYPO3 Popup configuration String to a JS configuration String
     */
    public function convertCfg2Js($string)
    {
        return $this->convertArray2Js($this->convertCfg2Array($string));
    } # function - convertCfg2Js


    /**
     * Convert a JS configuration string to a TYPO3 Popup configuration
     */
    public function convertJs2Cfg($string)
    {
        return $this->convertArray2Cfg($this->convertJs2Array($string, []));
    } # function - convertJs2Cfg


    /**
     * Set all fields in the given configuration by the param parameter
     */
    protected function setAllFields($params, $config)
    {
        $param = [];
        foreach (array_keys($params) as $p) {
            if (!in_array($p, array_keys($config))) {
                switch ($params[$p]) {
                    case 'integer':
                        $config[$p] = 0;
                        break;
                    case 'boolean':
                        $config[$p] = false;
                        break;
                } # switch
            } # if
        } # foreach
        return $config;
    } # function - setAllFields
}
