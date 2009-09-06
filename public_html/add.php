<?php
/****************************************************************************
*   Daily Quote Plugin for Geeklog - The Ultimate Weblog
*****************************************************************************
*   $Id$
*****************************************************************************
*   Copyright (C) 2004 by the following authors:

*   Author: Alf Deeley aka machinari - ajdeeley@summitpages.ca
*   Constructed with the Universal Plugin
*   Copyright (C) 2002 by the following authors:
*   Tom Willett                 -    twillett@users.sourceforge.net
*   Blaine Lang                 -    langmail@sympatico.ca
*   The Universal Plugin is based on prior work by:
*   Tony Bibbs                  -    tony@tonybibbs.com
*****************************************************************************
*   This program is free software; you can redistribute it and/or
*   modify it under the terms of the GNU General Public License
*   as published by the Free Software Foundation; either version 2
*   of the License, or (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program; if not, write to the Free Software Foundation,
*   Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*****************************************************************************/

require_once '../lib-common.php';

// Retrieve access settings
$query = DB_query("SELECT anonadd, anonview, loginadd FROM {$_TABLES['dailyquote_settings']}");
list($anonadd,$anonview,$loginadd) = DB_fetchArray($query);

// Check user has rights to access this page
if (((($anonview == '0') || ($anonadd == '0')) && ($_USER['uid'] < 2)) || 
    (($loginadd == '0') && (!SEC_hasRights('dailyquote.add')))
) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_DQ00['access_denied']);
    $display .= $LANG_DQ00['access_denied_msg1'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}


