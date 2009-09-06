<?php

// +---------------------------------------------------------------------------+
// | Daily Quote Plugin v1.0.5 for Geeklog - The Ultimate Weblog                 |
// +---------------------------------------------------------------------------+
// | manage.php                                                                |
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

// Check user has rights to access this page
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

//displays the search results
function search_results($page, $qid = ''){
    global $_TABLES, $_CONF, $LANG_DQ;

    $sql = "SELECT DISTINCT 
            q.ID, q.Quotes, q.Quoted, q.Title, q.Source, 
            q.Sourcedate, q.Date, q.UID, gl.username
        FROM 
            {$_TABLES['dailyquote_quotes']} q, 
            {$_TABLES['dailyquote_lookup']} l, 
            {$_TABLES['users']} gl";
    if (!empty($_POST['categ'])) {
        $categ = slashctrl($_POST['categ']);
        $categsql = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='$categ'");
        list($catID) = DB_fetchArray($categsql);
        $catwh = " AND l.CID='$catID'";
    }
    $sql .= " WHERE (q.Status='1' AND q.UID = gl.UID AND l.QID = q.ID AND l.Status='1'";
    if(isset($catwh)){
        $sql .= $catwh;
    }

    $query = trim($_POST['keywords']);

    if ((empty($query)) && (empty($qid))){
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['searchmsg1'] . "</p>";
        return $retval;
    } elseif (!empty($qid)){
        $sql .= " AND ID='$qid'";
    }
    $sql .= ")";

    if (!empty($_POST['keytype'])) {
        $keytype = $_POST['keytype'];
        if ($keytype == "phrase"){//exact phrase as query
            $mysearchterm = slashctrl(trim ($query));
            $q = "(quotes like '%$mysearchterm%')";
            $pq = "(quoted like '%$mysearchterm%')";
            $c = "(username like '%$mysearchterm%')";
            $s = "(source like '%mysearchterm%')";
            $t = "(title like '%mysearchterm%')";
            if (!empty($_POST['type'])){
                $type = $_POST['type'];
                if ($type == "1"){//search within quotations
                    $sql .= " AND $q";
                } elseif ($type == "2"){//search within persons quoted
                    $sql .= " AND $pq";
                } elseif ($type == "4"){//search within contributor
                    $sql .= " AND $c";
                } elseif ($type == "5"){//search within source
                    $tmp .= " $s AND";
                } elseif ($type == "6"){//search within title
                    $tmp .= " $t AND";
                } else/*if ($type == "7")*/{//search in all of the above
                    $sql .= " AND ($q OR $pq OR $c OR $s OR $t)";
                }
            } else{//search in all of the above
                $sql .= " AND ($q OR $pq OR $c OR $s OR $t)";
            }

        } elseif ($keytype == "all") {//all the words as query
            $mywords = explode(' ', $query);
            $sql .= ' AND (';
            $tmp = '';
            foreach ($mywords AS $mysearchterm) {
                $mysearchterm = slashctrl(trim ($mysearchterm));
                if (!$mysearchterm == ""){
                    $q = "(Quotes like '%$mysearchterm%')";
                    $pq = "(Quoted like '%$mysearchterm%')";
                    $c = "(username like '%$mysearchterm%')";
                    $s = "(Source like '%mysearchterm%')";
                    $t = "(Title like '%mysearchterm%')";
                    if (!empty($_POST['type'])){
                        $type = $_POST['type'];
                        if ($type == "1"){//search within quotations
                            $tmp .= " $q AND";
                        } elseif ($type == "2"){//search within persons quoted
                            $tmp .= " $pq AND";
                        } elseif ($type == "4"){//search within contributor
                            $tmp .= " $c AND";
                        } elseif ($type == "5"){//search within source
                            $tmp .= " $s AND";
                        } elseif ($type == "6"){//search within title
                            $tmp .= " $t AND";
                        } else/*if ($type == "7")*/{//search in all of the above
                            $tmp .= " ($q OR $pq OR $c OR $s OR $t) AND";
                        }
                    } else{ //search in all of the above
                        $tmp .= " ($q OR $pq OR $c OR $s OR $t) AND";
                    }
                }
            }
            $tmp = substr($tmp, 0, strlen($tmp) - 4);
            $sql .= $tmp . ")";

        } else { //any of the words as query
            $mywords = explode(' ', $query);
            $sql .= ' AND (';
            $tmp = '';
            foreach ($mywords AS $mysearchterm) {
                $mysearchterm = slashctrl(trim ($mysearchterm));
                if (!$mysearchterm == ""){
                    $q = "(Quotes like '%$mysearchterm%')";
                    $pq = "(Quoted like '%$mysearchterm%')";
                    $c = "(username like '%$mysearchterm%')";
                    $s = "(Source like '%mysearchterm%')";
                    $t = "(Title like '%mysearchterm%')";
                    if (!empty($_POST['type'])){
                        $type = $_POST['type'];
                        if ($type == "1"){//search within quotations
                            $tmp .= " $q OR";
                        } elseif ($type == "2"){//search within persons quoted
                            $tmp .= " $pq OR";
                        } elseif ($type == "4"){//search within contributor
                            $tmp .= " $c OR";
                        } elseif ($type == "5"){//search within source
                            $tmp .= " $s OR";
                        } elseif ($type == "6"){//search within title
                            $tmp .= " $t OR";
                        } else/*if ($type == "7")*/{//search in all of the above
                            $tmp .= " ($q OR $pq OR $c OR $s OR $t) OR";
                        }
                    } else{//search in all of the above
                        $tmp .= " ($q OR $pq OR $c OR $s OR $t) OR";
                    }
                }
            }
            $tmp = substr($tmp, 0, strlen($tmp) - 3);
            $sql .= $tmp . ")";
        }
    }

    if (!empty($_POST['datestart'])){
        $start = $_POST['datestart'];
        $sql .= " AND q.dt_add >= '$start'";
    }
    if (!empty($_POST['dateend'])){
        $end = $_POST['dateend'];
        $sql .= " AND q.dt_add < '$end'";
    }

    if (!empty($_POST['contrib'])){
        $subm = slashctrl($_POST['contrib']);
        //$result = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE username='$subm'");
        //list($uid) = DB_fetchArray($result);
        $sql .= " AND username='$subm'";
    }

    // Retrieve results per page setting
    // This is on hold till i decide what to do with the base url for google paging.
    // My reason: too many POST values to send with GET IMO.
    // So for the time, all results are displayed.
    //$query = DB_query("SELECT searchdisplim AS displim FROM {$_TABLES['dailyquote_settings']}");
    //list( $displim) = DB_fetchArray($query);
    //$limit = ($displim * $page) - $displim;
    //$sql .= " LIMIT $limit, $displim";


    $result = DB_query($sql);
    if (!$result){
        $retval .= $LANG_DQ['disperror'];
        COM_errorLog("An error occured while retrieving list of quotes",1);
    }

    //display quotes if any to display
    else {
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'dispquotesheader.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        $numrows = DB_numRows($result);
        if ($numrows>0){
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'numresults.thtml');
            $T->set_var('numresults', $numrows);
            $T->set_var('numresultstxt', $LANG_DQ['numresultstxt']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            //$retval .= display_pagenav($qid, $show, $mode, $page, $numrows);

            while ($row = DB_fetchArray($result)){
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singleeditquote1.thtml');
                $T->set_var('site_url', $_CONF['site_url']);
                $T->set_var('action', 'manage.php');
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
                //pass search vars to action page
                while (list($key, $value) = each($_POST)){
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'searchvar.thtml');
                    $T->set_var('searchvar', '<input type="hidden" value="' . $value . '" name="' . $key . '">');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                }
                reset($_POST);
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singleeditquote2.thtml');
                $T->set_var('title', $LANG_DQ['title']);
                $T->set_var('disptitle', $row['title']);
                $T->set_var('quotation', $LANG_DQ['quotation']);
                $T->set_var('dispquote', $row['quotes']);
                $T->set_var('quoted', $LANG_DQ['quoted']);
                $T->set_var('dispquoted', $row['quoted']);
                $T->set_var('source', $LANG_DQ['addsource']);
                $T->set_var('dispsource', $row['source']);
                $T->set_var('sourcedate', $LANG_DQ['addsourcedate']);
                $T->set_var('dispsourcedate', $row['sourcedate']);
                $T->set_var('dateformat', $LANG_DQ['srcdateformat']);
                $T->set_var('subm_by', $LANG_DQ['subm_by']);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));

                //retrieve contributors from db
                $contribs = DB_query("SELECT uid, username FROM {$_TABLES['users']} ORDER BY username ASC");
                $numrows = DB_numRows($contribs);
                if ($numrows>0){
                    while ($contribrow = DB_fetchArray($contribs)){
                        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                        $T->set_file('page', 'contribs.thtml');
                        if($contribrow['uid'] == $row['uid']){
                            $T->set_var('selected', ' SELECTED');
                        }
                        $T->set_var('dispcontr', $contribrow['username']);
                        $T->parse('output','page');
                        $retval .= $T->finish($T->get_var('output'));
                    }
                }

                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'contribs2.thtml');
                $T->set_var('datecontr', $row['dt_add']);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
                //get cats for display
                if(!$cats = DB_query("SELECT 
                            c.ID, c.Name, l.CID FROM 
                            {$_TABLES['dailyquote_cat']} c 
                        LEFT JOIN 
                            {$_TABLES['dailyquote_lookup']} l 
                        ON 
                            c.ID = l.CID 
                        AND 
                            l.QID = {$row['ID']} 
                        ORDER BY name ASC")) {
                    $errstatus = 1;
                } else {
                    $numrows = DB_numRows($cats);
                }
                if($numrows > 0){
                    $i = 0;
                    $colnum = 5;
                    $down = ceil($numrows/$colnum);
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'addcol.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                    while ($catrow = DB_fetchArray($cats)){
                        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                        $T->set_file('page', 'catoption.thtml');
                        if($chkst = DB_query("SELECT 
                                    * 
                                FROM 
                                    {$_TABLES['dailyquote_lookup']} 
                                WHERE 
                                    cid = '{$catrow['catid']}' 
                                AND 
                                    status = '0' 
                                LIMIT 1")) {
                            if(DB_numRows($chkst) > 0) {
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
                        if ($i % $down === 0 && $i % $colnum !== 0) {
                            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                            $T->set_file('page', 'addcol2.thtml');
                            $T->parse('output','page');
                            $retval .= $T->finish($T->get_var('output'));
                        }
                    }
                }

                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'cats2.thtml');
                $T->set_var('newcat', $LANG_DQ['newcat']);
                $T->set_var('qid', $row['ID']);
                $T->set_var('update', $LANG_DQ['update']);
                $T->set_var('delete', $LANG_DQ['delete']);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
            }
            //$retval .= display_pagenav($qid, $show, $mode, $page, $numrows);
        }
        else {
            $retval .= "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['searchmsg2'] . "</p>";
        }
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'dispquotesfooter.thtml');
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
    }
    return $retval;
}

