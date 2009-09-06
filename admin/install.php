<?php
/****************************************************************************
*   Daily Quote Plugin for Geeklog - The Ultimate Weblog
*****************************************************************************
*   $Id$
*****************************************************************************
*   Copyright (C) 2004 by the following authors:

*   Author: Alf Deeley aka machinari - ajdeeley@summitpages.ca
*   Constructed with the Universal Plugin
*   Copyright (C) 2002 by the following authors:
*   Tom Willett                 -    twillett@users.sourceforge.net
*   Blaine Lang                 -    langmail@sympatico.ca
*   The Universal Plugin is based on prior work by:
*   Tony Bibbs                  -    tony@tonybibbs.com
*****************************************************************************
*   This program is free software; you can redistribute it and/or
*   modify it under the terms of the GNU General Public License
*   as published by the Free Software Foundation; either version 2
*   of the License, or (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program; if not, write to the Free Software Foundation,
*   Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*****************************************************************************/

require_once('../../../lib-common.php');

// Only let Root users access this page
if (!SEC_inGroup('Root')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote install/uninstall page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_DQ00['access_denied']);
    $display .= $LANG_DQ00['access_denied_msg'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}

$base_path = "{$_CONF['path']}plugins/dailyquote";

// Check that the user has created the config.php file
if (!file_exists("$base_path/config.php")) {
    echo COM_siteHeader();
    echo '<span class="alert">';
    echo COM_startBlock("Missing Config File");
    echo "Config.PHP file not found.  You probably need to copy config-dist.php
to config.php in the plugin directory.";
    echo COM_endBlock();
    echo "</span>\n";
    echo COM_siteFooter(true);
    echo $display;
    exit;
}

require_once $base_path . '/functions.inc';
// Online configuration requirements
require_once $_CONF['path_system'] . 'classes/config.class.php';
require_once $base_path . '/install_defaults.php';


// Default data
$DEFVALUES = array();
$DEFVALUES['dailyquote_settings'] = 
    "INSERT INTO 
        {$_TABLES['dailyquote_settings']} 
    VALUES 
        (10, 50, 1, 0, 1, 1, 1, 0, 0, 2, 1, 0, 0, 0, 
        1, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1, '', 0, '', 0)";
$DEFVALUES['dailyquote_phpblock'] = "INSERT INTO {$_TABLES['blocks']}
        (is_enabled,name,type,title,tid,blockorder,onleft,phpblockfn,group_id,
        owner_id,perm_owner,perm_group,perm_members,perm_anon)
    VALUES
        ('1','dailyquote','phpblock',
        'Quote of the Day','all',255,0,
        'phpblock_dailyquote_random_quote',2,2,3,3,2,2)
;";
$DEFVALUES['dqmenu_phpblock'] = "INSERT INTO {$_TABLES['blocks']}
        (is_enabled,name,type,title,tid,blockorder,onleft,phpblockfn,group_id,
        owner_id,perm_owner,perm_group,perm_members,perm_anon)
    VALUES
        ('1','dqmenu','phpblock',
        'DailyQuote','all',255,0,
        'phpblock_dailyquote_dqmenu',2,2,3,3,2,2)
;";

// Security Feature to add
$NEWFEATURE = array();
$NEWFEATURE['dailyquote.edit']  ="dailyquote Admin Rights";
$NEWFEATURE['dailyquote.view']  ="dailyquote Access";
$NEWFEATURE['dailyquote.add']   ="dailyquote Add Quote Rights";



