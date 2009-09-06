<?php

// +---------------------------------------------------------------------------+
// | Daily Quote Plugin v1.0.5 for Geeklog - The Ultimate Weblog               |
// +---------------------------------------------------------------------------+
// | catman.php                                                                |
// |                                                                           |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2004 by the following authors:                              |
// |                                                                           |
// | Author: Alf Deeley aka machinari - ajdeeley@summitpages.ca                |
// | Constructed with the Universal Plugin                                     |
// | Copyright (C) 2002 by the following authors:                              |
// | Tom Willett                 -    twillett@users.sourceforge.net           |
// | Blaine Lang                 -    langmail@sympatico.ca                    |
// | The Universal Plugin is based on prior work by:                           |
// | Tony Bibbs                  -    tony@tonybibbs.com                       |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+
//

require_once('../lib-common.php');

// Retrieve access settings
if (!SEC_hasRights('dailyquote.edit')) {
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


//displays the category listing
function catforms(){
    global $_TABLES, $_CONF, $LANG_DQ;

    if (!$result = DB_query("SELECT * FROM {$_TABLES['dailyquote_cat']} ORDER BY Name ASC")){
        $retval = $LANG_DQ['caterror'];
        COM_errorLog("An error occured while retrieving list of categories",1);
    } else {//display cats if any to display
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'mancatsheader.thtml');
        $T->set_var('categories', $LANG_DQ['categories']);
        $T->set_var('site_url', $_CONF['site_url']);
        $T->set_var('enableall', $LANG_DQ['enableall']);
        $T->set_var('disableall', $LANG_DQ['disableall']);
        $T->set_var('beware', $LANG_DQ['beware']);
        $T->set_var('deleteall', $LANG_DQ['deleteall']);
        $T->set_var('instr', $LANG_DQ['catinstr2']);
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        $i = 0;

        //display vertical columns -- 2 cats per column = $colnum.
        //if you increase this number, you need to adjust the cell width in the .thtml file.
        //I suggest you don't increase it unless your display is fairly wide.
        $numrows = DB_numRows($result);
        if ($numrows>0){
            $colnum = 2;
            $rownum = 5;//number of cats per column per column-row-set.
            $col = $rownum * $colnum;
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'catformcolheader.thtml');
            $T->set_var('category', $LANG_DQ['editcat']);
            $T->set_var('enable', $LANG_DQ['enableq']);
            $T->set_var('mdelete', $LANG_DQ['mark']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            while ($row = DB_fetchArray($result)){
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singlecatform.thtml');
                $T->set_var('name', $row['Name']);
                $T->set_var('yes', $LANG_DQ['yes']);
                $T->set_var('no', $LANG_DQ['no']);
                if (!$chkst = DB_query("SELECT Status FROM {$_TABLES['dailyquote_lookup']} WHERE CID='{$row['ID']}'")){
                    $errstatus = 1;
                } else {
                    $e = DB_fetchArray($chkst);
                    if ((DB_numRows($chkst) == 0) || ($e['Status'] == '0')){
                        $T->set_var('enabledn', 'CHECKED');
                    } else {
                        $T->set_var('enabledy', 'CHECKED');
                    }
                }
                $T->set_var('cid', $row['ID']);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
                $i++;
                if (($i % $rownum === 0) && ($i % $col !== 0) && ($i != $numrows)){
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'catformcolfooter.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'catformcolheader.thtml');
                    $T->set_var('category', $LANG_DQ['editcat']);
                    $T->set_var('enable', $LANG_DQ['enableq']);
                    $T->set_var('mdelete', $LANG_DQ['mark']);
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                }
                if (($i % $col === 0) && ($i != $numrows)){
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'catformcolfooter.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'catformsetfooter.thtml');
                    $T->set_var('update', $LANG_DQ['mupdate']);
                    $T->set_var('delete', $LANG_DQ['mdelete']);
                    $T->set_var('reset', $LANG_DQ['reset']);
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'catformsetheader.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'catformcolheader.thtml');
                    $T->set_var('category', $LANG_DQ['editcat']);
                    $T->set_var('enable', $LANG_DQ['enableq']);
                    $T->set_var('mdelete', $LANG_DQ['mark']);
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                }
            }
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'catformcolfooter.thtml');
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'catformsetfooter.thtml');
            $T->set_var('update', $LANG_DQ['mupdate']);
            $T->set_var('delete', $LANG_DQ['mdelete']);
            $T->set_var('reset', $LANG_DQ['reset']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
        }
        else $retval .= "<p align=\"center\">".$LANG_DQ['StatsMsg2']."</p>";
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'mancatsfooter.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
    }
    return $retval;
}

