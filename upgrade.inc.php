<?php
//  $Id: upgrade.inc.php 5217 2009-12-06 21:15:25Z lgarner $
/**
*   Upgrade routines for the Dailyquote plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    ban
*   @version    0.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*   GNU Public License v2 or later
*   @filesource
*/

// Required to get the config values
global $_CONF, $_CONF_DQ;


/**
*   Perform the upgrade starting at the current version.
*
*   @param  string  $current_ver    Current installed version to be upgraded
*   @return integer                 Error code, 0 for success
*/
function DQ_do_upgrade($current_ver)
{
    global $_TABLES;

    $error = 0;

    return $error;

}


?>
