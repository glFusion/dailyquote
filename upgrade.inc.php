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
    }
        
    return $error;
}

?>
