<?php
/**
*   Upgrade routines for the Dailyquote plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

global $_CONF, $_CONF_DQ, $_DB_dbms, $_SQL_UPGRADE;

/** Include the default configuration values */
require_once dirname(__FILE__) . '/install_defaults.php';
/** Include the table creation strings */
require_once dirname(__FILE__) . "/sql/{$_DB_dbms}_install.php";


/**
*   Perform the upgrade starting at the current version.
*
*   @return integer                 Error code, 0 for success
*/
function DQ_do_upgrade()
{
    global $_CONF_DQ, $_TABLES;

    $current_ver = DB_getItem($_TABLES['plugins'], 'pi_version',
        "pi_name = '{$_CONF_DQ['pi_name']}'");
    if (empty($current_ver)) {
        COM_errorLog("Error getting the {$_CONF_DQ['pi_name']} plugin version",1);
        return false;
    }

    require_once DQ_PI_PATH . '/install_defaults.php';

    $error = 0;
    $c = config::get_instance();

    if ($current_ver < '0.1.4') {
        // upgrade to 0.1.4
        if ($c->group_exists($_CONF_DQ['pi_name'])) {
            $c->add('displayblocks', $_DQ_DEFAULT['displayblocks'], 'select',
                0, 0, 13, 170, true, $_CONF_DQ['pi_name']);
        } else {
            return false;
        }
    }

    if ($current_ver < '0.2.0') {
        if ($c->group_exists($_CONF_DQ['pi_name'])) {
            $c->del('anonview', $_CONF_DQ['pi_name']);
        }
        if (!DQ_do_upgrade_sql('0.2.0', $sql)) {
            return false;
        }
    }

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
                pi_version = '{$_CONF_DQ['pi_version']}',
                pi_gl_version = '{$_CONF_DQ['gl_version']}',
                pi_homepage = '{$_CONF_DQ['pi_url']}'
            WHERE pi_name = '{$_CONF_DQ['pi_name']}';";
    DB_query($sql);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_CONF_DQ['pi_name']} plugin version",1);
        return false;
    }

    COM_errorLog("Succesfully updated the {$_CONF_DQ['pi_name']} plugin!",1);
    return true;
}


/**
*   Actually perform any sql updates
*
*   @param  array   $sql        Array of SQL statement(s) to execute
*   @return integer             0 for success, 1 for error
*/
function DQ_do_upgrade_sql($version)
{
    global $_CONF_DQ, $_SQL_UPGRADE;

    // If no sql statements passed in, return success
    if (empty($_SQL_UPGRADE[$version]))
        return 0;

    // Execute SQL now to perform the upgrade
    COM_errorLog("--Updating {$_CONF_DQ['pi_name']} to version $version");
    foreach ($_SQL_UPGRADE[$version] as $sql) {
        COM_errorLog("{$_CONF_DQ['pi_name']} $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during {$_CONF_DQ['pi_name']} plugin update",1);
            return false;
            break;
        }
    }
    return true;
}

?>
