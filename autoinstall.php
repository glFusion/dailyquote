<?php
//  $Id$
/**
*   Automatically install the Dailyquote plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_dbms;

/** Import plugin functions */
require_once $_CONF['path'].'plugins/dailyquote/functions.inc';
/** Import plugin database definition */
require_once $_CONF['path'].'plugins/dailyquote/sql/'. $_DB_dbms. '_install.php';

/** Plugin installation options
*   @global array $INSTALL_plugin['dailyquote']
*/
$INSTALL_plugin['dailyquote'] = array(
    'installer' => array('type' => 'installer', 
            'version' => '1', 
            'mode' => 'install'),

    'plugin' => array('type' => 'plugin', 
            'name' => $_CONF_DQ['pi_name'],
            'ver' => $_CONF_DQ['pi_version'], 
            'gl_ver' => $_CONF_DQ['gl_version'],
            'url' => $_CONF_DQ['pi_url'], 
            'display' => $_CONF_DQ['pi_display_name']),

    array('type' => 'table', 
            'table' => $_TABLES['dailyquote_quotes'], 
            'sql' => $_SQL['dailyquote_quotes']),

    array('type' => 'table', 
            'table' => $_TABLES['dailyquote_submission'], 
            'sql' => $_SQL['dailyquote_submission']),

    array('type' => 'table', 
            'table' => $_TABLES['dailyquote_cat'], 
            'sql' => $_SQL['dailyquote_cat']),

    array('type' => 'table', 
            'table' => $_TABLES['dailyquote_quoteXcat'], 
            'sql' => $_SQL['dailyquote_quoteXcat']),

    array('type' => 'group', 
            'group' => 'dailyquote Admin', 
            'desc' => 'Users in this group can administer the Daily Quote plugin',
            'variable' => 'admin_group_id', 
            'admin' => true,
            'addroot' => true),

    array('type' => 'feature', 
            'feature' => 'dailyquote.admin', 
            'desc' => 'Daily Quote Admin',
            'variable' => 'admin_feature_id'),

    array('type' => 'feature', 
            'feature' => 'dailyquote.edit', 
            'desc' => 'Daily Quote Editor',
            'variable' => 'edit_feature_id'),

    array('type' => 'feature', 
            'feature' => 'dailyquote.submit', 
            'desc' => 'Bypass Daily Quote Submission Queue',
            'variable' => 'submit_feature_id'),

    array('type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'admin_feature_id',
            'log' => 'Adding feature to the admin group'),

    array('type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'edit_feature_id',
            'log' => 'Adding feature to the admin group'),

    array('type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'submit_feature_id',
            'log' => 'Adding feature to the admin group'),

    array('type' => 'block', 
            'name' => 'dailyquote_random', 
            'title' => $LANG_DQ['randomboxtitle'],
            'phpblockfn' => 'phpblock_dailyquote_random', 
            'block_type' => 'phpblock',
            'group_id' => 'admin_group_id'),

    array('type' => 'block', 
            'name' => 'dailyquote_dgmenu', 
            'title' => $_CONF_DQ['pi_display_name'],
            'phpblockfn' => 'phpblock_dailyquote_dqmenu', 
            'block_type' => 'phpblock',
            'group_id' => 'admin_group_id'),

    array('type' => 'sql',
            'sql' => $_SQL['dq_cat_data']),


);


/**
* Puts the datastructures for this plugin into the glFusion database
* Note: Corresponding uninstall routine is in functions.inc
* @return   boolean True if successful False otherwise
*/
function plugin_install_dailyquote()
{
    global $INSTALL_plugin, $_CONF_DQ;

    $pi_name            = $_CONF_DQ['pi_name'];
    $pi_display_name    = $_CONF_DQ['pi_display_name'];
    $pi_version         = $_CONF_DQ['pi_version'];

    COM_errorLog("Attempting to install the $pi_display_name plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$pi_name]);
    if ($ret > 0) {
        return false;
    }

    return true;
}


/**
*   Plugin-specific post-installation function
*   Copies the admin documentation
*/
function plugin_postinstall_dailyquote()
{
    global $_CONF, $_CONF_DQ;

    $filepath = "{$_CONF['path']}/plugins/{$_CONF_DQ['pi_name']}";

    // Try to copy admin documentation.
    @copy($filepath .'/docs/'. $_CONF_DQ['pi_name'].'.html', 
            $_CONF['path_html'].'/docs/'.$_CONF_DQ['pi_name'].'.html');

}


/**
* Loads the configuration records for the Online Config Manager
* @return   boolean     true = proceed with install, false = an error occured
*/
function plugin_load_configuration_dailyquote()
{
    global $_CONF, $_CONF_DQ, $_TABLES;

    require_once $_CONF['path'].'plugins/'.$_CONF_DQ['pi_name'].'/install_defaults.php';

    // Get the admin group ID that was saved previously.
    $group_id = (int)DB_getItem($_TABLES['groups'], 'grp_id', 
            "grp_name='{$_CONF_DQ['pi_name']} Admin'");

    return plugin_initconfig_dailyquote($group_id);
}


/**
* Automatic uninstall function for plugins
* This code is automatically uninstalling the plugin.
* It passes an array to the core code function that removes
* tables, groups, features and php blocks from the tables.
* Additionally, this code can perform special actions that cannot be
* foreseen by the core code (interactions with other plugins for example)
*   @deprecated
* @return   array
*/
function plugin_autouninstall_dailyquote_X()
{
    $out = array (
        /* give the name of the tables, without $_TABLES[] */
        'tables' => array('ad_ads'),
        /* give the full name of the group, as in the db */
        'groups' => array('dailyquote Admin'),
        /* give the full name of the feature, as in the db */
        'features' => array('dailyquote.admin', 'dailyquote.edit', 'dailyquote.submit'),
        /* give the full name of the block, including 'phpblock_', etc */
        'php_blocks' => array(),
        /* give all vars with their name */
        'vars'=> array()
    );
    return $out;
}
?>