/**
* Puts the datastructures for this plugin into the Geeklog database
* Note: Corresponding uninstall routine is in functions.inc
* @return   boolean True if successful False otherwise
*/
function plugin_install_dailyquote()
{
    global $NEWTABLE, $DEFVALUES, $NEWFEATURE, $_CONF_DQ;
    global $_TABLES, $_CONF;

    $pi_name = $_CONF_DQ['pi_name'];
    $pi_version = $_CONF_DQ['pi_version'];
    $pi_url = $_CONF_DQ['pi_url'];
    $gl_version = $_CONF_DQ['gl_version'];

    COM_errorLog("Attempting to install the $pi_name Plugin",1);

    // Create the Plugins Tables
    require_once($_CONF['path'] . 'plugins/dailyquote/sql/dailyquote_sql_install.php');
    for ($i = 1; $i <= count($_SQL); $i++) {
        $progress .= "executing " . current($_SQL) . "<br>\n";
        COM_errorLOG("executing " . current($_SQL));
        DB_query(current($_SQL),'1');
        if (DB_error()) {
            COM_errorLog("Error Creating $table table",1);
            plugin_uninstall_dailyquote('DeletePlugin');
            return false;
            exit;
        }
        next($_SQL);
    }
    COM_errorLog("Success - Created $table table",1);

    // Insert Default Data
    foreach ($DEFVALUES as $table => $sql) {
        COM_errorLog("Inserting default data into $table table",1);
        DB_query($sql,1);
        if (DB_error()) {
            COM_errorLog("Error inserting default data into $table table",1);
            plugin_uninstall_dailyquote();
            return false;
            exit;
        }
        COM_errorLog("Success - inserting data into $table table",1);
    }

    // Create the plugin admin security group
    COM_errorLog("Attempting to create $pi_name admin group", 1);
    DB_query("INSERT INTO 
            {$_TABLES['groups']} 
            (grp_name, grp_descr) 
        VALUES 
            ('$pi_name Admin', 
            'Users in this group can administer the $pi_name plugin')",
    1);
    if (DB_error()) {
        plugin_uninstall_dailyquote();
        return false;
        exit;
    }
    COM_errorLog('...success',1);
    $group_id = DB_insertId();

    // Save the grp id for later uninstall
    COM_errorLog('About to save group_id to vars table for use during uninstall',1);
    DB_query("INSERT INTO 
            {$_TABLES['vars']} 
        VALUES 
            ('{$pi_name}_gid', $group_id)",
    1);
    if (DB_error()) {
        plugin_uninstall_dailyquote();
        return false;
        exit;
    }
    COM_errorLog('...success',1);

    // Add plugin Features
    foreach ($NEWFEATURE as $feature => $desc) {
        COM_errorLog("Adding $feature feature",1);
        DB_query("INSERT INTO {$_TABLES['features']} (ft_name, ft_descr) "
            . "VALUES ('$feature','$desc')",1);
        if (DB_error()) {
            COM_errorLog("Failure adding $feature feature",1);
            plugin_uninstall_dailyquote();
            return false;
            exit;
        }
        $feat_id = DB_insertId();
        COM_errorLog("Success",1);
        COM_errorLog("Adding $feature feature to admin group",1);
        DB_query("INSERT INTO {$_TABLES['access']} (acc_ft_id, acc_grp_id) VALUES ($feat_id, $group_id)");
        if (DB_error()) {
            COM_errorLog("Failure adding $feature feature to admin group",1);
            plugin_uninstall_dailyquote();
            return false;
            exit;
        }
        COM_errorLog("Success",1);
    }        

    // OK, now give Root users access to this plugin now! NOTE: Root group should always be 1
    COM_errorLog("Attempting to give all users in Root group access to $pi_name admin group",1);
    DB_query("INSERT INTO {$_TABLES['group_assignments']} VALUES ($group_id, NULL, 1)");
    if (DB_error()) {
        plugin_uninstall_dailyquote();
        return false;
        exit;
    }

    // Load the online configuration records
    if (!plugin_initconfig_dailyquote($group_id)) {
        PLG_uninstall($pi_name);
        return false;
    }

    // Register the plugin with glFusion
    COM_errorLog("Registering $pi_name plugin with glFusion", 1);
    DB_delete($_TABLES['plugins'],'pi_name',$pi_name);
    DB_query("INSERT INTO 
            {$_TABLES['plugins']} 
            (pi_name, pi_version, pi_gl_version, pi_homepage, pi_enabled) 
        VALUES 
            ('$pi_name', '$pi_version', '$gl_version', '$pi_url', 1)"
    );
    if (DB_error()) {
        plugin_uninstall_dailyquote();
        return false;
        exit;
    }

    COM_errorLog("Succesfully installed the $pi_name Plugin!",1);
    return true;
}

/* 
* Main Function
*/

$display = COM_siteHeader();
$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('install', 'install.thtml');
$T->set_var('install_header', $LANG_DQ00['install_header']);
$T->set_var('img',$_CONF['site_url'] . '/dailyquote/images/dailyquote.gif');
$T->set_var('cgiurl', $_CONF['site_admin_url'] . '/plugins/dailyquote/install.php');
$T->set_var('admin_url', $_CONF['site_admin_url'] . '/plugins/dailyquote/index.php');
$T->set_var('plugin_admin_url', $_CONF['site_admin_url'] . '/plugins.php' );

$action = $_REQUEST['action'];
switch ($action) {
case 'install':
    if (plugin_install_dailyquote()) {
        $install_msg = sprintf($LANG_DQ00['install_success'],
            $_CONF['site_admin_url'] .'/plugins/dailyquote/install_doc.htm');
        $T->set_var('installmsg1',$LANG_DQ00['install_success']);
        $T->set_var('editor',$LANG_DQ00['editor']);
    } else {
        $T->set_var('installmsg1',$LANG_DQ00['install_failed']);
    }
    break;

case 'uninstall':
   plugin_uninstall_dailyquote('installed');
   $T->set_var('installmsg1',$LANG_DQ00['uninstall_msg']);
    break;
}

if (DB_count($_TABLES['plugins'], 'pi_name', $_CONF_DQ['pi_name']) == 0) {
    $T->set_var('installmsg2', $LANG_DQ00['uninstalled']);
    $T->set_var('readme', $LANG_DQL00['readme']);
    $T->set_var('installdoc', $LANG_DQ00['installdoc']);
    $T->set_var('btnmsg', $LANG_DQ00['install']);
    $T->set_var('action','install');
} else {
    $T->set_var('installmsg2', $LANG_DQ00['installed']);
    $T->set_var('btnmsg', $LANG_DQ00['uninstall']);
    $T->set_var('editor',$LANG_DQ00['editor']);
    $T->set_var('action','uninstall');
}
$T->parse('output','install');
$display .= $T->finish($T->get_var('output'));
$display .= COM_siteFooter(true);

echo $display;

?>
