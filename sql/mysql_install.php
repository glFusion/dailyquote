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

// Main quote table
$_SQL[] = "CREATE TABLE {$_TABLES['dailyquote_quotes']} (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  Quote TEXT,
  Quoted TEXT,
  Title TEXT,
  Source TEXT,
  Sourcedate VARCHAR(16),
  Date INT(11) DEFAULT 0,
  UID INT NOT NULL default '1',
  Status TINYINT(1) NOT NULL DEFAULT '1',
  UNIQUE Quote (Quote(32))
) TYPE=MyISAM";

// Submission Table
$_SQL[] = "CREATE TABLE {$_TABLES['dailyquote_submission']} (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  Quote TEXT,
  Quoted TEXT,
  Title TEXT,
  Source TEXT,
  Sourcedate VARCHAR(16),
  Date INT(11) DEFAULT 0,
  UID INT NOT NULL default '1',
  UNIQUE quote (quote(32))
) TYPE=MyISAM";

// Categories Table
$_SQL[] = "CREATE TABLE {$_TABLES['dailyquote_cat']} (
  ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  Name VARCHAR(64) NOT NULL default 'Miscellany',
  Status TINYINT(1) NOT NULL default '1',
  UNIQUE Name (Name(10))
) TYPE=MyISAM";

// Lookup Table
$_SQL[] = "CREATE TABLE {$_TABLES['dailyquote_lookup']} (
  QID VARCHAR(40) NOT NULL,
  CID MEDIUMINT UNSIGNED NOT NULL,
  UID MEDIUMING UNSIGNED NOT NULL,
  Status TINYINT(1) NOT NULL default '1',
  PRIMARY KEY(QID,CID,UID)
) TYPE=MyISAM";


?>