//displays the category add form
function add_catform(){
    global $LANG_DQ, $_CONF, $_POST;

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'cataddform.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('title', $LANG_DQ['cataddtitle']);
    $T->set_var('instr', $LANG_DQ['catinstr']);
    $T->set_var('add', $LANG_DQ['addcat']);
    //$T->set_var('enable', $LANG_DQ['catenable']);
    //$T->set_var('yes', $LANG_DQ['yes']);
    //$T->set_var('no', $LANG_DQ['no']);
    $T->set_var('submit', $LANG_DQ['submitcat']);
    $T->parse('output','page');
    $retval = $T->finish($T->get_var('output'));
    return $retval;
}

//enables or disables all cats at once
function enableall($new){
    global $_TABLES, $LANG_DQ, $_CONF;

    if($new == '1'){
        $old = '0';
    } elseif ($new == '0'){
        $old = '1';
    }
    if(!DB_query("UPDATE {$_TABLES['dailyquote_lookup']} SET Status='$new' WHERE Status='$old'")){
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['allsterr'] . '</p>';
        COM_errorLog("An error occured while modifying category status",1);
    } else {
        $retval = '<p align="center" style="font-weight: bold; color: red;">';
        if($new == '1'){
            $retval .= $LANG_DQ['allstsuc1'] . '</p>';
        } elseif ($new == '0'){
            $retval .= $LANG_DQ['allstsuc2'] . '</p>';
        }
    }
    return $retval;
}

