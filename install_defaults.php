<?php
//  $Id$
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
    'indexdisplim' => '10',     // limit quotes shown on index page
    'searchdisplim' => '50',    // limit search results
    'queue' => '1',             // use submission queue? 1=yes, 0=no
    'anonadd' => '0',           // anon user can add? 1=yes, 0=no
    'anonview' => '1',          // anon user can view?
    'loginadd' => '1',          // logged-in user can add quote?
    'loginaddcat' => '1',       // logged-in user can add category?
    'loginbatch' => '0',        // logged-in user can batch add quotes?
    'cb_enable' => '0',         // centerblock enabled?
    'cb_pos' => '2',            // centerblock position (2=top)
    'cb_home' => '1',           // centerblock home page only?
    'google_link' => '1',       // add Google link to person quoted?
    'whatsnew' => '0',          // show new quotes in whatsnew block?
    'whatsnewdays' => '14',     // number of days to be considered new
    'default_permissions' => array (3, 2, 2, 2),
    'email_admin' => '2',       // email admin? 0=never, 1=if queue, 2=always
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

    // Use configured default if a valid group ID wasn't presented
    if ($group_id == 0)
        $group_id = $_DQ_DEFAULT['defgrpad'];

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
        $c->add('anonview', $_DQ_DEFAULT['anonview'], 'select',
                0, 0, 0, 50, true, $_CONF_DQ['pi_name']);
        $c->add('loginadd', $_DQ_DEFAULT['loginadd'], 'select',
                0, 0, 0, 60, true, $_CONF_DQ['pi_name']);
        $c->add('loginaddcat', $_DQ_DEFAULT['loginaddcat'], 'select',
                0, 0, 0, 70, true, $_CONF_DQ['pi_name']);
        $c->add('loginbatch', $_DQ_DEFAULT['loginbatch'], 'select',
                0, 0, 0, 80, true, $_CONF_DQ['pi_name']);
        $c->add('cb_enable', $_DQ_DEFAULT['cb_enable'], 'select',
                0, 0, 0, 90, true, $_CONF_DQ['pi_name']);
        $c->add('cb_pos', $_DQ_DEFAULT['cb_pos'],
                'select', 0, 0, 5, 100, true, $_CONF_DQ['pi_name']);
        $c->add('cb_home', $_DQ_DEFAULT['cb_home'], 'select',
                0, 0, 0, 110, true, $_CONF_DQ['pi_name']);
        $c->add('gglink', $_DQ_DEFAULT['gglink'], 'select',
                0, 0, 0, 120, true, $_CONF_DQ['pi_name']);
        $c->add('whatsnew', $_DQ_DEFAULT['whatsnew'], 'select',
                0, 0, 0, 130, true, $_CONF_DQ['pi_name']);
        $c->add('email_admin', $_DQ_DEFAULT['email_admin'], 'select',
                0, 0, 0, 140, true, $_CONF_DQ['pi_name']);


        $c->add('fs_permissions', NULL, 'fieldset', 0, 4, NULL, 0, true, $_CONF_DQ['pi_name']);
        $c->add('defgrpad', $group_id,
                'select', 0, 4, 0, 90, true, $_CONF_DQ['pi_name']);
        $c->add('default_permissions', $_DQ_DEFAULT['default_permissions'],
                '@select', 0, 4, 12, 100, true, $_CONF_DQ['pi_name']);

    }

    return true;
}

?>