//displays the search form
function search_form(){
    global $_TABLES, $_CONF, $LANG_DQ;

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'manageform.thtml');
    $T ->set_var('site_url', $_CONF['site_url']);
    $T ->set_var('searchlabel', $LANG_DQ['searchlabel']);
    $T ->set_var('keyphrase', $LANG_DQ['keyphrase']);
    $T ->set_var('keyall', $LANG_DQ['keyall']);
    $T ->set_var('keyany', $LANG_DQ['keyany']);
    $T ->set_var('datestart', $LANG_DQ['date']);
    $T ->set_var('enddate', $LANG_DQ['enddate']);
    $T ->set_var('dateformat', $LANG_DQ['dateformat']);
    $T ->set_var('type', $LANG_DQ['type']);
    $T ->set_var('sortopt1', $LANG_DQ['sortopt1']);
    $T ->set_var('sortopt2', $LANG_DQ['sortopt2']);
    $T ->set_var('sortopt4', $LANG_DQ['sortopt4']);
    $T ->set_var('sortopt5', $LANG_DQ['sortopt5']);
    $T ->set_var('sortopt6', $LANG_DQ['sortopt6']);
    $T ->set_var('sortopt7', $LANG_DQ['sortopt7']);
    $T ->set_var('limit', $LANG_DQ['limit']);
    $T ->parse('output','page');
    $retval = $T->finish($T->get_var('output'));

    //retrieve contributors from db
    $result = DB_query("SELECT UID FROM {$_TABLES['dailyquote_quotes']} WHERE Status='1' GROUP BY UID ASC");
    $numrows = DB_numRows($result);
    if ($numrows>0){
        while ($row = DB_fetchArray($result)){
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T ->set_file('page', 'searchcontropt.thtml');
            $subm = DB_query("SELECT username FROM {$_TABLES['users']} WHERE uid={$row['UID']}");
            list($username) = DB_fetchArray($subm);
            $T->set_var('contr', $username);
            $T ->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
        }
    }

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'searchcontropt2.thtml');
    $T ->set_var('sortopt3', $LANG_DQ['sortopt3']);
    $T ->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    //retrieve categories from db
    $result = DB_query("Select DISTINCT Name FROM {$_TABLES['dailyquote_cat']} c, {$_TABLES['dailyquote_lookup']} l WHERE ID=l.CID AND l.Status='1' ORDER BY Name");
    $numrows = DB_numRows($result);
    if ($numrows>0){
        while ($row = DB_fetchArray($result)){
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T ->set_file('page', 'searchcatopt.thtml');
            $T->set_var('catoption', $row['Name']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
        }
    }

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'searchcatopt2.thtml');
    $T ->set_var('submit', $LANG_DQ['go']);
    $T ->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));
    return $retval;
}

