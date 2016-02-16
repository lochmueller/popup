<?php
/**
 * @todo       General file information
 *
 * @category   Extension
 * @package    ...
 * @author     Tim Lochmüller <tim@fruit-lab.de>
 */


/**
 * @todo       General class information
 *
 * @package    ...
 * @subpackage ...
 * @author     Tim Lochmüller <tim@fruit-lab.de>
 */

class Tx_Popup_Utility {

	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	static public function checkInt($var) {
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(t3lib_utility_VersionNumber::getNumericTypo3Version()) > 4007000) {
			return t3lib_utility_Math::canBeInterpretedAsInteger($var);
		}
		return t3lib_div::testInt($var);
	}

}