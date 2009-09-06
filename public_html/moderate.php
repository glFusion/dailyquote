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

require_once('../lib-common.php');

// Check user has rights to access this page
if (!SEC_hasRights('dailyquote.edit')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote moderation page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_DQ00['access_denied']);
    $display .= $LANG_DQ00['access_denied_msg1'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}


//displays the submissions to be moderated
function mod_display()
{
    global $_TABLES, $_CONF, $LANG_DQ;

    $sql = "SELECT 
            q.ID, q.Quotes, q.Quoted, q.Title, q.Source, q.Sourcedate, q.Date, q.UID, gl.username
        FROM 
            {$_TABLES['dailyquote_quotes']} q, 
            {$_TABLES['users']} gl
        WHERE 
            q.Status='0' 
        AND 
            q.UID = gl.uid";
    $result = DB_query($sql);
    if (!$result) {
        $retval .= $LANG_DQ['disperror'];
        COM_errorLog("An error occured while retrieving list of submissions",1);
    } else {
        //display quotes if any to display
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'dispquotesheader.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        $numrows = DB_numRows($result);
        if ($numrows>0) {
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'numresults.thtml');
            $T->set_var('numresults', $numrows);
            $T->set_var('numresultstxt', $LANG_DQ['nummodtxt']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));

            while ($row = DB_fetchArray($result)) {
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singleeditquote1.thtml');
                $T->set_var('site_url', $_CONF['site_url']);
                $T->set_var('action', 'moderate.php');
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singleeditquote2.thtml');
                $T->set_var('title', $LANG_DQ['title']);
                $T->set_var('disptitle', $row['Title']);
                $T->set_var('quotation', $LANG_DQ['quotation']);
                $T->set_var('dispquote', $row['Quotes']);
                $T->set_var('quoted', $LANG_DQ['quoted']);
                $T->set_var('dispquoted', $row['Quoted']);
                $T->set_var('source', $LANG_DQ['addsource']);
                $T->set_var('dispsource', $row['Source']);
                $T->set_var('sourcedate', $LANG_DQ['addsourcedate']);
                $T->set_var('dispsourcedate', $row['Sourcedate']);
                $T->set_var('dateformat', $LANG_DQ['srcdateformat']);
                $T->set_var('subm_by', $LANG_DQ['subm_by']);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));

                //get contribs
                $contribs = DB_query("SELECT UID, username FROM {$_TABLES['users']} ORDER BY username ASC");
                $numrows = DB_numRows($contribs);
                if ($numrows>0) {
                    while ($contribrow = DB_fetchArray($contribs)) {
                        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                        $T->set_file('page', 'contribs.thtml');
                        if($contribrow['UID'] == $row['UID']) {
                            $T->set_var('selected', ' SELECTED');
                        }
                        $T->set_var('dispcontr', $contribrow['username']);
                        $T->parse('output','page');
                        $retval .= $T->finish($T->get_var('output'));
                    }
                }

                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'contribs2.thtml');
                $T->set_var('datecontr', $row['Date']);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));

                //get cats for display
                $sql = "SELECT 
                        c.ID, c.Name, l.CID 
                    FROM 
                        {$_TABLES['dailyquote_cat']} c 
                    LEFT JOIN 
                        {$_TABLES['dailyquote_lookup']} l 
                    ON 
                        c.ID = l.CID 
                        AND 
                        l.QID = {$row['ID']} 
                    ORDER BY Name ASC";
                if (!$cats = DB_query($sql)) {
                    $errstatus = 1;
                } else {
                    $numrows = DB_numRows($cats);
                }
                if ($numrows > 0) {
                    $i = 0;
                    $colnum = 5;
                    $down = ceil($numrows/$colnum);
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'addcol.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                    while ($catrow = DB_fetchArray($cats)) {
                        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                        $T->set_file('page', 'catoption.thtml');
                            $sql = "SELECT 
                                    * 
                                FROM 
                                    {$_TABLES['dailyquote_lookup']} 
                                WHERE 
                                    CID = '{$catrow['ID']}' 
                                AND 
                                    Status='0' 
                                LIMIT 1";
                            if ($chkst = DB_query($sql)) {
                                if (DB_numRows($chkst) > 0) {
                                    $T->set_var('discat', ' color: gray;');
                                }
                            }
                        $T->set_var('catoption', $catrow['Name']);
                        if ($catrow['CID'] != NULL) {
                            $T->set_var('checked', 'CHECKED');
                        }
                        $T->parse('output','page');
                        $retval .= $T->finish($T->get_var('output'));
                        $i++;
                        if (($i % $down === 0) && ($i % $colnum !== 0)) {
                            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                            $T->set_file('page', 'addcol2.thtml');
                            $T->parse('output','page');
                            $retval .= $T->finish($T->get_var('output'));
                        }
                    }
                }

                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'modcats2.thtml');
                $T->set_var('newcat', $LANG_DQ['newcat']);
                $T->set_var('qid', $row['ID']);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
            }
        } else {
            // if moderation is finished
            $retval .= "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . 
                    $LANG_DQ['emptyqueue'] . "</p>";
        }
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'dispquotesfooter.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
    }

    return $retval;
}


