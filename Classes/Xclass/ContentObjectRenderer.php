<?php
/**
 * ContentObjectRenderer
 */

namespace FRUIT\Popup\Xclass;

use FRUIT\Popup\Controller\PluginController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * ContentObjectRenderer
 */
class ContentObjectRenderer extends \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
{

    /**
     * Implements the "typolink" property of stdWrap (and others)
     * Basically the input string, $linktext, is (typically) wrapped in a <a>-tag linking to some page, email address, file or URL based on a parameter defined by the configuration array $conf.
     * This function is best used from internal functions as is. There are some API functions defined after this function which is more suited for general usage in external applications.
     * Generally the concept "typolink" should be used in your own applications as an API for making links to pages with parameters and more. The reason for this is that you will then automatically make links compatible with all the centralized functions for URL simulation and manipulation of parameters into hashes and more.
     * For many more details on the parameters and how they are interpreted, please see the link to TSref below.
     *
     * the FAL API is handled with the namespace/prefix "file:..."
     *
     * @param string $linktxt The string (text) to link
     * @param array  $conf    TypoScript configuration (see link below)
     *
     * @return string A link-wrapped string.
     * @see stdWrap(), \TYPO3\CMS\Frontend\Plugin\AbstractPlugin::pi_linkTP()
     */
    public function typoLink($linktxt, $conf)
    {
        $linktxt = (string)$linktxt;
        $tsfe = $this->getTypoScriptFrontendController();

        $LD = array();
        $finalTagParts = array();
        $finalTagParts['aTagParams'] = $this->getATagParams($conf);
        $linkParameter = trim(isset($conf['parameter.']) ? $this->stdWrap($conf['parameter'],
            $conf['parameter.']) : $conf['parameter']);
        $this->lastTypoLinkUrl = '';
        $this->lastTypoLinkTarget = '';

        $resolvedLinkParameters = $this->resolveMixedLinkParameter($linktxt, $linkParameter, $conf);
        // check if the link handler hook has resolved the link completely already
        if (!is_array($resolvedLinkParameters)) {
            return $resolvedLinkParameters;
        }

        $linkParameter = $resolvedLinkParameters['href'];
        $forceTarget = $resolvedLinkParameters['target'];
        $linkClass = $resolvedLinkParameters['class'];
        $forceTitle = $resolvedLinkParameters['title'];

        if (!$linkParameter) {
            return $linktxt;
        }

        // Check, if the target is coded as a JS open window link:
        $JSwindowParts = array();
        $JSwindowParams = '';
        if ($forceTarget && preg_match('/^([0-9]+)x([0-9]+)(:(.*)|.*)$/', $forceTarget, $JSwindowParts)) {
            // Take all pre-configured and inserted parameters and compile parameter list, including width+height:
            $JSwindow_tempParamsArr = GeneralUtility::trimExplode(',',
                strtolower($conf['JSwindow_params'] . ',' . $JSwindowParts[4]), true);
            $JSwindow_paramsArr = array();
            foreach ($JSwindow_tempParamsArr as $JSv) {
                list($JSp, $JSv) = explode('=', $JSv, 2);
                $JSwindow_paramsArr[$JSp] = $JSp . '=' . $JSv;
            }
            // Add width/height:
            $JSwindow_paramsArr['width'] = 'width=' . $JSwindowParts[1];
            $JSwindow_paramsArr['height'] = 'height=' . $JSwindowParts[2];
            // Imploding into string:
            $JSwindowParams = implode(',', $JSwindow_paramsArr);
            // Resetting the target since we will use onClick.
            $forceTarget = '';
        }

        // Internal target:
        if ($tsfe->dtdAllowsFrames) {
            $target = isset($conf['target']) ? $conf['target'] : $tsfe->intTarget;
        } else {
            $target = isset($conf['target']) ? $conf['target'] : '';
        }
        if ($conf['target.']) {
            $target = $this->stdWrap($target, $conf['target.']);
        }

        // Title tag
        $title = $conf['title'];
        if ($conf['title.']) {
            $title = $this->stdWrap($title, $conf['title.']);
        }

        $theTypeP = 0;
        // Detecting kind of link
        $linkType = $this->detectLinkTypeFromLinkParameter($linkParameter);
        switch ($linkType) {
            // If it's a mail address
            case 'mailto':
                $linkParameter = preg_replace('/^mailto:/i', '', $linkParameter);
                list($this->lastTypoLinkUrl, $linktxt) = $this->getMailTo($linkParameter, $linktxt);
                $finalTagParts['url'] = $this->lastTypoLinkUrl;
                break;

            // url (external): If doubleSlash or if a '.' comes before a '/'.
            case 'url':
                if ($tsfe->dtdAllowsFrames) {
                    $target = isset($conf['extTarget']) ? $conf['extTarget'] : $tsfe->extTarget;
                } else {
                    $target = isset($conf['extTarget']) ? $conf['extTarget'] : '';
                }
                if ($conf['extTarget.']) {
                    $target = $this->stdWrap($target, $conf['extTarget.']);
                }
                if ($forceTarget) {
                    $target = $forceTarget;
                }
                if ($linktxt === '') {
                    $linktxt = $this->parseFunc($linkParameter, array('makelinks' => 0), '< lib.parseFunc');
                }
                // Parse URL:
                $urlParts = parse_url($linkParameter);
                if (!$urlParts['scheme']) {
                    $scheme = 'http://';
                } else {
                    $scheme = '';
                }

                $this->lastTypoLinkUrl = $this->processUrl(UrlProcessorInterface::CONTEXT_EXTERNAL, $scheme . $linkParameter,
                    $conf);

                $this->lastTypoLinkTarget = $target;
                $finalTagParts['url'] = $this->lastTypoLinkUrl;
                $finalTagParts['targetParams'] = $target ? ' target="' . $target . '"' : '';
                $finalTagParts['aTagParams'] .= $this->extLinkATagParams($finalTagParts['url'], $linkType);
                break;

            // file (internal)
            case 'file':

                $splitLinkParam = explode('?', $linkParameter);

                // check if the file exists or if a / is contained (same check as in detectLinkType)
                if (file_exists(rawurldecode($splitLinkParam[0])) || strpos($linkParameter, '/') !== false) {
                    if ($linktxt === '') {
                        $linktxt = $this->parseFunc(rawurldecode($linkParameter), array('makelinks' => 0), '< lib.parseFunc');
                    }
                    $this->lastTypoLinkUrl = $this->processUrl(UrlProcessorInterface::CONTEXT_FILE,
                        $GLOBALS['TSFE']->absRefPrefix . $linkParameter, $conf);
                    $this->lastTypoLinkUrl = $this->forceAbsoluteUrl($this->lastTypoLinkUrl, $conf);
                    $target = isset($conf['fileTarget']) ? $conf['fileTarget'] : $tsfe->fileTarget;
                    if ($conf['fileTarget.']) {
                        $target = $this->stdWrap($target, $conf['fileTarget.']);
                    }
                    if ($forceTarget) {
                        $target = $forceTarget;
                    }
                    $this->lastTypoLinkTarget = $target;
                    $finalTagParts['url'] = $this->lastTypoLinkUrl;
                    $finalTagParts['targetParams'] = $target ? ' target="' . $target . '"' : '';
                    $finalTagParts['aTagParams'] .= $this->extLinkATagParams($finalTagParts['url'], $linkType);
                } else {
                    $this->getTimeTracker()
                        ->setTSlogMessage('typolink(): File "' . $splitLinkParam[0] . '" did not exist, so "' . $linktxt . '" was not linked.',
                            1);
                    return $linktxt;
                }
                break;

            // Integer or alias (alias is without slashes or periods or commas, that is
            // 'nospace,alphanum_x,lower,unique' according to definition in $GLOBALS['TCA']!)
            case 'page':
                $enableLinksAcrossDomains = $tsfe->config['config']['typolinkEnableLinksAcrossDomains'];

                if ($conf['no_cache.']) {
                    $conf['no_cache'] = $this->stdWrap($conf['no_cache'], $conf['no_cache.']);
                }
                // Splitting the parameter by ',' and if the array counts more than 1 element it's an id/type/parameters triplet
                $pairParts = GeneralUtility::trimExplode(',', $linkParameter, true);
                $linkParameter = $pairParts[0];
                $link_params_parts = explode('#', $linkParameter);
                // Link-data del
                $linkParameter = trim($link_params_parts[0]);
                // If no id or alias is given
                if ($linkParameter === '') {
                    $linkParameter = $tsfe->id;
                }

                $sectionMark = trim(isset($conf['section.']) ? $this->stdWrap($conf['section'],
                    $conf['section.']) : $conf['section']);
                if ($sectionMark !== '') {
                    $sectionMark = '#' . (MathUtility::canBeInterpretedAsInteger($sectionMark) ? 'c' : '') . $sectionMark;
                }

                if ($link_params_parts[1] && $sectionMark === '') {
                    $sectionMark = trim($link_params_parts[1]);
                    $sectionMark = '#' . (MathUtility::canBeInterpretedAsInteger($sectionMark) ? 'c' : '') . $sectionMark;
                }
                if (count($pairParts) > 1) {
                    // Overruling 'type'
                    $theTypeP = isset($pairParts[1]) ? $pairParts[1] : 0;
                    $conf['additionalParams'] .= isset($pairParts[2]) ? $pairParts[2] : '';
                }
                // Checking if the id-parameter is an alias.
                if (!MathUtility::canBeInterpretedAsInteger($linkParameter)) {
                    $linkParameter = $tsfe->sys_page->getPageIdFromAlias($linkParameter);
                }
                // Link to page even if access is missing?
                if (isset($conf['linkAccessRestrictedPages'])) {
                    $disableGroupAccessCheck = (bool)$conf['linkAccessRestrictedPages'];
                } else {
                    $disableGroupAccessCheck = (bool)$tsfe->config['config']['typolinkLinkAccessRestrictedPages'];
                }
                // Looking up the page record to verify its existence:
                $page = $tsfe->sys_page->getPage($linkParameter, $disableGroupAccessCheck);
                if (!empty($page)) {
                    // MointPoints, look for closest MPvar:
                    $MPvarAcc = array();
                    if (!$tsfe->config['config']['MP_disableTypolinkClosestMPvalue']) {
                        $temp_MP = $this->getClosestMPvalueForPage($page['uid'], true);
                        if ($temp_MP) {
                            $MPvarAcc['closest'] = $temp_MP;
                        }
                    }
                    // Look for overlay Mount Point:
                    $mount_info = $tsfe->sys_page->getMountPointInfo($page['uid'], $page);
                    if (is_array($mount_info) && $mount_info['overlay']) {
                        $page = $tsfe->sys_page->getPage($mount_info['mount_pid'], $disableGroupAccessCheck);
                        if (empty($page)) {
                            $this->getTimeTracker()
                                ->setTSlogMessage('typolink(): Mount point "' . $mount_info['mount_pid'] . '" was not available, so "' . $linktxt . '" was not linked.',
                                    1);
                            return $linktxt;
                        }
                        $MPvarAcc['re-map'] = $mount_info['MPvar'];
                    }


                    // -----------------------
                    // Popup Hook
                    // -----------------------
                    $plugin = new PluginController();
                    $popup = $plugin->pi_getRecord('pages', $page['uid']);
                    $popup_configuration = $popup['tx_popup_configuration'];


                    // Setting title if blank value to link:
                    if ($linktxt === '') {
                        $linktxt = $this->parseFunc($page['title'], array('makelinks' => 0), '< lib.parseFunc');
                    }
                    // Query Params:
                    $addQueryParams = $conf['addQueryString'] ? $this->getQueryArguments($conf['addQueryString.']) : '';
                    $addQueryParams .= isset($conf['additionalParams.']) ? trim($this->stdWrap($conf['additionalParams'],
                        $conf['additionalParams.'])) : trim($conf['additionalParams']);
                    if ($addQueryParams === '&' || $addQueryParams[0] !== '&') {
                        $addQueryParams = '';
                    }
                    if ($conf['useCacheHash']) {
                        // Mind the order below! See http://forge.typo3.org/issues/17070
                        $params = $tsfe->linkVars . $addQueryParams;
                        if (trim($params, '& ') != '') {
                            /** @var $cacheHash CacheHashCalculator */
                            $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class);
                            $cHash = $cacheHash->generateForParameters($params);
                            $addQueryParams .= $cHash ? '&cHash=' . $cHash : '';
                        }
                        unset($params);
                    }
                    $targetDomain = '';
                    $currentDomain = (string)$this->getEnvironmentVariable('HTTP_HOST');
                    // Mount pages are always local and never link to another domain
                    if (!empty($MPvarAcc)) {
                        // Add "&MP" var:
                        $addQueryParams .= '&MP=' . rawurlencode(implode(',', $MPvarAcc));
                    } elseif (strpos($addQueryParams, '&MP=') === false && $tsfe->config['config']['typolinkCheckRootline']) {
                        // We do not come here if additionalParams had '&MP='. This happens when typoLink is called from
                        // menu. Mount points always work in the content of the current domain and we must not change
                        // domain if MP variables exist.
                        // If we link across domains and page is free type shortcut, we must resolve the shortcut first!
                        // If we do not do it, TYPO3 will fail to (1) link proper page in RealURL/CoolURI because
                        // they return relative links and (2) show proper page if no RealURL/CoolURI exists when link is clicked
                        if ($enableLinksAcrossDomains && (int)$page['doktype'] === PageRepository::DOKTYPE_SHORTCUT && (int)$page['shortcut_mode'] === PageRepository::SHORTCUT_MODE_NONE) {
                            // Save in case of broken destination or endless loop
                            $page2 = $page;
                            // Same as in RealURL, seems enough
                            $maxLoopCount = 20;
                            while ($maxLoopCount && is_array($page) && (int)$page['doktype'] === PageRepository::DOKTYPE_SHORTCUT && (int)$page['shortcut_mode'] === PageRepository::SHORTCUT_MODE_NONE) {
                                $page = $tsfe->sys_page->getPage($page['shortcut'], $disableGroupAccessCheck);
                                $maxLoopCount--;
                            }
                            if (empty($page) || $maxLoopCount === 0) {
                                // We revert if shortcut is broken or maximum number of loops is exceeded (indicates endless loop)
                                $page = $page2;
                            }
                        }

                        $targetDomain = $tsfe->getDomainNameForPid($page['uid']);
                        // Do not prepend the domain if it is the current hostname
                        if (!$targetDomain || $tsfe->domainNameMatchesCurrentRequest($targetDomain)) {
                            $targetDomain = '';
                        }
                    }
                    $absoluteUrlScheme = 'http';
                    // URL shall be absolute:
                    if (isset($conf['forceAbsoluteUrl']) && $conf['forceAbsoluteUrl'] || $page['url_scheme'] > 0) {
                        // Override scheme:
                        if (isset($conf['forceAbsoluteUrl.']['scheme']) && $conf['forceAbsoluteUrl.']['scheme']) {
                            $absoluteUrlScheme = $conf['forceAbsoluteUrl.']['scheme'];
                        } elseif ($page['url_scheme'] > 0) {
                            $absoluteUrlScheme = (int)$page['url_scheme'] === HttpUtility::SCHEME_HTTP ? 'http' : 'https';
                        } elseif ($this->getEnvironmentVariable('TYPO3_SSL')) {
                            $absoluteUrlScheme = 'https';
                        }
                        // If no domain records are defined, use current domain:
                        $currentUrlScheme = parse_url($this->getEnvironmentVariable('TYPO3_REQUEST_URL'), PHP_URL_SCHEME);
                        if ($targetDomain === '' && ($conf['forceAbsoluteUrl'] || $absoluteUrlScheme !== $currentUrlScheme)) {
                            $targetDomain = $currentDomain;
                        }
                        // If go for an absolute link, add site path if it's not taken care about by absRefPrefix
                        if (!$tsfe->config['config']['absRefPrefix'] && $targetDomain === $currentDomain) {
                            $targetDomain = $currentDomain . rtrim($this->getEnvironmentVariable('TYPO3_SITE_PATH'), '/');
                        }
                    }
                    // If target page has a different domain and the current domain's linking scheme (e.g. RealURL/...) should not be used
                    if ($targetDomain !== '' && $targetDomain !== $currentDomain && !$enableLinksAcrossDomains) {
                        $target = isset($conf['extTarget']) ? $conf['extTarget'] : $tsfe->extTarget;
                        if ($conf['extTarget.']) {
                            $target = $this->stdWrap($target, $conf['extTarget.']);
                        }
                        if ($forceTarget) {
                            $target = $forceTarget;
                        }
                        $LD['target'] = $target;
                        // Convert IDNA-like domain (if any)
                        if (!preg_match('/^[a-z0-9.\\-]*$/i', $targetDomain)) {
                            $targetDomain = GeneralUtility::idnaEncode($targetDomain);
                        }
                        $this->lastTypoLinkUrl = $this->URLqMark($absoluteUrlScheme . '://' . $targetDomain . '/index.php?id=' . $page['uid'],
                                $addQueryParams) . $sectionMark;
                    } else {
                        // Internal link or current domain's linking scheme should be used
                        if ($forceTarget) {
                            $target = $forceTarget;
                        }
                        $LD = $tsfe->tmpl->linkData($page, $target, $conf['no_cache'], '', '', $addQueryParams, $theTypeP,
                            $targetDomain);
                        if ($targetDomain !== '') {
                            // We will add domain only if URL does not have it already.
                            if ($enableLinksAcrossDomains && $targetDomain !== $currentDomain) {
                                // Get rid of the absRefPrefix if necessary. absRefPrefix is applicable only
                                // to the current web site. If we have domain here it means we link across
                                // domains. absRefPrefix can contain domain name, which will screw up
                                // the link to the external domain.
                                $prefixLength = strlen($tsfe->config['config']['absRefPrefix']);
                                if (substr($LD['totalURL'], 0, $prefixLength) === $tsfe->config['config']['absRefPrefix']) {
                                    $LD['totalURL'] = substr($LD['totalURL'], $prefixLength);
                                }
                            }
                            $urlParts = parse_url($LD['totalURL']);
                            if (empty($urlParts['host'])) {
                                $LD['totalURL'] = $absoluteUrlScheme . '://' . $targetDomain . ($LD['totalURL'][0] === '/' ? '' : '/') . $LD['totalURL'];
                            }
                        }
                        $this->lastTypoLinkUrl = $this->URLqMark($LD['totalURL'], '') . $sectionMark;
                    }
                    $this->lastTypoLinkTarget = $LD['target'];
                    // If sectionMark is set, there is no baseURL AND the current page is the page the link is to, check if there are any additional parameters or addQueryString parameters and if not, drop the url.
                    if ($sectionMark && !$tsfe->config['config']['baseURL'] && (int)$page['uid'] === (int)$tsfe->id && !trim($addQueryParams) && (empty($conf['addQueryString']) || !isset($conf['addQueryString.']))) {
                        $currentQueryParams = $this->getQueryArguments(array());
                        if (!trim($currentQueryParams)) {
                            list(, $URLparams) = explode('?', $this->lastTypoLinkUrl);
                            list($URLparams) = explode('#', $URLparams);
                            parse_str($URLparams . $LD['orig_type'], $URLparamsArray);
                            // Type nums must match as well as page ids
                            if ((int)$URLparamsArray['type'] === (int)$tsfe->type) {
                                unset($URLparamsArray['id']);
                                unset($URLparamsArray['type']);
                                // If there are no parameters left.... set the new url.
                                if (empty($URLparamsArray)) {
                                    $this->lastTypoLinkUrl = $sectionMark;
                                }
                            }
                        }
                    }
                    // If link is to an access restricted page which should be redirected, then find new URL:
                    if (empty($conf['linkAccessRestrictedPages']) && $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] && $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] !== 'NONE' && !$tsfe->checkPageGroupAccess($page)) {
                        $thePage = $tsfe->sys_page->getPage($tsfe->config['config']['typolinkLinkAccessRestrictedPages']);
                        $addParams = str_replace(array(
                            '###RETURN_URL###',
                            '###PAGE_ID###'
                        ), array(
                            rawurlencode($this->lastTypoLinkUrl),
                            $page['uid']
                        ), $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams']);
                        $this->lastTypoLinkUrl = $this->getTypoLink_URL($thePage['uid'] . ($theTypeP ? ',' . $theTypeP : ''),
                            $addParams, $target);
                        $this->lastTypoLinkUrl = $this->forceAbsoluteUrl($this->lastTypoLinkUrl, $conf);
                        $this->lastTypoLinkLD['totalUrl'] = $this->lastTypoLinkUrl;
                        $LD = $this->lastTypoLinkLD;
                    }
                    // Rendering the tag.
                    $finalTagParts['url'] = $this->lastTypoLinkUrl;
                    $finalTagParts['targetParams'] = (string)$LD['target'] !== '' ? ' target="' . htmlspecialchars($LD['target']) . '"' : '';
                } else {
                    $this->getTimeTracker()
                        ->setTSlogMessage('typolink(): Page id "' . $linkParameter . '" was not found, so "' . $linktxt . '" was not linked.',
                            1);
                    return $linktxt;
                }
                break;
        }

        $finalTagParts['TYPE'] = $linkType;
        $this->lastTypoLinkLD = $LD;

        if ($forceTitle) {
            $title = $forceTitle;
        }

        if ($JSwindowParams) {
            // Create TARGET-attribute only if the right doctype is used
            $xhtmlDocType = $tsfe->xhtmlDoctype;
            if ($xhtmlDocType !== 'xhtml_strict' && $xhtmlDocType !== 'xhtml_11' && $xhtmlDocType !== 'xhtml_2') {
                $target = ' target="FEopenLink"';
            } else {
                $target = '';
            }
            $onClick = 'vHWin=window.open(' . GeneralUtility::quoteJSvalue($tsfe->baseUrlWrap($finalTagParts['url'])) . ',\'FEopenLink\',' . GeneralUtility::quoteJSvalue($JSwindowParams) . ');vHWin.focus();return false;';
            $finalAnchorTag = '<a href="' . htmlspecialchars($finalTagParts['url']) . '"' . $target . ' onclick="' . htmlspecialchars($onClick) . '"' . ((string)$title !== '' ? ' title="' . htmlspecialchars($title) . '"' : '') . ($linkClass !== '' ? ' class="' . $linkClass . '"' : '') . $finalTagParts['aTagParams'] . '>';
        } else {
            if ($tsfe->spamProtectEmailAddresses === 'ascii' && $linkType === 'mailto') {
                $finalAnchorTag = '<a href="' . $finalTagParts['url'] . '"';
            } else {
                $finalAnchorTag = '<a href="' . htmlspecialchars($finalTagParts['url']) . '"';


                // -----------------------
                // Popup Hook
                // -----------------------
                if (isset($popup_configuration) && strlen($popup_configuration)) {
                    $popup = GeneralUtility::makeInstance('FRUIT\\Popup\\Popup');
                    $popup_configuration = $popup->convertCfg2Js($popup_configuration);
                    $finalTagParts['aTagParams'] .= ' onclick="window.open(this.href,\'\',\'' . $popup_configuration . '\'); return false;"';
                }

            }
            $finalAnchorTag .= ((string)$title !== '' ? ' title="' . htmlspecialchars($title) . '"' : '') . $finalTagParts['targetParams'] . ($linkClass ? ' class="' . $linkClass . '"' : '') . $finalTagParts['aTagParams'] . '>';
        }

        // Call user function:
        if ($conf['userFunc']) {
            $finalTagParts['TAG'] = $finalAnchorTag;
            $finalAnchorTag = $this->callUserFunction($conf['userFunc'], $conf['userFunc.'], $finalTagParts);
        }

        // Hook: Call post processing function for link rendering:
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc'])) {
            $_params = array(
                'conf'          => &$conf,
                'linktxt'       => &$linktxt,
                'finalTag'      => &$finalAnchorTag,
                'finalTagParts' => &$finalTagParts
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        // If flag "returnLastTypoLinkUrl" set, then just return the latest URL made:
        if ($conf['returnLast']) {
            switch ($conf['returnLast']) {
                case 'url':
                    return $this->lastTypoLinkUrl;
                    break;
                case 'target':
                    return $this->lastTypoLinkTarget;
                    break;
            }
        }

        $wrap = isset($conf['wrap.']) ? $this->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];

        if ($conf['ATagBeforeWrap']) {
            return $finalAnchorTag . $this->wrap($linktxt, $wrap) . '</a>';
        }
        return $this->wrap($finalAnchorTag . $linktxt . '</a>', $wrap);
    }

}
