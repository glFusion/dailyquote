<?php
/**
 * Upgrade routines for the Dailyquote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2023 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

global $_CONF, $_SQL_UPGRADE;

/** Include the default configuration values */
require_once __DIR__ . '/install_defaults.php';
/** Include the table creation strings */
require_once __DIR__ . "/sql/mysql_install.php";

use glFusion\Database\Database;
use glFusion\Log\Log;
use DailyQuote\Config;


/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True if this is a development update
 * @return  boolean         True on success, False on failure
 */
function DQ_do_upgrade($dvlp=false)
{
    global $_TABLES, $_PLUGIN_INFO, $dailyquoteConfigData;

    $cfg = \config::get_instance();
    $db = Database::getInstance();
    if (isset($_PLUGIN_INFO[Config::PI_NAME])) {
        if (is_array($_PLUGIN_INFO[Config::PI_NAME])) {
            // glFusion > 1.6.5
            $current_ver = $_PLUGIN_INFO[Config::PI_NAME]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[Config::PI_NAME];
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

    if (!COM_checkVersion($current_ver, '0.3.0')) {
        $current_ver = '0.3.0';
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
                "ALTER TABLE {$_TABLES['dailyquote_quoteXcat']} CHANGE qid qid mediumint unsigned not null"
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        }

        // Update the config for who can submit.
        // Make sure to run only once (if called by dvlpupdate).
        if (Config::isset('anonadd') && Config::isset('loginadd')) {
            $idx = array_search('submit_grp', array_column($dailyquoteConfigData, 'name'));
            if ($idx !== false) {       // make sure it's found
                if (Config::get('anonadd')) {
                    $grp_id = 2;        // all users
                } elseif (Config::get('loginadd')) {
                    $grp_id = 13;       // logged-in users
                } else {
                    $grp_id = 1;        // root users
                }
                $dailyquoteConfigData[$idx]['default_value'] = $grp_id;
            }
        }
        if (Config::isset('cb_enable') && Config::get('cb_enable') == 0) {
            // Leveraging cb_pos to infer cb_enable
            $cfg->set('cb_pos', 0, 'dailyquote');
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
                Config::get('pi_display_name') . " Error performing final update $current_ver to $installed_ver"
            );
            return false;
        }
    }
    CTL_clearCache(Config::PI_NAME);
    Log::write('system', Log::INFO, 'Succesfully updated the ' . Config::PI_NAME . ' plugin!');
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
    global $_SQL_UPGRADE;

    // If no sql statements passed in, return success
    if (empty($_SQL_UPGRADE[$version])) {
        return true;
    }

    $db = Database::getInstance();
    // Execute SQL now to perform the upgrade
    Log::write('system', Log::INFO, "--Updating {Config::PI_NAME} to version $version");
    foreach ($_SQL_UPGRADE[$version] as $sql) {
        Log::write('system', Log::DEBUG, "{Config::PI_NAME} $version update: Executing SQL => $sql");
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
function DQ_do_set_version(string $ver) : bool
{
    global $_TABLES;

    try {
        Database::getInstance()->conn->update(
            $_TABLES['plugins'],
            array(
                'pi_version' => Config::get('pi_version'),
                'pi_gl_version' => Config::get('gl_version'),
                'pi_homepage' => Config::get('pi_url'),
            ),
            array('pi_name' => Config::PI_NAME),
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
            // v0.4.0
            'batch.php',
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