//approves a single quote removing it from the queue
function app_quote($qid)
{
    global $_TABLES, $LANG_DQ;

    $errstatus = 0;
    if (!DB_query("UPDATE {$_TABLES['dailyquote_quotes']} SET Status='1' WHERE ID='$qid'")) {
        $errstatus = 1;
    } elseif (!$result = DB_query(
        "SELECT 
            CID 
        FROM 
            {$_TABLES['dailyquote_lookup']}, 
            {$_TABLES['dailyquote_cat']} c 
        WHERE 
            c.Status = '0' 
        AND 
            QID = '$qid'"
    )){
        $errstatus = 1;
    } else {
        list($cid) = DB_fetchArray($result);
    }
    if ($errstatus == 0) {
        if(!DB_query("UPDATE {$_TABLES['dailyquote_cat']} SET Status='1' WHERE ID='$cid'")) {
            $errstatus = 1;
        }
    }
    if ($errstatus == 1) {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['apperror'] . "</p>";
        COM_errorLog("An error occured while approving a quotation -- QID = $qid",1);
    } else {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['appsuccess'] . "</p>";
    }
    return $retval;
}


//deletes a single quote and related data
function del_quote($qid)
{
    global $_TABLES, $LANG_DQ;

    $errstatus = 0;
    if (!DB_query(
            "DELETE FROM 
                {$_TABLES['dailyquote_quotes']} 
            WHERE 
                ID=$qid 
            LIMIT 1"
    )) {
        $errstatus = 1;
    }
    if ($errstatus == 0) {
        if (!DB_query(
            "DELETE FROM 
                {$_TABLES['dailyquote_lookup']} 
            WHERE 
                QID = $qid"
        )) {
            $errstatus = 1;
        }
    }
    if($errstatus == 1) {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['delerror'] . "</p>";
        COM_errorLog("An error occured while deleting a quotation -- Q.ID = $qid",1);
    } else {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['delsuccess'] . "</p>";
    }
    return $retval;
}