//deletes all categories putting quotes to miscellany
function deleteall(){
//make this one look like del_cat, but not limited to a single CID
    global $_TABLES, $LANG_DQ, $_CONF;

    $errstatus = 0;
    //using the qid, check count in lookup table where cid=$cid (group by qid)
    if(!$qresult = DB_query("SELECT QID FROM {$_TABLES['dailyquote_lookup']} GROUP BY QID")){
        $errstatus = 1;
    } elseif (DB_numRows($qresult) > 0){
        while ($qrow = DB_fetchArray($qresult)){//loop thru each QID modifying CID as necessary
            if(!$cresult = DB_query("SELECT COUNT(*) AS count FROM {$_TABLES['dailyquote_lookup']} WHERE QID='{$qrow['QID']}'")){
                $errstatus = 1;
            } else {
                list($count) = DB_fetchArray($cresult);
                if ($count == 1){//quotes belonging only to this cat--rename cat to misc
                    if (!$chkmisc = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='{$LANG_DQ['misc']}'")){
                        $errstatus = 1;
                    } elseif (DB_numRows($chkmisc) > 0){
                        list($miscid) = DB_fetchArray($chkmisc);
                        //reassign CID in lookup table
                        if(!DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET CID='{$miscid}' WHERE QID='{$qrow['QID']}' AND CID<>'$miscid'")){
                            $errstatus = 1;
                        }
                    } elseif (DB_numRows($chkmisc) == 0){
                        //create misc cat
                        if (!DB_query("INSERT INTO {$_TABLES['dailyquote_cat']} SET Name='{$LANG_DQ['misc']}', Status='1'")){
                            $errstatus = 1;
                        } else {//assign newly created miscid to QID
                            $miscid = mysql_insert_id();
                            if(!$altresult = DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET CID='{$miscid}' WHERE QID='{$qrow['QID']}'")){
                                $errstatus = 1;
                            }
                        }
                    }
                } elseif ($count > 1){//quotes belonging to >1 cats--del cat only if not miscid
                    if (!$chkmisc = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='{$LANG_DQ['misc']}'")){
                        $errstatus = 1;
                    } elseif (DB_numRows($chkmisc) > 0){
                        list($miscid) = DB_fetchArray($chkmisc);
                        //delete all but one entry per QID
                        $limit = $count - 1;
                        if(!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE QID='{$qrow['QID']}' LIMIT $limit")){
                            $errstatus = 1;
                        }
                        //reassign last entry to $miscid
                        elseif(!DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET CID='{$miscid}' WHERE QID='{$qrow['QID']}' AND CID<>'$miscid'")){
                            $errstatus = 1;
                        }
                    } elseif (DB_numRows($chkmisc) == 0){
                        //create misc cat
                        if (!DB_query("INSERT INTO {$_TABLES['dailyquote_cat']} SET Name='{$LANG_DQ['misc']}', Status='1'")){
                            $errstatus = 1;
                        } else {//assign newly created miscid to QID
                            $miscid = mysql_insert_id();
                            //delete all but one entry
                            $limit = $count - 1;
                            if(!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE QID='{$qrow['QID']}' LIMIT $limit")){
                                $errstatus = 1;
                            }
                            //reassign last entry to $miscid
                            if(!$altresult = DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET CID='{$miscid}' WHERE QID='{$qrow['QID']}' AND CID<>'$miscid'")){
                                $errstatus = 1;
                            }
                        }
                    }
                }
                //now delete from cat table
                if (!$chkmisc = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='{$LANG_DQ['misc']}'")){
                    $errstatus = 1;
                } elseif (DB_numRows($chkmisc) > 0){
                    list($miscid) = DB_fetchArray($chkmisc);
                    if (!$dcresult = DB_query("DELETE FROM {$_TABLES['dailyquote_cat']} WHERE ID<>'$miscid'")){
                        $errstatus = 1;
                    }
                } elseif (DB_numRows($chkmisc) == 0){
                    if (!$dcresult = DB_query("DELETE FROM {$_TABLES['dailyquote_cat']}")){
                        $errstatus = 1;
                    }
                }
            }
        }
    }
    else {
        if(!DB_query("DELETE FROM {$_TABLES['dailyquote_cat']}")){
            $errstatus = 1;
        }
    }
    if($errstatus == 1){
        $retval .= '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['delcaterr'] . '</p>';
        COM_errorLog("An error occured while adding a category -- $cat",1);
    } elseif ($errstatus == 0) {
        $retval .= '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['delcatsuc'] . '</p>';
    }
    return $retval;
}

//adds a category to the database
function add_cat(){
    global $_TABLES, $LANG_DQ, $_CONF, $_POST;

    $cat = slashctrl(strip_tags(COM_checkWords(trim ($_POST['addcat']))));
    if (empty($cat)){
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['emptycat'] . '</p>';
        return $retval;
    }
    $errstatus = 0;
    //checking for duplicate entry
    if(!$result = DB_query("SELECT * FROM {$_TABLES['dailyquote_cat']} WHERE Name='$cat'")){
        $errstatus = 1;
    } elseif (DB_numRows($result) > 0){
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['dupcat'] . '</p>';
        return $retval;
    } else {
        if(!$result = DB_query("INSERT IGNORE into {$_TABLES['dailyquote_cat']} SET Name='$cat', Status='1'")){
            $errstatus = 1;
        }
    }
    if($errstatus == 1){
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['addcaterr'] . '</p>';
        COM_errorLog("An error occured while adding a category -- $cat",1);
    } else {
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['addcatsuc'] . '</p>';
    }
    return $retval;
}