//displays the add form for single quotes
function add_quote_form()
{
    global $_TABLES, $_CONF, $_USER, $LANG_DQ;

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'addformheader.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    //displays the add quote form for single quotations
    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'addform.thtml');
    $T->set_var('addqtitle', $LANG_DQ['addquote']);
    $T->set_var('addtitle', $LANG_DQ['addtitle']);
    $T->set_var('addquotation', $LANG_DQ['quotation']);
    $T->set_var('nomarks', $LANG_DQ['nomarks']);
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('addquoted', $LANG_DQ['quoted']);
    $T->set_var('addsource', $LANG_DQ['addsource']);
    $T->set_var('addsourcedate', $LANG_DQ['addsourcedate']);
    $T->set_var('dateformat', $LANG_DQ['srcdateformat']);
    $T->set_var('category', $LANG_DQ['cat']);

    //retrieve categories from db if any and display
    if (!$result = DB_query("SELECT ID, Name 
                            FROM {$_TABLES['dailyquote_cat']} 
                            WHERE Status='1' 
                            ORDER BY Name")) {
        $errstatus = 1;
    } else {
        $numrows = DB_numRows($result);
    }

    if ($numrows == 0) {
        //first cat must be created--doesn't matter who does it
        $T->set_var('firstcat', $LANG_DQ['firstcat']);
        $T->set_var('catinput', '<input name="cat[]" type="text" size="18" value="" />');
    } else {
        //check perm to create category
        if (!$query = DB_query("SELECT loginaddcat 
                                FROM {$_TABLES['dailyquote_settings']}")) {
            $errstatus = 1;
        } else {
            list($loginaddcat) = DB_fetchArray($query);
            if (($loginaddcat == 0) && (!SEC_hasRights('dailyquote.edit'))){
                $T->set_var('choosecat', $LANG_DQ['choosecat']);
            } else {
                $T->set_var('choosecat', $LANG_DQ['choosecat1']);
                $T->set_var('catinput', '<input name="cat[]" type="text" size="18" value="" />');
            }
        }
    }
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    //display $colnum vertical columns
    //if you increase or decrease this number,
    //then you'll need to adjust the cell width in the addcol and addcatcol.thtml files
    if ($numrows > 0) {
        $i = 0;
        $colnum = 5;
        $down = ceil($numrows/$colnum);
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'addcol.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));

        while ($row = DB_fetchArray($result)) {
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'catoption.thtml');
            if ($chkst = DB_query(
                    "SELECT * FROM {$_TABLES['dailyquote_lookup']} 
                    WHERE CID='{$row['ID']}' 
                    AND Status='0' 
                    LIMIT 1")) {
                if (DB_numRows($chkst) > 0) {
                    $T->set_var('discat', ' color: #808080;');
                }
            }
            $T->set_var('catoption', $row['Name']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            $i++;
            if (($i % $down === 0) && ($i % $colnum !== 0)){
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'addcol2.thtml');
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
            }
        }

    }
    
    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'closeaddform.thtml');
    $T->set_var('catreadme', $LANG_DQ['catreadme']);
    $T->set_var('submit', $LANG_DQ['submitquote']);
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'addformfooter.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}


//adds a single quote to the db with pertinent info
function single_add_quote()
{
    global $_TABLES, $_CONF, $_USER, $LANG_DQ, $_POST;

    //add quote to the database
    //return to add.php with msg=failure or with msg=success
    if ($_POST['quote'] == ""){
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['emptyquote'] . "</p>";
        return $retval;
    }

    //get title to insert
    if ((isset($_POST['title'])) && ($_POST['title'] != '')){
        $title = slashctrl(strip_tags((COM_checkWords((trim ($_POST['title']))))));
    }

    //get quote to insert
    if ((isset($_POST['quote'])) && ($_POST['quote'] != '')){
        $quote = slashctrl(strip_tags((COM_checkWords((trim ($_POST['quote'])))), '<strong><em><br><br />'));
    }

    //get quoted to insert
    if ((isset($_POST['quoted'])) && ($_POST['quoted'] != '')){
        $quoted = slashctrl(strip_tags((COM_checkWords((trim ($_POST['quoted']))))));
    } else {
        $quoted = $LANG_DQ['unknown'];
    }

    //get source
    if ((isset($_POST['source'])) && ($_POST['source'] != '')){
        $source = slashctrl(strip_tags((COM_checkWords((trim ($_POST['source']))))));
    }

    //get source date
    //for now it is a VARCHAR field due to the lack of range with DATE, etc.
    //to regex or not to regex? that is the query.
    if ((isset($_POST['sourcedate'])) && ($_POST['sourcedate'] != '')){
        $sourcedate = slashctrl(strip_tags((COM_checkWords((trim ($_POST['sourcedate']))))));
    }

    //get date to insert
    $date = "CURDATE()";

    //get status to insert.  Status is for the moderation functions not category management.
    $queue = DB_query("SELECT queue FROM {$_TABLES['dailyquote_settings']}");
    list($queuestatus) = DB_fetchArray($queue);
    if ((SEC_hasRights('dailyquote.edit')) || ($queuestatus == '0')) {
        $st = "1";
    } else {
        $st = "0";
    }

    //get user name and UID to insert
    if (!empty($_USER['username'])) {
        //$user = $_USER['username'];
        $uid = $_USER['uid'];
    } else {
        $user = $LANG_DQ['anonymous'];
        $uid = '1';
    }

    //get ip to insert
    //$ip = getenv("REMOTE_ADDR");

    //add the quote to the db with all relevant info
    $sql = "INSERT IGNORE INTO 
            {$_TABLES['dailyquote_quotes']} 
        SET 
            Quotes='$quote', 
            Quoted='$quoted', 
            Title='$title', 
            Source='$source', 
            Sourcedate='$sourcedate', 
            Date=CURDATE(), 
            Status='$st', 
            UID='$uid'";
    if (!DB_query( $sql)) {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" .
                 $LANG_DQ['inserterror'] . "</p>";
        return $retval;
    } elseif (mysql_affected_rows() > 0) {
        $qid = mysql_insert_id();
        //get category to insert
        $a = $_POST['cat'];
        //break up the 'cat' array and loop thru it to insert all cats listed
        foreach ($a as $cat) {
            if (!empty($cat)) {
                $cat = slashctrl(strip_tags(COM_checkWords((trim ($cat)))));
                $sql = "INSERT IGNORE INTO 
                        {$_TABLES['dailyquote_cat']} 
                    SET 
                        Name='$cat', 
                        Status='$st'";
                if (!DB_query($sql)) {
                    $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . 
                                $LANG_DQ['inserterror'] . "</p>";
                    return $retval;
                }

                if (mysql_affected_rows() > 0) {
                    //this portion will never happen unless a new cat is included in cat[]
                    $cid = mysql_insert_id();
                }
                else {
                    $sql = 
                        "SELECT 
                            ID 
                        FROM 
                            {$_TABLES['dailyquote_cat']} 
                        WHERE 
                            Name='$cat'";
                    $result = DB_query($sql);
                    if (!$result){
                        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . 
                                    $LANG_DQ['inserterror'] . "</p>";
                        return $retval;
                    }
                    $row = DB_fetchArray($result);
                    $cid = $row[ID];
                    if (!$chkst = DB_query("SELECT Status FROM {$_TABLES['dailyquote_lookup']} WHERE CID='$cid'")) {
                        $errstatus = 1;
                    } else {
                        list($status) = DB_fetchArray($chkst);
                    }
                }

                $sql = "INSERT INTO 
                        {$_TABLES['dailyquote_lookup']} 
                    SET 
                        QID='$qid', 
                        CID='$cid', 
                        UID='$uid', 
                        Status='$status'";
                if (!DB_query($sql)) {
                    $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . 
                                $LANG_DQ['inserterror'] . "</p>";
                    return $retval;
                }
            } elseif (empty($a[1])) {
                $cat = $LANG_DQ['misc'];
                $sql = "INSERT IGNORE INTO 
                        {$_TABLES['dailyquote_cat']} 
                    SET Name='$cat', 
                    Status='$st'";
                DB_query($sql);
                if (mysql_affected_rows() > 0) {
                    //this portion will only ever be called once and then will 
                    //be useless once the 'misc' category is established
                    $cid = mysql_insert_id();
                    $status = '1';
                } else {
                    $result = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='$cat'");
                    if (!$result){
                        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['inserterror'] . "</p>";
                        return $retval;
                    }
                    $row = DB_fetchArray($result);
                    $cid = $row[ID];
                    if (!$chkst = DB_query("SELECT Status FROM {$_TABLES['dailyquote_lookup']} WHERE CID='$cid'")) {
                        $errstatus = 1;
                    } else {
                        list($status) = DB_fetchArray($chkst);
                    }
                }
                $sql = "INSERT INTO 
                        {$_TABLES['dailyquote_lookup']} 
                    SET 
                        QID='$qid', 
                        CID='$cid', 
                        UID='$uid', 
                        Status='$status'";
                if (!DB_query($sql)) {
                    $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . 
                            $LANG_DQ['inserterror'] . "</p>";
                    return $retval;
                }
            }
        }
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">";
        if ($st == "0") {
            $retval .= $LANG_DQ['addqueue'];
        } else {
            $retval .= $LANG_DQ['addsuccess'];
        }
        $retval .= "</p>";
    } else {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['dupquote'] . "</p>";
    }
    return $retval;
}

 
/* 
* Main Function
*/

if (eregi('1.3.1',VERSION)) {
    $dqblockmenu = array(dqmenu);
    $display = COM_siteHeader($dqblockmenu);
} else {
    $display = COM_siteHeader();
}
$display .= COM_startBlock($LANG_DQ['indextitle']);

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqheader.thtml');
$T->set_var('header', $LANG_DQ00['plugin']);
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('plugin', 'dailyquote');

if (isset($_GET['msg'])) {
    $msg = "msg" . $_GET['msg'];
    $T->set_var('msg', $LANG_DQ[$msg]);
}
$T->set_var('indextitle', $LANG_DQ['addquote']);
$T->set_var('indexintro', $LANG_DQ['addintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

//display a row of pertinent links
if(!eregi('1.3.1',VERSION)){
    $display .= link_row();
}

if (isset($_POST['submit'])){
    $display .= single_add_quote();
}

$display .= add_quote_form();

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

$display .= COM_endBlock();
$display .= COM_siteFooter();
echo $display;

?>
