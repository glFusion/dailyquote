<?php
/**
 * Table names and other global configuraiton values.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
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
$_TABLES['dailyquote_quoteXcat']   = $DQ_prefix . 'quoteXcat';

// Static configuration items
use DailyQuote\Config;
Config::set('pi_version', '0.3.1');
Config::set('gl_version', '2.0.0');