//updates a single quote and related data
function upd_quote($qid, $quote, $quoted, $title, $source, $sourcedate, $contr, $catarray)
{
    global $_CONF, $_TABLES, $LANG_DQ;
    
    $title = slashctrl($title);
    $quote = slashctrl($quote);
    $quoted = slashctrl($quoted);
    $contr = slashctrl($contr);
    $source = slashctrl($source);
    $sourcedate = slashctrl($sourcedate);
    $st = "1";

    $sql = "UPDATE IGNORE 
            {$_TABLES['dailyquote_quotes']}
        SET 
            Quotes = '$quote', 
            Quoted = '$quoted', 
            Title = '$title', 
            Source = '$source', 
            Sourcedate = '$sourcedate', 
            Status = '$st'";
    
    $result = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE username='$contr'");
    list($uid) = DB_fetchArray($result);
    $sql .= ", UID='$uid'";
    $sql .= " WHERE ID='$qid'";

    //update the UID in lookup first if necessary
    if (!DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET UID='$uid' WHERE QID='$qid'")) {
        $errstatus = 1;
    }

    //updating quote and related info and queue status
    if ($errstatus == 0) {
        if (!DB_query($sql)) {
            $errstatus = 1;
        }
    }
    if ($errstatus == 0) {
        if (!$result = DB_query(
                "SELECT 
                    c.Name, c.ID 
                FROM 
                    {$_TABLES['dailyquote_cat']} c, 
                    {$_TABLES['dailyquote_lookup']} l 
                WHERE 
                    $qid = l.QID 
                AND 
                    c.ID = l.CID"
        )) {
            $errstatus = 1;
        } else {
            //updating category... adding, ignoring, or deleting
            while ($row = DB_fetchArray($result)) {
                foreach ($catarray as $val) {
                    if (($val != $row['Name']) && (!empty($val))) {
                        //if quote added to new category then add it to cat table and to lookup table
                        $val = slashctrl($val);
                        if (!DB_query(
                                "INSERT IGNORE INTO 
                                    {$_TABLES['dailyquote_cat']} 
                                SET 
                                    Name='$val', 
                                    Status=$st"
                        )) {
                            $errstatus = 1;
                        } elseif (mysql_affected_rows() > 0) {
                            //unique cat added
                            $cid = mysql_insert_id();
                            if (!DB_query(
                                "INSERT IGNORE INTO 
                                    {$_TABLES['dailyquote_lookup']} 
                                SET 
                                    QID = $qid, 
                                    CID = $cid, 
                                    UID = $uid, 
                                    Status = $st"
                            )) {
                                $errstatus = 1;
                            }
                        } elseif (mysql_affected_rows() == 0) {
                            //added quote to an existing category so add cat to lookup table using QID
                            if (!$catname = DB_query(
                                    "SELECT 
                                        ID, Status 
                                    FROM 
                                        {$_TABLES['dailyquote_cat']} 
                                    WHERE 
                                        Name = '$val'"
                            )) {
                                $errstatus = 1;
                            } else {
                                list($cid, $status) = DB_fetchArray($catname);
                                if (!DB_query("INSERT IGNORE into {$_TABLES['dailyquote_lookup']} SET QID=$qid, CID=$cid, UID=$uid, Status=$status")) {
                                    $errstatus = 1;
                                }
                            }
                        }
                    }
                }
                if (!in_array($row['Name'], $catarray)) {
                    //if cat deselected, delete from lookup table if entries > 1
                    if (!$rcnt = DB_query("SELECT * FROM {$_TABLES['dailyquote_lookup']} WHERE QID=$qid")) {
                        $errstatus = 1;
                    } elseif (DB_numRows($rcnt) > 1) {
                        if (!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE $qid=QID AND CID={$row['ID']}")){
                            $errstatus = 1;
                        }
                    } elseif (DB_numRows($rcnt) == 1) {
                        //do nothing
                        $errstatus = 3;
                    }
                }
            }
        }
    }
    if ($errstatus == 1) {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['upderror'] . "</p>";
        COM_errorLog("An error occured while updating a quotation",1);
    } elseif ($errstatus == 0) {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['updsuccess'] . "</p>";
        COM_errorLog("Update process complete.  Q.ID = $qid",1);
    } elseif ($errstatus == 3) {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['delcaterror'] . "</p>";
        COM_errorLog("Update process complete.  Q.ID = $qid",1);
    }
    return $retval;
}


/* 
* Main Function
*/

if(eregi('1.3.1',VERSION)) {
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
$T->set_var('indextitle', $LANG_DQ['moderatetitle']);
$T->set_var('indexintro', $LANG_DQ['moderateintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

if ($_POST['submit'] == "update and approve") {
    $qid = $_POST['qid'];
    $quote = $_POST['quote'];
    $quoted = $_POST['quoted'];
    $contr = $_POST['contr'];
    $catarray = $_POST['cat'];
    $display .= upd_quote($qid, $quote, $quoted, $title, $source, $sourcedate, $contr, $catarray);
}

if ($_POST['submit'] == "delete") {
    $qid = $_POST['qid'];
    $display .= del_quote($qid);
}

if ($_POST['submit'] == "approve") {
    $qid = $_POST['qid'];
    $display .= app_quote($qid);
}

//display a row of pertinent links
if(!eregi('1.3.1',VERSION)) {
    $display .= link_row();
}

$display .= mod_display();

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

$display .= COM_endBlock();
$display .= COM_siteFooter();
echo $display;

?>