//deletes a single quote and related data
function del_quote($qid){
    global $_TABLES, $LANG_DQ;

    $errstatus = 0;
    if (!DB_query("DELETE FROM {$_TABLES['dailyquote_quotes']} WHERE {$_TABLES['dailyquote_quotes']}.ID=$qid LIMIT 1")){
        $errstatus = 1;
    }
    if($errstatus == 0){
        if (!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE {$_TABLES['dailyquote_lookup']}.QID=$qid")){
            $errstatus = 1;
        }
    }
    if($errstatus == 1){
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['delerror'] . "</p>";
        COM_errorLog("An error occured while deleting a quotation -- Q.ID = $qid",1);
    } else {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['delsuccess'] . "</p>";
    }
    
    return $retval;
}

//updates a single quote and related data
function upd_quote($qid){
    global $_CONF, $_TABLES, $LANG_DQ, $_POST;
    
    $title = slashctrl($_POST['title']);
    $quote = slashctrl($_POST['quote']);
    $quoted = slashctrl($_POST['quoted']);
    $contr = slashctrl($_POST['contr']);
    $source = slashctrl($_POST['source']);
    $sourcedate = slashctrl($_POST['sourcedate']);
    $catarray = $_POST['cat'];
    $st = "1";

    $sql = "UPDATE IGNORE {$_TABLES['dailyquote_quotes']}";
    $sql .= " SET Quotes='$quote', Quoted='$quoted', Title='$title', Source='$source', Sourcedate='$sourcedate'";
    
    $result = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE username='$contr'");
    list($uid) = DB_fetchArray($result);
    $sql .= ", UID='$uid'";
    $sql .= " WHERE ID='$qid'";

    //update the UID in lookup first if necessary
    if (!DB_query("UPDATE IGNORE {$_TABLES['dailyquote_lookup']} SET UID='$uid' WHERE QID='$qid'")){
        $errstatus = 1;
    }
    //updating quote and related info
    if ($errstatus == 0){
        if (!DB_query($sql)){
            $errstatus = 1;
        }
    }
    if ($errstatus == 0){
        if (!$result = DB_query("SELECT c.Name, c.ID FROM {$_TABLES['dailyquote_cat']} c, {$_TABLES['dailyquote_lookup']} l WHERE $qid=l.QID AND c.ID=l.CID")){
            $errstatus = 1;
        } else {//updating category... adding, ignoring, or deleting
            while ($row = DB_fetchArray($result)){
                foreach ($catarray as $val){
                    if (($val != $row['Name']) && (!empty($val))){//if quote added to new category then add it to cat table and to lookup table
                        $val = slashctrl($val);
                        if (!DB_query("INSERT IGNORE into {$_TABLES['dailyquote_cat']} SET Name='$val', Status=$st")){
                            $errstatus = 1;
                        }
                        elseif (mysql_affected_rows() > 0){//unique cat added
                            $cid = mysql_insert_id();
                            if (!DB_query("INSERT IGNORE into {$_TABLES['dailyquote_lookup']} SET QID=$qid, CID=$cid, UID=$uid, Status=$st")){
                                $errstatus = 1;
                            }
                        }
                        elseif (mysql_affected_rows() == 0) {//added quote to an existing category so add cat to lookup table using QID
                            if (!$catname = DB_query("SELECT ID, Status FROM {$_TABLES['dailyquote_cat']} WHERE Name='$val'")){
                                $errstatus = 1;
                            } else {
                                list($cid, $status) = DB_fetchArray($catname);
                                if (!DB_query("INSERT IGNORE into {$_TABLES['dailyquote_lookup']} SET QID=$qid, CID=$cid, UID=$uid, Status=$status")){
                                    $errstatus = 1;
                                }
                            }
                        }
                    }
                }
                if (!in_array($row['Name'], $catarray)){//if cat deselected, delete from lookup table if entries > 1
                    if (!$rcnt = DB_query("SELECT * FROM {$_TABLES['dailyquote_lookup']} WHERE QID=$qid")){
                        $errstatus = 1;
                    } elseif (DB_numRows($rcnt) > 1){
                        if (!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE $qid=QID AND CID={$row['ID']}")){
                            $errstatus = 1;
                        }
                    } elseif (DB_numRows($rcnt) == 1){
                        //do nothing
                        $errstatus = 3;
                    }
                }
            }
        }
    }
    if ($errstatus == 1){
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['upderror'] . "</p>";
        COM_errorLog("An error occured while updating a quotation",1);
    } elseif ($errstatus == 0){
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['updsuccess'] . "</p>";
        COM_errorLog("Update process complete.  Q.ID = $qid",1);
    } elseif ($errstatus == 3){
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['delcaterror'] . "</p>";
        COM_errorLog("Update process complete.  Q.ID = $qid",1);
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
$T->set_var('indextitle', $LANG_DQ['managetitle']);
$T->set_var('indexintro', $LANG_DQ['manageintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

//display a row of pertinent links
if(!eregi('1.3.1',VERSION)){
    $display .= link_row();
}

$display .= search_form();

if (empty($_GET['page'])){
    $page = 1;
}
if ($_POST['submit'] == "Update"){
    $qid = $_POST['qid'];
    $display .= upd_quote($qid);
    $display .= search_results($page, $qid);
}
elseif ($_POST['submit'] == "Delete"){
    $qid = $_POST['qid'];
    $display .= del_quote($qid);
    $display .= search_results($page, $qid);
}
elseif ($_POST['submit'] == "GO"){
    $display .= search_results($page);
}
elseif (isset($_GET['qid'])){
    $qid = $_GET['qid'];
    $display .= search_results($page, $qid);
}

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

$display .= COM_endBlock();
$display .= COM_siteFooter();
echo $display;
?>
