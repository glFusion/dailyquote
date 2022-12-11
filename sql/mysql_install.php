<?php
/**
 * Schema definition.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

global $_TABLES;

$_SQL = array();
use Dailyquote\MO;

// Main quote table
$_SQL['dailyquote_quotes'] = "CREATE TABLE {$_TABLES['dailyquote_quotes']} (
  `quote_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `quote` text DEFAULT NULL,
  `quoted` text DEFAULT NULL,
  `title` text DEFAULT NULL,
  `source` text DEFAULT NULL,
  `sourcedate` varchar(16) DEFAULT NULL,
  `dt` int(11) unsigned DEFAULT 0,
  `uid` int(11) unsigned NOT NULL DEFAULT 1,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `approved` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`quote_id`),
  UNIQUE KEY `idx_quote` (`quote`(32))
) ENGINE=MyISAM";

// Categories Table
$_SQL['dailyquote_cat'] = "CREATE TABLE {$_TABLES['dailyquote_cat']} (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  enabled TINYINT(1) UNSIGNED NOT NULL default '1',
  UNIQUE idx_name (`name`(10))
) ENGINE=MyISAM";

// Lookup Table
$_SQL['dailyquote_quoteXcat'] = "CREATE TABLE {$_TABLES['dailyquote_quoteXcat']} (
  qid VARCHAR(40) NOT NULL,
  cid INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(qid,cid)
) ENGINE=MyISAM";


// Default data
$_SQL['dq_cat_data'] = "INSERT INTO {$_TABLES['dailyquote_cat']} (name, enabled)
        VALUES ('" . 'Miscellaneous' . "', '1');";

$_SQL_UPGRADE = array(
    '0.2.0' => array(
        "ALTER TABLE {$_TABLES['dailyquote_submission']}
            ADD `enabled` tinyint(1) default 1",
    ),
    '0.4.0' => array(
        "ALTER TABLE {$_TABLES['dailyquote_quotes']} DROP PRIMARY KEY",
        "ALTER TABLE {$_TABLES['dailyquote_quotes']} ADD quote_id int(11) unsigned not null auto_increment primary key first",
        "ALTER TABLE {$_TABLES['dailyquote_quotes']} ADD approved tinyint(1) unsigned not null default 1",
    ),
);
