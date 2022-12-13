<?php
/**
 * Upgrade routines for the Dailyquote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.3.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

global $_CONF, $_CONF_DQ, $_SQL_UPGRADE;

/** Include the default configuration values */
require_once __DIR__ . '/install_defaults.php';
/** Include the table creation strings */
require_once __DIR__ . "/sql/mysql_install.php";

use glFusion\Database\Database;
use glFusion\Log\Log;

/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True if this is a development update
 * @return  boolean         True on success, False on failure
 */
function DQ_do_upgrade($dvlp=false)
{
    global $_CONF_DQ, $_TABLES, $_PLUGIN_INFO, $dailyquoteConfigData;

    $db = Database::getInstance();
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

    if (!COM_checkVersion($current_ver, '0.4.0')) {
        $current_ver = '0.4.0';
        if (!DQ_do_upgrade_sql($current_ver, $dvlp)) return false;
        // Changing quote ID to auto_increment integer.
        // Must add new key field to quote table, then update quoteXcat to
        // change to the integer key, and finally change the field type for
        // quote ID to int.
        $qb = $db->conn->createQueryBuilder();
        try {
            $stmt = $qb->select('x.qid', 'q.qid')
                       ->distinct()
                       ->from($_TABLES['dailyquote_quoteXcat'], 'x')
                       ->leftJoin('x', $_TABLES['dailyquote_quotes'], 'q', 'q.id = x.qid')
                       ->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                try {
                    $db->conn->update(
                        $_TABLES['dailyquote_quoteXcat'],
                        array('qid' => $A['qid']),
                        array('qid' => $A['qid']),
                        array(Database::INTEGER, Database::STRING)
                    );
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
                }
            }
        }
        try {
            $db->conn->executeStatement(
                "ALTER TABLE {$_TABLES['dailyquote_quoteXcat']} CHANGE qid qid int(11) unsigned not null"
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        }
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
            Log::write('system', Log::ERROR,
                $_CONF_DQ['pi_display_name'] . " Error performing final update $current_ver to $installed_ver"
            );
            return false;
        }
    }
    CTL_clearCache($_CONF_DQ['pi_name']);
    Log::write('system', Log::INFO, "Succesfully updated the {$_CONF_DQ['pi_name']} plugin!");
    return true;
}


/**
 * Actually perform any sql updates.
 *
 * @param   string  $version    Plugin version
 * @param   boolean $dvlp       True to ignore errors and continue
 * @return  boolean         True on success, False on failure
 */
function DQ_do_upgrade_sql(string $version, bool $dvlp=false) : bool
{
    global $_CONF_DQ, $_SQL_UPGRADE;

    // If no sql statements passed in, return success
    if (empty($_SQL_UPGRADE[$version])) {
        return true;
    }

    $db = Database::getInstance();
    // Execute SQL now to perform the upgrade
    Log::write('system', Log::INFO, "--Updating {$_CONF_DQ['pi_name']} to version $version");
    foreach ($_SQL_UPGRADE[$version] as $sql) {
        Log::write('system', Log::DEBUG, "{$_CONF_DQ['pi_name']} $version update: Executing SQL => $sql");
        try {
            $db->conn->executeStatement($sql);
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
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

    try {
        Database::getInstance()->conn->update(
            $_TABLES['plugins'],
            array(
                'pi_version' => $_CONF_DQ['pi_version'],
                'pi_gl_version' =>$_CONF_DQ['gl_version'],
                'pi_homepage' => $_CONF_DQ['pi_url'],
            ),
            array('pi_name' => $_CONF_DQ['pi_name']),
            array(
                Database::STRING,
                Database::STRING,
                Database::STRING,
                Database::STRING,
            )
        );
        return true;
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        return false;
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
