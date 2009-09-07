<?php
/**
*   Schema definition.
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

$_SQL = array();

// Main quote table
$_SQL['dailyquote_quotes'] = 
  "CREATE TABLE {$_TABLES['dailyquote_quotes']} (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  content TEXT,
  quoted TEXT,
  title TEXT,
  source TEXT,
  sourcedate VARCHAR(16),
  dt INT(11) DEFAULT 0,
  uid INT NOT NULL default '1',
  status TINYINT(1) NOT NULL DEFAULT '1',
  UNIQUE idx_quote (content(32))
) TYPE=MyISAM";

// Submission Table
$_SQL['dailyquote_submission'] = 
  "CREATE TABLE {$_TABLES['dailyquote_submission']} (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  content TEXT,
  quoted TEXT,
  title TEXT,
  source TEXT,
  sourcedate VARCHAR(16),
  dt INT(11) DEFAULT 0,
  uid INT NOT NULL default '1',
  UNIQUE idx_quote (content(32))
) TYPE=MyISAM";

// Categories Table
$_SQL['dailyquote_cat'] = 
  "CREATE TABLE {$_TABLES['dailyquote_cat']} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL default 'Miscellany',
  status TINYINT(1) NOT NULL default '1',
  UNIQUE idx_name (name(10))
) TYPE=MyISAM";

// Lookup Table
$_SQL['dailyquote_lookup'] = 
  "CREATE TABLE {$_TABLES['dailyquote_lookup']} (
  qid VARCHAR(40) NOT NULL,
  cid INT UNSIGNED NOT NULL,
  uid INT UNSIGNED NOT NULL,
  status TINYINT(1) UNSIGNED NOT NULL default '1',
  PRIMARY KEY(qid,cid,uid)
) TYPE=MyISAM";


?>