//deletes the category
function del_cat(){
    global $_TABLES, $LANG_DQ, $_CONF, $_POST;

    $catA = $_POST['catid'];
    $errlog = 0;
    foreach ($catA as $cid){
        $posted = 'delete' . $cid;
        if (!empty($_POST[$posted])){
            //using the qid, check count in lookup table where cid=$cid (group by qid)
            if(!$qresult = DB_query("SELECT QID FROM {$_TABLES['dailyquote_lookup']} WHERE CID='$cid'")){
                $errlog++;
                COM_errorLog("Could not query database while deleting category {$cid}",1);
            } elseif (DB_numRows($qresult) > 0){
                while ($qrow = DB_fetchArray($qresult)){
                    if(!$cresult = DB_query("SELECT COUNT(*) AS count FROM {$_TABLES['dailyquote_lookup']} WHERE QID='{$qrow['QID']}'")){
                        $errlog++;
                        COM_errorLog("Could not query database while deleting category {$cid}",1);
                    } else {
                        list($count) = DB_fetchArray($cresult);
                        if ($count == 1){
                            //quotes belonging only to this cat--rename cat to misc
                            if (!$chkmisc = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='{$LANG_DQ['misc']}'")){
                                $errlog++;
                                COM_errorLog("Could not query database while deleting category {$cid}",1);
                            } elseif (DB_numRows($chkmisc) > 0){
                                list($miscid) = DB_fetchArray($chkmisc);
                                //reassign CID in lookup table
                                if ($miscid != $cid){
                                    if(!$altresult = DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET CID='{$miscid}' WHERE QID='{$qrow['QID']}' AND CID='$cid'")){
                                        $errlog++;
                                        COM_errorLog("Could not query database while deleting category {$cid}",1);
                                    }
                                } else {
                                    $errlog++;
                                    COM_errorLog("Error 1 -- You are not permitted to delete the {$LANG_DQ['misc']} category.",1);
                                }
                            } elseif (DB_numRows($chkmisc) == 0){
                                //create misc cat
                                if (!$crmisc = DB_query("INSERT INTO {$_TABLES['dailyquote_cat']} SET Name='{$LANG_DQ['misc']}', Status='1'")){
                                    $errlog++;
                                    COM_errorLog("Could not query database while deleting category {$cid}",1);
                                } else {
                                    $miscid = mysql_insert_id();
                                    if(!$altresult = DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET CID='{$miscid}' WHERE QID='{$qrow['QID']}' AND CID='$cid'")){
                                        $errlog++;
                                        COM_errorLog("Could not query database while deleting category {$cid}",1);
                                    }
                                }
                            }
                        } elseif ($count > 1){
                            //quotes belonging to >1 cats--del cat
                            if (!$dlresult = DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE CID='$cid' AND QID='{$qrow['QID']}' LIMIT 1")){
                                $errlog++;
                                COM_errorLog("Could not query database while deleting category {$cid}",1);
                            }
                        }
                        if (!$dcresult = DB_query("DELETE FROM {$_TABLES['dailyquote_cat']} WHERE ID='$cid' LIMIT 1")){
                            $errlog++;
                            COM_errorLog("Could not query database while deleting category {$cid}",1);
                        }
                    }
                }
            } else {
                if(!$dcresult = DB_query("DELETE FROM {$_TABLES['dailyquote_cat']} WHERE ID='$cid' LIMIT 1")){
                    $errlog++;
                    COM_errorLog("Could not query database while deleting category {$cid}",1);
                }
            }
        }
    }
    if ($errlog > 0){
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['delcaterr'] . '</p>';
    } else {
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['delcatsuc'] . '</p>';
    }
    return $retval;
}

