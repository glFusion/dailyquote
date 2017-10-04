<?php
/**
*   Schema definition.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

global $_TABLES;

$_SQL = array();

$quote_table_creation = "id VARCHAR(40) NOT NULL PRIMARY KEY,
  quote TEXT,
  quoted TEXT,
  title TEXT,
  source TEXT,
  sourcedate VARCHAR(16),
  dt INT(11) UNSIGNED DEFAULT 0,
  uid INT(11) UNSIGNED NOT NULL default '1',
  enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  UNIQUE idx_quote (`quote`(32)
) ENGINE=MyISAM";

// Main quote table
$_SQL['dailyquote_quotes'] = "CREATE TABLE {$_TABLES['dailyquote_quotes']} (
  $quote_table_creation
)";

// Submission Table
$_SQL['dailyquote_submission'] = "CREATE TABLE {$_TABLES['dailyquote_submission']} (
  $quote_table_creation
)";

// Categories Table
$_SQL['dailyquote_cat'] =
  "CREATE TABLE {$_TABLES['dailyquote_cat']} (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  enabled TINYINT(1) UNSIGNED NOT NULL default '1',
  UNIQUE idx_name (`name`(10))
) ENGINE=MyISAM";

// Lookup Table
$_SQL['dailyquote_quoteXcat'] =
  "CREATE TABLE {$_TABLES['dailyquote_quoteXcat']} (
  qid VARCHAR(40) NOT NULL,
  cid INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(qid,cid)
) ENGINE=MyISAM";


// Default data
$_SQL['dq_cat_data'] =
    "INSERT INTO {$_TABLES['dailyquote_cat']} (name, enabled)
        VALUES ('{$LANG_DQ['misc']}', '1');";

$_SQL_UPGRADE = array(
    '0.2.0' => array(
        "ALTER TABLE {$_TABLES['dailyquote_submission']}
            ADD `enabled` tinyint(1) default 1",
    ),
);

?>
