<?php
/**
*   English language file for the DailyQuote plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

$LANG_DQ= array(
'indexintro'        => 'La historia está llena de historias, diatribas, perspectivas, verdades, mentiras, hechos, detalles, opiniones, ordenanzas, etc. Las historias contadas por los hombres y mujeres que estuvieron allí, así como por los que no estuvieron, y sus comentarios son elementos. para exhibir en los archivos de la humanidad.',
);


// GL Interface Messages
$PLG_dailyquote_MESSAGE01 = 'Your quotation has been queued for administrator approval.';
$PLG_dailyquote_MESSAGE02 = 'Your quotation has been saved.';
$PLG_dailyquote_MESSAGE03 = 'Error retrieving current version number';
$PLG_dailyquote_MESSAGE04 = 'Error performing the plugin upgrade';
$PLG_dailyquote_MESSAGE05 = 'Error upgrading the plugin version number';
$PLG_dailyquote_MESSAGE06 = 'Plugin is already up to date';


// Localization of the Admin Configuration UI
$LANG_configsections['dailyquote'] = array(
    'label' => 'Daily Quotes',
    'title' => 'Daily Quote Configuration'
);

$LANG_configsubgroups['dailyquote'] = array(
    'sg_main' => 'Main Settings'
);

$LANG_fs['dailyquote'] = array(
    'fs_main' => 'General Settings',
    'fs_cblock' => 'CenterBlock settings',
    'fs_pblock' => 'PHP Block settings',
    'fs_rblock' => 'Regular Block settings',
    'fs_permissions' => 'Default Permissions',
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['dailyquote'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => TRUE, 'False' => FALSE),
    3 => array('Yes' => 1, 'No' => 0),
    4 => array('On' => 1, 'Off' => 0),
    5 => array('Top of Page' => 1, 'Below Featured Article' => 2, 'Bottom of Page' => 3),
    9 => array('Never' => 0, 'If Submission Queue' => 1, 'Always' => 2),
    10 => array('5' => 5, '10' => 10, '25' => 25, '50' => 50),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3),
    13 => array('None' => 0, 'Left' => 1, 'Right' => 2, 'Both' => 3),
);

$LANG_confignames['dailyquote'] = array(
    'indexdisplim' => 'Limit display on index page',
    'searchdisplim' => 'Limit search results to',
    'queue' => 'Use submission queue?',
    'anonadd' => 'Allow anonymous users to add quotes?',
    'loginadd' => 'Allow logged-in users to add quotes?',
    'loginaddcat' => 'Allow logged-in users to add categories?',
    'loginbatch' => 'Allow logged-in users to batch-add quotes?',
    'cb_enable' => 'Enable Centerblock?',
    'cb_pos' => 'Centerblock Position',
    'cb_home' => 'Centerblock on home page only?',
    'cb_replhome' => 'Centerblock replaces home page?',
    'google_link' => 'Add Google links to quotes?',
    'google_url' => 'Google URL',
    'whatsnew' => 'Show Quotes in "What\'s New" block?',
    'whatsnewdays' => 'Number of days for a quote to be considered "new"',
    'email_admin' => 'Notification Email to Admin?',
    'displayblocks'  => 'Display glFusion Blocks',
);


?>