//updates the edited category
function upd_cat(){
    global $_TABLES, $LANG_DQ, $_CONF, $_POST;

    $errlog = 0;
    $catA = $_POST['catid'];
    foreach ($catA as $cid){
        $postname = 'editcat' . $cid;
        $newname = $_POST[$postname];
        $postenable = 'enable' . $cid;
        $cat = slashctrl(strip_tags(COM_checkWords(trim($newname))));

        //getting vars for later
        if(!$query1 = DB_query("SELECT l.Status FROM {$_TABLES['dailyquote_lookup']} l, {$_TABLES['dailyquote_cat']} c WHERE c.Name='$cat' AND c.ID=l.CID LIMIT 1")){
            $errlog++;
            COM_errorLog("Could not query database while updating category {$cid}",1);
        } else {
            list($status) = DB_fetchArray($query1);
        }
        if(!$query4 = DB_query("SELECT Status FROM {$_TABLES['dailyquote_lookup']} WHERE CID='$cid' LIMIT 1")){
            $errlog++;
            COM_errorLog("Could not query database while updating category {$cid}",1);
        } else {
            list($currstatus) = DB_fetchArray($query4);
        }
        if(!$query5 = DB_query("SELECT Name FROM {$_TABLES['dailyquote_cat']} WHERE ID='$cid' LIMIT 1")){
            $errlog++;
            COM_errorLog("Could not query database while updating category {$cid}",1);
        } else {
            list($currname) = DB_fetchArray($query5);
        }
        if(!$query2 = DB_query("SELECT ID, Name FROM {$_TABLES['dailyquote_cat']} WHERE Name='$cat' LIMIT 1")){
            $errlog++;
            COM_errorLog("Could not query database while updating category {$cid}",1);
        } else {
            list($id,$name) = DB_fetchArray($query2);
        }
        if(!$query3 = DB_query("SELECT ID, Name FROM {$_TABLES['dailyquote_cat']} WHERE Name='{$LANG_DQ['misc']}' LIMIT 1")){
            $errlog++;
            COM_errorLog("Could not query database while updating category {$cid}",1);
        } else {
            list($miscid, $miscat) = DB_fetchArray($query3);
        }

        //if posted category name field is empty
        if (empty($cat)){
            $errlog++;
            COM_errorLog("Error 1 -- " . sprintf($LANG_DQ['usedel'], $currname),1);
        }
        //new cat exists and status is unchanged
        elseif ((DB_numRows($query1) == 1) && ($cat != $currname) && ($_POST[$postenable] == $currstatus)){
            //found dup entry
            $errlog++;
            COM_errorLog("Error 2 -- " . sprintf($LANG_DQ['updduperr'], $name),1);
        }

        //new cat exists and status is changed
        elseif ((DB_numRows($query1) == 1) && ($_POST[$postenable] != $currstatus)){
            //change enabled status
            if (!DB_query("UPDATE {$_TABLES['dailyquote_lookup']} SET Status='{$_POST[$postenable]}' WHERE CID='$cid'")){
                $errlog++;
                COM_errorLog("Could not query database while updating category {$cid}",1);
            }
            //found dup entry
            elseif ($id != $cid){
                $errlog++;
                COM_errorLog("Error 3 -- " . sprintf($LANG_DQ['updduperr'], $name),1);
            }
        }

        //new cat exists but is not active--status remains 0
        elseif ((!empty($name) && (DB_numRows($query1) == 0))){
            //change cat, but ensure misc cat remains intact
            if (DB_numRows($query3) == 1){
                if (($cid == $miscid) && ($cat != $miscat)){
                    $errlog++;
                    COM_errorLog("Error 4 -- " . sprintf($LANG_DQ['updmiscerr'], $miscat),1);
                } elseif ($_POST[$postenable] != 0){
                    //cannot enable an inactive cat
                    $errlog++;
                    COM_errorLog("Error 5 -- " . sprintf($LANG_DQ['updsterr'], $cid),1);
                } elseif (($cid != $miscid) && (($cat == $miscat) || ($cat == $name))){
                    //cat already exists
                    $errlog++;
                    COM_errorLog("Error 6 -- " . sprintf($LANG_DQ['updduperr'], $name),1);
                } elseif ($cid != $miscid){
                    //update cat table
                    if (!DB_query("UPDATE {$_TABLES['dailyquote_cat']} SET Name='$cat' WHERE ID='$cid'")){
                        $errlog++;
                        COM_errorLog("Could not query database while updating category {$cid}",1);
                    }
                }
            }
            //cat exists, misc cat doesn't, safe to change cat
            elseif (DB_numRows($query3) == 0){
                if (!DB_query("UPDATE {$_TABLES['dailyquote_cat']} SET Name='$cat' WHERE ID='$cid'")){
                    $errlog++;
                    COM_errorLog("Could not query database while updating category {$cid}",1);
                }
            }
        }

        //new cat doesn't exist and status is unchanged
        elseif (empty($name) && ($_POST[$postenable] == $currstatus)) {
            //change cat, but ensure misc cat remains intact
            if ($cid == $miscid){
                //cannot change the misc cat
                $errlog++;
                COM_errorLog("Error 7 -- " . sprintf($LANG_DQ['updmiscerr'], $miscat),1);
            } elseif ($cid != $miscid){
                //change cat
                if (!DB_query("UPDATE {$_TABLES['dailyquote_cat']} SET Name='$cat' WHERE ID='$cid'")){
                    $errlog++;
                    COM_errorLog("Could not query database while updating category {$cid}",1);
                }
            }
        }

        //new cat doesn't exist and status is changed
        elseif (empty($name) && ($_POST[$postenable] != $currstatus)) {
            //change cat, but ensure misc cat remains intact
            if ($cid == $miscid){
                //cannot change the misc cat
                $errlog++;
                COM_errorLog("Error 8 -- " . sprintf($LANG_DQ['updmiscerr'], $miscat),1);
                //change status
                if (!DB_query("UPDATE {$_TABLES['dailyquote_lookup']} SET Status='{$_POST[$postenable]}' WHERE CID='$cid'")){
                    $errlog++;
                    COM_errorLog("Could not query database while updating category {$cid}",1);
                }
            } elseif ($cid != $miscid){
                //change cat
                if (!DB_query("UPDATE {$_TABLES['dailyquote_cat']} SET Name='$cat' WHERE ID='$cid'")){
                    $errlog++;
                    COM_errorLog("Could not query database while updating category {$cid}",1);
                }
                //change status
                elseif (!DB_query("UPDATE {$_TABLES['dailyquote_lookup']} SET Status='{$_POST[$postenable]}' WHERE CID='$cid'")){
                    $errlog++;
                    COM_errorLog("Could not query database while updating category {$cid}",1);
                }
            }
        }
    }
    //end foreach
    //error/success messages
    if($errlog > 0){
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['updcaterr'] . '</p>';
    } else {
        $retval = '<p align="center" style="font-weight: bold; color: red;">' . $LANG_DQ['updcatsuc'] . '</p>';
    }

    return $retval;
}



/* 
* Main Function
*/

if(eregi('1.3.1',VERSION)){
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

if (isset($_GET['msg'])){
    $msg = "msg" . $_GET['msg'];
    $T->set_var('msg', $LANG_DQ[$msg]);
}

$T->set_var('indextitle', $LANG_DQ['catmantitle']);
$T->set_var('indexintro', $LANG_DQ['catmanintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

if (isset($_POST['add'])){
    $display .= add_cat();
} elseif (isset($_POST['update'])){
    $display .= upd_cat();
} elseif (isset($_POST['delete'])){
    $display .= del_cat();
} elseif (isset($_POST['enableall'])){
    $display .= enableall(1);
} elseif (isset($_POST['disableall'])){
    $display .= enableall(0);
} elseif (isset($_POST['deleteall'])){
    $display .= deleteall();
}

//display a row of pertinent links
if(!eregi('1.3.1',VERSION)){
    $display .= link_row();
}

//display the add cat form
$display .= add_catform();

//display category listing
$display .= catforms();

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));
$display .= COM_endBlock();
$display .= COM_siteFooter();
echo $display;
?>
