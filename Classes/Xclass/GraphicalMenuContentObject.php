<?php
/**
 * GraphicalMenuContentObject
 */

namespace FRUIT\Popup\Xclass;

use FRUIT\Popup\Popup;

/**
 * GraphicalMenuContentObject
 */
class GraphicalMenuContentObject extends \TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject
{
    /**
     * Calls typolink to create menu item links.
     *
     * @param array $page Page record (uid points where to link to)
     * @param string $oTarget Target frame/window
     * @param bool $no_cache TRUE if caching should be disabled
     * @param string $script Alternative script name (unused)
     * @param array|string $overrideArray Array to override values in $page, empty string to skip override
     * @param string $addParams Parameters to add to URL
     * @param int|string $typeOverride "type" value, empty string means "not set"
     * @return array See linkData
     */
    public function menuTypoLink($page, $oTarget, $no_cache, $script, $overrideArray = '', $addParams = '', $typeOverride = '')
    {
        $LD = parent::menuTypoLink($page, $oTarget, $no_cache, $script, $overrideArray, $addParams, $typeOverride);
        Popup::makeMenuLink($page, $LD);
        return $LD;
    }
}
