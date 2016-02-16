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

require_once(t3lib_extMgm::extPath('popup').'class.tx_popup.php');
require_once(t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_tmenu.php');
require_once(t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_gmenu.php');
require_once(t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_imgmenu.php');
require_once(t3lib_extMgm::extPath('popup').'xclasses/class.ux_tslib_jsmenu.php');


class ux_tslib_menu extends tslib_menu {

	function menuTypoLink($page, $oTarget, $no_cache, $script, $overrideArray = '', $addParams = '', $typeOverride = '') {
		$LD = parent::menuTypoLink($page, $oTarget, $no_cache, $script, $overrideArray, $addParams, $typeOverride);
		tx_popup::makeMenuLink($page, $LD);
		return $LD;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/popup/xclasses/class.ux_tslib_menu.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/popup/xclasses/class.ux_tslib_menu.php']);
}
?>