<?php
/**
 * Automatically install the Dailyquote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2010 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.1.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Import plugin functions */
require_once __DIR__ . '/functions.inc';
/** Import plugin database definition */
require_once __DIR__ . '/sql/mysql_install.php';

use DailyQuote\Config;
use glFusion\Log\Log;

/** Plugin installation options
 * @global array $INSTALL_plugin['dailyquote']
 */
$INSTALL_plugin['dailyquote'] = array(
    'installer' => array(
        'type'  => 'installer',
        'version' => '1',
        'mode'  => 'install',
    ),
    'plugin' => array(
        'type'  => 'plugin',
        'name'  => Config::PI_NAME,
        'ver'   => Config::get('pi_version'),
        'gl_ver' => Config::get('gl_version'),
        'url'   => Config::get('pi_url'),
        'display' => Config::get('pi_display_name'),
    ),
    array(
        'type'  => 'table',
        'table' => $_TABLES['dailyquote_quotes'],
        'sql'   => $_SQL['dailyquote_quotes'],
    ),
    array(
        'type'  => 'table',
        'table' => $_TABLES['dailyquote_cat'],
        'sql'   => $_SQL['dailyquote_cat'],
    ),
    array(
        'type'  => 'table',
        'table' => $_TABLES['dailyquote_quoteXcat'],
        'sql'   => $_SQL['dailyquote_quoteXcat'],
    ),

    array(
        'type'  => 'group',
        'group' => 'dailyquote Admin',
        'desc'  => 'Users in this group can administer the Daily Quote plugin',
        'variable' => 'admin_group_id',
        'admin' => true,
        'addroot' => true,
    ),
    array(
        'type'  => 'feature',
        'feature' => 'dailyquote.admin',
        'desc'  => 'Daily Quote Admin',
        'variable' => 'admin_feature_id',
    ),
    array(
        'type'  => 'feature',
        'feature' => 'dailyquote.edit',
        'desc'  => 'Daily Quote Editor',
        'variable' => 'edit_feature_id',
    ),
    array(
        'type'  => 'feature',
        'feature' => 'dailyquote.submit',
        'desc'  => 'Bypass Daily Quote Submission Queue',
        'variable' => 'submit_feature_id',
    ),

    array(
        'type'  => 'mapping',
        'group' => 'admin_group_id',
        'feature' => 'admin_feature_id',
        'log'   => 'Adding feature to the admin group',
    ),
    array(
        'type'  => 'mapping',
        'group' => 'admin_group_id',
        'feature' => 'edit_feature_id',
        'log'   => 'Adding feature to the admin group',
    ),
    array(
        'type'  => 'mapping',
        'findgroup' => 'Logged-in Users',
        'feature' => 'submit_feature_id',
        'log'   => 'Adding feature to Logged-In Users',
    ),
    array(
        'type'  => 'block',
        'name'  => 'dailyquote_random',
        'title' => $LANG_DQ['randomboxtitle'],
        'phpblockfn' => 'phpblock_dailyquote_random',
        'block_type' => 'phpblock',
        'group_id' => 'admin_group_id',
    ),
    array(
        'type'  => 'block',
        'name'  => 'dailyquote_dgmenu',
        'title' => Config::get('pi_display_name'),
        'phpblockfn' => 'phpblock_dailyquote_dqmenu',
        'block_type' => 'phpblock',
        'group_id' => 'admin_group_id',
    ),
    array(
        'type'  => 'sql',
        'sql'   => $_SQL['dq_cat_data'],
    ),
);


/**
 * Puts the datastructures for this plugin into the glFusion database.
 * Note: Corresponding uninstall routine is in functions.inc.
 *
 * @return  boolean True if successful False otherwise
 */
function plugin_install_dailyquote()
{
    global $INSTALL_plugin;

    $pi_name            = Config::PI_NAME;
    $pi_display_name    = Config::get('pi_display_name');
    $pi_version         = Config::get('pi_version');

    Log::write('system', Log::INFO, "Attempting to install the $pi_display_name plugin");

    $ret = INSTALLER_install($INSTALL_plugin[$pi_name]);
    if ($ret > 0) {
        return false;
    }

    return true;
}


/**
 * Loads the configuration records for the Online Config Manager.
 *
 * @return  boolean     true = proceed with install, false = an error occured
 */
function plugin_load_configuration_dailyquote()
{
    global $_CONF, $_TABLES;

    require_once __DIR__ . '/install_defaults.php';

    // Get the admin group ID that was saved previously.
    $group_id = (int)DB_getItem(
        $_TABLES['groups'],
        'grp_id',
        "grp_name='" . Config::PI_NAME . " Admin'"
    );
    return plugin_initconfig_dailyquote($group_id);
}


/**
 * Automatic uninstall function for plugins.
 * This code is automatically uninstalling the plugin.
 * It passes an array to the core code function that removes
 * tables, groups, features and php blocks from the tables.
 * Additionally, this code can perform special actions that cannot be
 * foreseen by the core code (interactions with other plugins for example).
 *
 * @deprecated
 * @return  array   Array of tables, blocks, groups, etc.
 */
function plugin_autouninstall_dailyquote_X()
{
    $out = array (
        /* give the name of the tables, without $_TABLES[] */
        'tables' => array('ad_ads'),
        /* give the full name of the group, as in the db */
        'groups' => array('dailyquote Admin'),
        /* give the full name of the feature, as in the db */
        'features' => array(
            'dailyquote.admin',
            'dailyquote.edit',
            'dailyquote.submit',
        ),
        /* give the full name of the block, including 'phpblock_', etc */
        'php_blocks' => array(),
        /* give all vars with their name */
        'vars'=> array()
    );
    return $out;
}
