<?php
//  $Id$
/**
*   Table names and other global configuraiton values.
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** @global array $_TABLES */
global $_TABLES;
/** @global string $_DB_table_prefix */
global $_DB_table_prefix;

$DQ_prefix = $_DB_table_prefix . 'dailyquote_';

// Table definitions
$_TABLES['dailyquote_quotes']      = $DQ_prefix . 'quotes';
$_TABLES['dailyquote_submission']  = $DQ_prefix . 'submission';
$_TABLES['dailyquote_cat']         = $DQ_prefix . 'category';
$_TABLES['dailyquote_cat_sub']     = $DQ_prefix . 'category_sub';
$_TABLES['dailyquote_lookup']      = $DQ_prefix . 'lookup';
$_TABLES['dailyquote_settings']    = $DQ_prefix . 'settings';

// Static configuration items
$_CONF_DQ['pi_version'] = '0.01';
$_CONF_DQ['pi_name'] = 'dailyquote';
$_CONF_DQ['gl_version'] = '1.1.4';
$_CONF_DQ['pi_url'] = 'http://www.leegarner.com';
$_CONF_DQ['pi_display_name'] = 'Daily Quotes';


// Temporary storage for items yet to be moved to the configuration system
// String length for quoted personalities in WhatsNewBlock
$_CONF_DQ['whatsnewnamelength'] = 20;

//directory to use for batch import and backup storage
//do not change!  at least until I've made this var available to all scripts using the dir.
$_CONF_DQ['datadir'] = $_CONF['path_html'] . 'dailyquote/data/';
$_CONF_DQ['disp_limit'] = '4';

?>
