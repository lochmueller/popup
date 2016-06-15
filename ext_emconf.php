<?php

########################################################################
# Extension Manager/Repository config file for ext "popup".
#
# Auto generated 09-08-2015 13:51
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Javascript Popup',
    'description' => 'Add the possibility for a easy configurable popup system for backend users and pages. Merge and modernize the old "Popup manager" (popup_manager), "KJ: Extended TYPO3 Links" (kj_extendedlinks), "TypoLinkPopUp" (cc_typolinkpopup) & "Pop-Up" (gsi_popup) Extensions and add lots of new features.',
    'category' => 'misc',
    'version' => '1.1.0',
    'module' => 'wizard',
    'state' => 'stable',
    'modify_tables' => 'pages',
    'author' => 'Tim Lochmueller',
    'author_email' => 'webmaster@fruit-lab.de',
    'constraints' => [
        'depends' => [
            'typo3' => '6.2.0-7.9.0',
        ],
    ],
];
