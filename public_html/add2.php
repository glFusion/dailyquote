<?php

// +---------------------------------------------------------------------------+
// | Daily Quote Plugin v1.0.5 for Geeklog - The Ultimate Weblog               |
// +---------------------------------------------------------------------------+
// | add2.php                                                                  |
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
$query = DB_query("SELECT loginbatch FROM {$_TABLES['dailyquote_settings']}");
list($loginbatch) = DB_fetchArray($query);
// Check user has rights to access this page
if (($_USER['uid'] < 2) || (($loginbatch == '0') && (!SEC_hasRights('dailyquote.edit')))){
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

//displays the batch add form
function batch_add_form(){
    global $_TABLES, $_CONF, $_USER, $LANG_DQ;

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'addformheader.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'batchaddform.thtml');
    $T->set_var('batchaddtitle', $LANG_DQ['batchaddtitle']);
    $T->set_var('batchadd', $LANG_DQ['batchadd']);
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('cat', $LANG_DQ['cat']);
    $T->set_var('catinstr', $LANG_DQ['batchcatinstr']);
    $T->set_var('srcinstr', $LANG_DQ['batchsrcinstr']);
    $T->set_var('addtitle', $LANG_DQ['addtitle']);
    $T->set_var('addsource', $LANG_DQ['addsource']);
    $T->set_var('addsourcedate', $LANG_DQ['addsourcedate']);
    $T->set_var('dateformat', $LANG_DQ['srcdateformat']);
    $T->set_var('submit', $LANG_DQ['batchsubmit']);
    $T->set_var('egtitle', $LANG_DQ['egtitle']);
    $T->set_var('tabs', $LANG_DQ['tabs']);
    $T->set_var('line2', $LANG_DQ['line2']);
    $T->set_var('line3', $LANG_DQ['line3']);
    $T->set_var('line1', $LANG_DQ['line1']);
    $T->set_var('line5', $LANG_DQ['line5']);
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'addformfooter.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));
    
    return $retval;
}

//inserts a batch of quotes into the database
function batch_add_quote(){
    global $_TABLES, $_CONF, $_USER, $LANG_DQ;

    if (isset($_POST['submit'])){
        // First, upload the file
        require_once($_CONF['path_system'] . 'classes/upload.class.php');

        $upload = new upload();
        $upload->setPath($_CONF['path_html']. 'dailyquote/data/');
        $upload->setAllowedMimeTypes(array('text/plain'=>'.txt'));
        $upload->setFileNames('batch_import_file.txt');
        if ($upload->uploadFiles()) {
            // Good, file got uploaded, now install everything
            $thefile =  $_FILES['batch_import_file'];
            $filename = $_CONF['path_html']. 'dailyquote/data/' . 'batch_import_file.txt';
        } else {
            // A problem occurred, print debug information
            print 'ERRORS<br>';
            $upload->printErrors();
            exit;
        }
        
        $retval = '';

        $handle = @fopen($filename,'r');
        if (empty ($handle)) {
            return $LANG_DQ['absentfile'];
        }

        // Following variables track import processing statistics
        $successes = 0;
        $failures = 0;
        while ($batchline = fgets($handle,4096)) {
            $singleline = rtrim($batchline);
            list ($quote, $quoted, $cat, $title, $src, $srcdate) = split ("\t", $singleline);

            if ($verbose_import) {
                $retval .="<br><b>Working on quote=$quote, quoted=$quoted, category=$cat, title=$title, source=$source, and sourcedate=$sourcedate</b><br>\n";
                COM_errorLog("Working on quote=$quote, quoted=$quoted, category=$cat, title=$title, source=$source, and sourcedate=$sourcedate",1);
            }

            // prepare import for database

            if ($quote == ''){
                $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['txterror'] . "</p>";
                if ($verbose_import) {
                    $retval .= "<br>The &quot;quote&quot; field cannot be blank.<br>\n";
                }
                $failures++;
            }
            else {
                $quote = addslashes(strip_tags((COM_checkWords((trim ($quote)))), '<strong><em><br><br />'));

                if ((!$quoted) || ($quoted == '')){
                    $quoted = $LANG_DQ['unknown'];
                }
                else {
                    $quoted = addslashes(strip_tags((COM_checkWords((trim ($quoted))))));
                }
                
                if ((!$cat) || ($cat == '')){
                    if ($_POST['batchcat'] != ''){
                        $cat = slashctrl($_POST['batchcat']);
                    }
                    else {
                        $cat = $LANG_DQ['misc'];
                    }
                }
                else {
                    $cat = addslashes(strip_tags(COM_checkWords((trim ($cat)))));
                }
                if ((!$title) || ($title == '')){
                    if ($_POST['title'] != ''){
                        $title = slashctrl($_POST['title']);
                    }
                }
                else {
                    $title = addslashes(strip_tags(COM_checkWords((trim ($title)))));
                }
                if ((!$src) || ($src == '')){
                    if ($_POST['source'] != ''){
                        $source = slashctrl($_POST['source']);
                    }
                }
                else {
                    $src = addslashes(strip_tags(COM_checkWords((trim ($src)))));
                }
                if ((!$srcdate) || ($srcdate == '')){
                    if ($_POST['sourcedate'] != ''){
                        $srcdate = slashctrl($_POST['sourcedate']);
                    }
                }
                else {
                    $srcdate = addslashes(strip_tags(COM_checkWords((trim ($srcdate)))));
                }

                //get user info for db
                //$user = $_USER['username'];
                $uid = $_USER['uid'];

                //get status for db--moderation value
                $queue = DB_query("SELECT queue FROM {$_TABLES['dailyquote_settings']}");
                list($queuestatus) = DB_fetchArray($queue);
                if ((SEC_hasRights('dailyquote.edit')) || ($queuestatus == '0')){
                    $st = "1";
                } else {
                    $st = "0";
                }

                //get ip for db
                //$ip = getenv("REMOTE_ADDR");

                //insert all data to the db
                DB_query("INSERT IGNORE into {$_TABLES['dailyquote_quotes']} SET Quotes='$quote', Quoted='$quoted', Title='$title', Source='$src', Sourcedate='$srcdate', Date=CURDATE(), Status='$st', UID='$uid'");
                if (mysql_affected_rows() > 0){
                    $qid = mysql_insert_id();
                    DB_query("INSERT IGNORE into {$_TABLES['dailyquote_cat']} SET Name='$cat', Status='$st'");
                    if (mysql_affected_rows() > 0){
                        $cid = mysql_insert_id();
                    }
                    else {
                        $result = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='$cat'");
                        $row = mysql_fetch_array($result);
                        $cid = $row[ID];
                    }
                    //get status for db--isenabled value
                    if(!$chkst = DB_query("SELECT Status FROM {$_TABLES['dailyquote_lookup']} WHERE CID='$cid'")){
                        $errstatus = 1;
                    } elseif (DB_numRows($chkst) > 0){
                        list($status) = DB_fetchArray($chkst);
                    } else $status = '1';
                    DB_query("INSERT into {$_TABLES['dailyquote_lookup']} SET QID='$qid', CID='$cid', UID='$uid', Status='$status'");
                    if ($verbose_import) {
                        $retval .= "<br> $quote by <em>$quoted</em> successfully added.<br>\n";
                    }
                    $successes++;
                }
                else {
                    if ($verbose_import) {
                        $retval .= "<br>The quote, &quot;$quote,&quot; already exists in our database.<br>\n";
                    }
                    $failures++;
                }
            }
        }

        fclose($handle);
        unlink($filename);

        $report = $LANG_DQ['msg2'];
        eval ("\$report = \"$report\";");

        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $report . "</p>";
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
$T->set_var('indextitle', $LANG_DQ['batchaddtitle']);
$T->set_var('indexintro', $LANG_DQ['add2intro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

//display a row of pertinent links
if(!eregi('1.3.1',VERSION)){
    $display .= link_row();
}

if (isset($_POST['submit'])){
    $display .= batch_add_quote();
}
if (SEC_hasRights('dailyquote.edit')){
        $display .= batch_add_form();
}

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

$display .= COM_endBlock();
$display .= COM_siteFooter();
echo $display;
?>
