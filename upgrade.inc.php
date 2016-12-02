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

global $_CONF, $_CONF_DQ, $_DB_dbms;

/** Include the default configuration values */
require_once dirname(__FILE)) . '/install_defaults.php';
/** Include the table creation strings */
require_once dirname(__FILE)) . "/sql/{$_DB_dbms}_install.php";

/**
*   Perform the upgrade starting at the current version.
*
*   @param  string  $current_ver    Current installed version to be upgraded
*   @return integer                 Error code, 0 for success
*/
function DQ_do_upgrade($current_ver)
{
    global $_CONF_DQ;

    require_once DQ_PI_PATH . '/install_defaults.php';

    $error = 0;
    $c = config::get_instance();

    if ($current_ver < '0.1.4') {
        // upgrade to 0.1.4
        if ($c->group_exists($_CONF_DQ['pi_name'])) {
            $c->add('displayblocks', $_DQ_DEFAULT['displayblocks'], 'select',
                0, 0, 13, 170, true, $_CONF_DQ['pi_name']);
        } else {
            $error = 1;
        }

        if ($error)
            return $error;
    }

    if ($current_ver < '0.2.0') {
        if ($c->group_exists($_CONF_DQ['pi_name'])) {
            $c->del('anonview', $_CONF_DQ['pi_name']);
        }
        $errror = DQ_do_upgrade_sql('0.2.0', $sql);
        if ($error) return $error;
    }
        
    return $error;      // == 0 at this point
}


/**
*   Actually perform any sql updates
*
*   @param  array   $sql        Array of SQL statement(s) to execute
*   @return integer             0 for success, 1 for error
*/
function DQ_do_upgrade_sql($version='Undefined')
{
    global $_CONF_DQ, $_SQL_UPGRADE;

    // We control this, so it shouldn't happen, but just to be safe...
    if ($version == 'Undefined') {
        COM_errorLog("Error updating {$_CONF_DQ['pi_name']} - Undefined Version");
        return 1;
    }

    // If no sql statements passed in, return success
    if (empty($_SQL_UPGRADE[$version]))
        return 0;

    // Execute SQL now to perform the upgrade
    COM_errorLOG("--Updating DailyQuote Ads to version $version");
    foreach ($_SQL_UPGRADE[$version] as $sql) {
        COM_errorLOG("DailyQuote Plugin $version update: Executing SQL => $s");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during DailyQuote plugin update",1);
            return 1;
            break;
        }
    }
    return 0;
}

?>
