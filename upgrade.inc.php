<?php
/**
 * Upgrade routines for the Dailyquote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

global $_CONF, $_CONF_DQ, $_DB_dbms, $_SQL_UPGRADE;

/** Include the default configuration values */
require_once __DIR__ . '/install_defaults.php';
/** Include the table creation strings */
require_once __DIR__ . "/sql/mysql_install.php";


/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True if this is a development update
 * @return  boolean         True on success, False on failure
 */
function DQ_do_upgrade($dvlp=false)
{
    global $_CONF_DQ, $_TABLES, $_PLUGIN_INFO, $dailyquoteConfigData;

    if (isset($_PLUGIN_INFO[$_CONF_DQ['pi_name']])) {
        if (is_array($_PLUGIN_INFO[$_CONF_DQ['pi_name']])) {
            // glFusion > 1.6.5
            $current_ver = $_PLUGIN_INFO[$_CONF_DQ['pi_name']]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[$_CONF_DQ['pi_name']];
        }
    } else {
        return false;
    }
    $installed_ver = plugin_chkVersion_dailyquote();

    if (!COM_checkVersion($current_ver, '0.2.0')) {
        $current_ver = '0.2.0';
        if (!DQ_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!DQ_do_set_version($current_ver)) return false;
    }

    // Update the configuration
    USES_lib_install();
    require_once __DIR__ . '/install_defaults.php';
    _update_config('dailyquote', $dailyquoteConfigData);

    // Remove deprecated files
    DQ_remove_old_files();

    // Final version update to catch updates that don't go through
    // any of the update functions, e.g. code-only updates
    if (!COM_checkVersion($current_ver, $installed_ver)) {
        if (!DQ_do_set_version($installed_ver)) {
            COM_errorLog($_CONF_DQ['pi_display_name'] .
                    " Error performing final update $current_ver to $installed_ver");
            return false;
        }
    }
    CTL_clearCache($_CONF_DQ['pi_name']);
    COM_errorLog("Succesfully updated the {$_CONF_DQ['pi_name']} plugin!",1);
    return true;
}


/**
 * Actually perform any sql updates.
 *
 * @param   string  $version    Plugin version
 * @param   boolean $dvlp       True to ignore errors and continue
 * @return  boolean         True on success, False on failure
 */
function DQ_do_upgrade_sql($version, $dvlp=false)
{
    global $_CONF_DQ, $_SQL_UPGRADE;

    // If no sql statements passed in, return success
    if (empty($_SQL_UPGRADE[$version]))
        return true;

    // Execute SQL now to perform the upgrade
    COM_errorLog("--Updating {$_CONF_DQ['pi_name']} to version $version");
    foreach ($_SQL_UPGRADE[$version] as $sql) {
        COM_errorLog("{$_CONF_DQ['pi_name']} $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during {$_CONF_DQ['pi_name']} plugin update",1);
            if (!$dvlp) return false;
        }
    }
    return true;
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
 */
function DQ_do_set_version($ver)
{
    global $_TABLES, $_CONF_DQ;

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '{$_CONF_DQ['pi_version']}',
            pi_gl_version = '{$_CONF_DQ['gl_version']}',
            pi_homepage = '{$_CONF_DQ['pi_url']}'
        WHERE pi_name = '{$_CONF_DQ['pi_name']}'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_CONF_DQ['pi_display_name']} Plugin version",1);
        return false;
    } else {
        return true;
    }
}


/**
 * Remove deprecated files
 * Errors in unlink() and rmdir() are ignored.
 */
function DQ_remove_old_files()
{
    global $_CONF;

    $paths = array(
        // private/plugins/dailyquote
        __DIR__ => array(
	    'templates/batchaddform.uikit.thtml',
	    'templates/catform.uikit.thtml',
	    'templates/dispquotes.uikit.thtml',
	    'templates/editform.uikit.thtml',
        ),
        // public_html/dailyquote
        $_CONF['path_html'] . 'dailyquote' => array(
            'docs/english/config.legacy.html',
        ),
        // public_html/admin/plugins/dailyquote
        $_CONF['path_html'] . 'admin/plugins/dailyquote' => array(
        ),
    );

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            @unlink("$path/$file");
        }
    }
}

?>
