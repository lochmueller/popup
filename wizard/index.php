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

// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH . '../typo3/init.php');
require_once($BACK_PATH . 'template.php');
$LANG->includeLLFile('EXT:popup/wizard/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
require_once(t3lib_extMgm::extPath('popup') . 'class.tx_popup.php');
// DEFAULT initialization of a module [END]


/**
 * popup module tx_popup_wiz
 *
 * @author     Tim Lochmueller <tl@hdnet.de>
 * @package    TYPO3
 * @subpackage    tx_popup
 */
class tx_popup_wiz extends t3lib_SCbase
{


    /**
     * Main function of the module. Write the content to $this->content
     *
     */
    function main()
    {
        global $BE_USER, $LANG, $BACK_PATH;

        // popup Object
        $this->popup = t3lib_div::makeInstance('tx_popup');

        // Draw the header.
        $this->doc = t3lib_div::makeInstance('smallDoc');
        $this->doc->backPath = $BACK_PATH;
        $this->doc->form = '<form action="" method="post" name="wiz_form" style="margin: 5px;">';

        // JavaScript
        $this->doc->JScode = '
			<script language="javascript" type="text/javascript">
				function setElementValue(elName,elValue) {
					if (parent.window.opener && parent.window.opener.setFormValueFromBrowseWin)	{
						var checkbox = true;
						if(elValue == "") checkbox = false;
						parent.window.opener.document.forms["editform"].elements["data[pages][' . $_GET['P']['uid'] . ']["+elName+"]_hr"].value = elValue;
						parent.window.opener.document.forms["editform"].elements["data[pages][' . $_GET['P']['uid'] . ']["+elName+"]_cb"].checked = checkbox;
						parent.window.opener.document.forms["editform"].elements["data[pages][' . $_GET['P']['uid'] . ']["+elName+"]"].value = elValue;
						// setFormValueFromBrowseWin??
						parent.window.opener.focus();
						parent.close();
					} else {
						alert("Error - reference to main window is not set properly!");
						parent.window.opener.focus();
						parent.close();
					}
					return false;
				}
				
				function removePopup(){
					return setElementValue(\'' . $_GET['P']['field'] . '\',"");
				}
				
				function sendForm(){
					var value = "";
					
					value += document.forms["wiz_form"].elements["width"].value;
					value += "x";
					value += document.forms["wiz_form"].elements["height"].value;
					value += ":";
					
					value += "dependent="+document.forms["wiz_form"].elements["dependent"].value;
					
					value += ",";					
					value += "location="+document.forms["wiz_form"].elements["location"].value;
					
					value += ",";					
					value += "menubar="+document.forms["wiz_form"].elements["menubar"].value;
					
					value += ",";					
					value += "resizable="+document.forms["wiz_form"].elements["resizable"].value;
					
					value += ",";					
					value += "scrollbars="+document.forms["wiz_form"].elements["scrollbars"].value;
					
					value += ",";					
					value += "status="+document.forms["wiz_form"].elements["status"].value;
					
					value += ",";					
					value += "toolbar="+document.forms["wiz_form"].elements["toolbar"].value;
					
					value += ",";					
					value += "left="+document.forms["wiz_form"].elements["left"].value;
					
					value += ",";					
					value += "top="+document.forms["wiz_form"].elements["top"].value;
					
					if(document.forms["wiz_form"].elements["once_per_session"] != undefined) {
						value += ",";					
						value += "once_per_session="+document.forms["wiz_form"].elements["once_per_session"].value;
						
						value += ",";					
						value += "once_per_link="+document.forms["wiz_form"].elements["once_per_link"].value;
						
						value += ",";					
						value += "center="+document.forms["wiz_form"].elements["center"].value;
						
						value += ",";					
						value += "maximize="+document.forms["wiz_form"].elements["maximize"].value;
						
						value += ",";					
						value += "popunder="+document.forms["wiz_form"].elements["popunder"].value;
					}
										
					return setElementValue(\'' . $_GET['P']['field'] . '\',value);
				}
			</script>';


        $this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
        if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id)) {
            if ($BE_USER->user['admin'] && !$this->id) {
                $this->pageinfo = [
                    'title' => '[root-level]',
                    'uid' => 0,
                    'pid' => 0
                ];
            }

            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->header($LANG->getLL('title'));
            $this->content .= $this->doc->spacer(5);
            $this->content .= $this->doc->divider(5);

            // CSS
            $this->content .= '<style type="text/css"> table td { padding: 2px; width: 100px; } table td input, table td select { width: 90px; } </style>';

            // Render content:
            $this->moduleContent();
        }
    }

    /**
     * Output the content
     *
     */
    function printContent()
    {
        $this->content .= $this->doc->endPage();
        echo $this->content;
    }

    /**
     * Generate the content
     *
     */
    function moduleContent($content = '')
    {
        global $LANG;

        // Configuration
        $pageID = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($_GET['P']['uid']) ? $_GET['P']['uid'] : $_GET['P']['pid'];
        $advanced = (isset($_GET['advanced']) && intval($_GET['advanced']) === 1) ? true : false;
        $current = (isset($_GET['P']['currentValue']) && trim($_GET['P']['currentValue']) != '') ? $_GET['P']['currentValue'] : $this->popup->convertArray2Cfg($this->popup->getDefaultConfiguration($pageID,
            $advanced), $advanced);

        $config = $this->popup->convertCfg2Array($current, $advanced);

        // Basic configuration
        $content .= '<table>';
        foreach ($this->popup->allowedParams as $key => $value) {
            switch ($value) {
                case 'integer':
                    $content .= '<tr><td>' . $LANG->getLL($key) . '</td><td><input type="text" name="' . $key . '" value="' . intval($config[$key] ? $config[$key] : 0) . '" /></td></tr>';
                    break;
                case 'boolean':
                    $content .= '<tr><td>' . $LANG->getLL($key) . '</td><td><select name="' . $key . '"><option' . ($config[$key] ? ' selected="selected"' : '') . ' value="yes">' . $LANG->getLL('yes') . '</option><option' . (!$config[$key] ? ' selected="selected"' : '') . ' value="no">' . $LANG->getLL('no') . '</option></select></td></tr>';
                    break;
            } # switch
        } # foreach
        $this->content .= $this->doc->section($LANG->getLL('basic_configuration') . ':', $content . '</table>', 0, 1);

        // Advanced configuration
        if ($advanced) {
            $content = '<table>';

            foreach ($this->popup->advancedParams as $key => $value) {
                switch ($value) {
                    case 'integer':
                        $content .= '<tr><td>' . $LANG->getLL($key) . '</td><td><input type="text" name="' . $key . '" value="' . intval($config[$key] ? $config[$key] : 0) . '" /></td></tr>';
                        break;
                    case 'boolean':
                        $content .= '<tr><td>' . $LANG->getLL($key) . '</td><td><select name="' . $key . '"><option' . ($config[$key] ? ' selected="selected"' : '') . ' value="yes">' . $LANG->getLL('yes') . '</option><option' . (!$config[$key] ? ' selected="selected"' : '') . ' value="no">' . $LANG->getLL('no') . '</option></select></td></tr>';
                        break;
                } # switch
            } # foreach
            $this->content .= $this->doc->section($LANG->getLL('advanced_configuration') . ':', $content . '</table>',
                0, 1);
        } # if

        // Options
        $content = '<table><tr><td><input style="background-color: #ff9e9e;" type="button" value="remove Popup" onclick="return removePopup();" /></td><td><input style="background-color: #9eff9e;"  type="button" value="set Popup" onclick="return sendForm();" /></td></tr></table>';
        $this->content .= $this->doc->section($LANG->getLL('options') . ':', $content, 0, 1);
    }

} # class - tx_popup_wiz


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_popup_wiz');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>