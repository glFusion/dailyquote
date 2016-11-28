<?php
/**
*   Installation defaults for the Daily Quote plugin
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.3
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*   GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}

/**
 *  Daily Quote default settings
 *
 *  Initial Installation Defaults used when loading the online configuration
 *  records. These settings are only used during the initial installation
 *  and not referenced any more once the plugin is installed
 *  @global array $_DQ_DEFAULT
 *
 */
global $_DQ_DEFAULT, $_CONF_DQ;
$_DQ_DEFAULT = array(
    'indexdisplim' => 10,     // limit quotes shown on index page
    'searchdisplim' => 50,    // limit search results
    'queue' => 1,             // use submission queue? 1=yes, 0=no
    'anonadd' => 0,           // anon user can add? 1=yes, 0=no
    'loginadd' => 1,          // logged-in user can add quote?
    'cb_enable' => 0,         // centerblock enabled?
    'cb_pos' => 2,            // centerblock position (2=top)
    'cb_home' => '1',           // centerblock homepage only?
    'cb_replhome' => 0,         // centerblock replaces homepage?
    'google_link' => 1,       // add Google link to person quoted?
    'google_url' => 'http://www.google.com/search?hl=%s&q=%s',
    'whatsnew' => 0,          // show new quotes in whatsnew block?
    'whatsnewdays' => 14,     // number of days to be considered new
    'email_admin' => 1,       // email admin? 0=never, 1=if queue, 2=always
    'displayblocks' => 3,    // display both block colums by default
);


/**
 *  Initialize Daily Quote plugin configuration
 *
 *  Creates the database entries for the configuation if they don't already
 *  exist. Initial values will be taken from $_CONF_DQ if available (e.g. from
 *  an old config.php), uses $_DQ_DEFAULT otherwise.
 *
 *  @param  integer $group_id   Group ID to use as the plugin's admin group
 *  @return boolean             true: success; false: an error occurred
 */
function plugin_initconfig_dailyquote($group_id = 0)
{
    global $_CONF, $_CONF_DQ, $_DQ_DEFAULT;

    if (is_array($_CONF_DQ) && (count($_CONF_DQ) > 1)) {
        $_DQ_DEFAULT = array_merge($_DQ_DEFAULT, $_CONF_DQ);
    }

    $c = config::get_instance();

    if (!$c->group_exists($_CONF_DQ['pi_name'])) {

        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true, $_CONF_DQ['pi_name']);
        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true, $_CONF_DQ['pi_name']);
        $c->add('indexdisplim', $_DQ_DEFAULT['indexdisplim'],
                'text', 0, 0, 0, 10, true, $_CONF_DQ['pi_name']);
        $c->add('searchdisplim', $_DQ_DEFAULT['searchdisplim'], 
                'text', 0, 0, 0, 20, true, $_CONF_DQ['pi_name']);
        $c->add('queue', $_DQ_DEFAULT['queue'], 'select',
                0, 0, 0, 30, true, $_CONF_DQ['pi_name']);
        $c->add('anonadd', $_DQ_DEFAULT['anonadd'], 'select',
                0, 0, 0, 40, true, $_CONF_DQ['pi_name']);
        $c->add('loginadd', $_DQ_DEFAULT['loginadd'], 'select',
                0, 0, 0, 60, true, $_CONF_DQ['pi_name']);
        $c->add('google_link', $_DQ_DEFAULT['google_link'], 'select',
                0, 0, 0, 120, true, $_CONF_DQ['pi_name']);
        $c->add('google_url', $_DQ_DEFAULT['google_url'], 'text',
                0, 0, 0, 130, true, $_CONF_DQ['pi_name']);
        $c->add('whatsnew', $_DQ_DEFAULT['whatsnew'], 'select',
                0, 0, 0, 140, true, $_CONF_DQ['pi_name']);
        $c->add('whatsnewdays', $_DQ_DEFAULT['whatsnewdays'], 'text',
                0, 0, 0, 150, true, $_CONF_DQ['pi_name']);
        $c->add('email_admin', $_DQ_DEFAULT['email_admin'], 'select',
                0, 0, 9, 160, true, $_CONF_DQ['pi_name']);
        $c->add('displayblocks', $_DQ_DEFAULT['displayblocks'], 'select',
                0, 0, 13, 170, true, $_CONF_DQ['pi_name']);

        $c->add('fs_cblock', NULL, 'fieldset', 0, 2, NULL, 0, true, $_CONF_DQ['pi_name']);
        $c->add('cb_enable', $_DQ_DEFAULT['cb_enable'], 'select',
                0, 2, 0, 10, true, $_CONF_DQ['pi_name']);
        $c->add('cb_pos', $_DQ_DEFAULT['cb_pos'],
                'select', 0, 2, 5, 20, true, $_CONF_DQ['pi_name']);
        $c->add('cb_home', $_DQ_DEFAULT['cb_home'], 'select',
                0, 2, 0, 30, true, $_CONF_DQ['pi_name']);
        $c->add('cb_replhome', $_DQ_DEFAULT['cb_replhome'], 'select',
                0, 2, 0, 40, true, $_CONF_DQ['pi_name']);
    }

    return true;
}

?>
