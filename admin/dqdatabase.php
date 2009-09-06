<?php

// +---------------------------------------------------------------------------+
// | Daily Quote Plugin v1.0.5 for Geeklog - The Ultimate Weblog               |
// +---------------------------------------------------------------------------+
// | admin/dqdatabase.php                                                      |
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

require_once('../../../lib-common.php');

// this file can't be used on its own - redirect to index.php
if( eregi( 'dqdatabase.php', $HTTP_SERVER_VARS['PHP_SELF'] ))
{
    echo COM_refresh( $_CONF['site_url'] . '/index.php' );
    exit;
}

//check database tables for consistancy
function checkdb(&$T,$rep = ''){
    global $_TABLES, $LANG_DQ, $_CONF;

    $errstatus = 0;
    $repstatus = 0;

    //get misc ID for later
    if (!$chkmisc = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='{$LANG_DQ['misc']}'")){
        $errstatus = 1;
    } elseif (DB_numRows($chkmisc) > 0){
        list($miscid) = DB_fetchArray($chkmisc);
    //create misc cat
    } else {
        if (!DB_query("INSERT INTO {$_TABLES['dailyquote_cat']} SET Name='{$LANG_DQ['misc']}', Status='1'")){
            $errstatus = 1;
        } else {//assign newly created miscid to QID
            $miscid = mysql_insert_id();
        }
    }
    //check quotes table for orphans--no representation in the lookup table
    if(!$q = DB_query("SELECT ID, q.UID FROM {$_TABLES['dailyquote_quotes']} q LEFT JOIN {$_TABLES['dailyquote_lookup']} ON ID=QID WHERE QID IS NULL")){
        $errstatus = 1;
    } elseif ((DB_numRows($q)) > 0){
        if ($rep == 1){
            //repair database
            while($repq = DB_fetchArray($q)){
                //populate lookup table
                if ($errstatus == 1){
                    break;
                }
                if(!DB_query("INSERT INTO {$_TABLES['dailyquote_lookup']} SET QID='{$repq['ID']}', CID='$miscid', UID='{$repq['UID']}', Status='1'")){
                    $errstatus = 1;
                }
            }
            //display repair results
            if ($errstatus != 1){
                $repstatus = 1;
                $T->set_var('result', $LANG_DQ['dbresults1b']);
                $T->parse('chkrepresult', 'dbresult', true);
            }
        }
        else {
            //display check results
            $errstatus = 2;
            $res = $LANG_DQ['dbresults1'] . '<span style="font-weight: bold; color: red;">';
            $i = 0;
            while ($qidrow = DB_fetchArray($q)){
                if ($i > 0){
                    $ids .= ", ";
                }
                $qid = $qidrow['ID'];
                $ids = $ids . $qid;
                $i++;
            }
            $res .= $ids . '.</span>';
            $T->set_var('result', $res);
            $T->parse('chkrepresult', 'dbresult', true);
        }
    }
    //check lookup table for orphans according to QID
    if($errstatus != 1){
        if(!$l = DB_query("SELECT QID FROM {$_TABLES['dailyquote_lookup']} LEFT JOIN {$_TABLES['dailyquote_quotes']} ON QID=ID WHERE ID IS NULL")){
            $errstatus = 1;
        } elseif ((DB_numRows($l)) > 0){
            if ($rep == 1){
                //repair database
                while($repl = DB_fetchArray($l)){
                    //delete orphans from the lookup table
                    if ($errstatus == 1){
                        break;
                    }
                    if(!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE QID='{$repl['QID']}'")){
                        $errstatus = 1;
                    }
                }
                //display repair results
                if ($errstatus != 1){
                    $repstatus = 1;
                    $T->set_var('result', $LANG_DQ['dbresults2b']);
                    $T->parse('chkrepresult', 'dbresult', true);
                }
            }
            else {
                //display check results
                $errstatus = 2;
                $res = $LANG_DQ['dbresults2'] . '<span style="font-weight: bold; color: red;">';
                $i = 0;
                while ($lidrow = DB_fetchArray($l)){
                    if ($i > 0){
                        $lids .= ", ";
                    }
                    $lid = $lidrow['QID'];
                    $lids = $lids . $lid;
                    $i++;
                }
                $res .= $lids . '.</span>';
                $T->set_var('result', $res);
                $T->parse('chkrepresult', 'dbresult', true);
            }
        }
    }
    if($errstatus != 1){
        //check lookup table for orphaned categories
        if(!$c = DB_query("SELECT CID FROM {$_TABLES['dailyquote_lookup']} LEFT JOIN {$_TABLES['dailyquote_cat']} ON CID=ID WHERE ID IS NULL")){
            $errstatus = 1;
        } elseif ((DB_numRows($c)) > 0){
            if ($rep == 1){
                //repair database
                while($repc = DB_fetchArray($c)){
                    //delete orphans from the lookup table
                    if ($errstatus == 1){
                        break;
                    }
                    //check if any quotes assigned to this catid
                    if(!$ctl = DB_query("SELECT QID FROM {$_TABLES['dailyquote_lookup']} WHERE CID={$repc['CID']}")){
                        $errstatus = 1;
                    } elseif (DB_numRows($ctl) >= 1){
                        while($qida = DB_fetchArray($ctl)){
                            //ensure assigned quote will still belong to a cat after repair
                            if(!$chknum = DB_query("SELECT * FROM {$_TABLES['dailyquote_lookup']} WHERE QID={$qida['QID']}")){
                                $errstatus = 1;
                            } elseif (DB_numRows($chknum) >= 2){
                                if(!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE QID={$qida['QID']} AND CID={$repc['CID']}")){
                                    $errstatus = 1;
                                }
                            } elseif (DB_numRows($chknum) == 1){
                                //reassign QID to miscellany catid and del current entry
                                if (!DB_query("UPDATE {$_TABLES['dailyquote_lookup']} SET CID='$miscid' WHERE CID={$repc['CID']} AND QID={$qida['QID']}")){
                                    $errstatus = 1;
                                }
                            }
                        }
                    //now delete blank entry
                    } elseif (DB_numRows($chknum) == 0){
                        if(!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE CID='{$repc['CID']}'")){
                            $errstatus = 1;
                        }
                    }
                }
                //display repair results
                if ($errstatus != 1){
                    $repstatus = 1;
                    $T->set_var('result', $LANG_DQ['dbresults3b']);
                    $T->parse('chkrepresult', 'dbresult', true);
                }
            } else {
                //display check results
                $errstatus = 2;
                $res = $LANG_DQ['dbresults3'] . '<span style="font-weight: bold; color: red;">';
                $i = 0;
                while ($cidrow = DB_fetchArray($c)){
                    if(!$ctl = DB_query("SELECT QID FROM {$_TABLES['dailyquote_lookup']} WHERE CID={$cidrow['CID']}")){
                        $errstatus = 1;
                    } elseif (DB_numRows($ctl) > 0){
                        list($qid) = DB_fetchArray($ctl);
                        $qres = '</span>' . $LANG_DQ['dbresults4a'];
                    }
                    if ($i > 0){
                        $cids .= ", ";
                    }
                    $cid = $cidrow['CID'] . $qres;
                    $cids = $cids . $cid;
                    $i++;
                }
                $res .= $cids . '.</span>';
                $T->set_var('result', $res);
                $T->parse('chkrepresult', 'dbresult', true);
            }
        }
    }
    if($errstatus != 1){
        //check category table for null entries
        if(!$ct = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name=''")){
            $errstatus = 1;
        } elseif (DB_numRows($ct) > 0){
            if ($rep == 1){
                //repair database
                while($repct = DB_fetchArray($ct)){
                    //delete nulls from the cat table
                    if ($errstatus == 1){
                        break;
                    }
                    //check if any quotes assigned to this catid
                    if(!$ctl = DB_query("SELECT QID FROM {$_TABLES['dailyquote_lookup']} WHERE CID={$repct['ID']}")){
                        $errstatus = 1;
                    } elseif (DB_numRows($ctl) >= 1){
                        while($qida = DB_fetchArray($ctl)){
                            //ensure assigned quote will still belong to a cat after repair
                            if(!$chknum = DB_query("SELECT * FROM {$_TABLES['dailyquote_lookup']} WHERE QID={$qida['QID']}")){
                                $errstatus = 1;
                            } elseif (DB_numRows($chknum) >= 2){
                                if(!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE QID={$qida['QID']} AND CID={$repct['ID']}")){
                                    $errstatus = 1;
                                }
                            } elseif (DB_numRows($chknum) == 1){
                                //reassign QID to miscellany catid and del current entry
                                if (!DB_query("UPDATE {$_TABLES['dailyquote_lookup']} SET CID='$miscid' WHERE CID={$repct['ID']} AND QID={$qida['QID']}")){
                                    $errstatus = 1;
                                } elseif (!DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE QID={$qida['QID']} AND CID={$repct['ID']}")){
                                    $errstatus = 1;
                                }
                            }
                        }
                    }
                    //now delete blank entry
                    if(!DB_query("DELETE FROM {$_TABLES['dailyquote_cat']} WHERE Name=''")){
                        $errstatus = 1;
                    }
                }
                //display repair results
                if ($errstatus != 1){
                    $repstatus = 1;
                    $T->set_var('result', $LANG_DQ['dbresults4b']);
                    $T->parse('chkrepresult', 'dbresult', true);
                }
            } else {
                //display check results
                $errstatus = 2;
                $res = $LANG_DQ['dbresults4'] . '<span style="font-weight: bold; color: red;">';
                $i = 0;
                //unecessary loop--only 1 entry would be returned, if any.  list() would be fine here.
                while ($ctidrow = DB_fetchArray($ct)){
                    if(!$ctl = DB_query("SELECT QID FROM {$_TABLES['dailyquote_lookup']} WHERE CID={$ctidrow['ID']}")){
                        $errstatus = 1;
                    } elseif (DB_numRows($ctl) > 0){
                        list($qid) = DB_fetchArray($ctl);
                        $qres = '</span>' . $LANG_DQ['dbresults4a'];
                    }
                    if ($i > 0){
                        $ctids .= "; ";
                    }
                    $ctid = $ctidrow['ID'] . $qres;
                    $ctids = $ctids . $ctid;
                    $i++;
                }
                $res .= $ctids . '.</span>';
                $T->set_var('result', $res);
                $T->parse('chkrepresult', 'dbresult', true);
            }
        }
    }
    if($errstatus == 1){
        $T->set_var('result', '<span style="font-weight: bold; color: red;">' . $LANG_DQ['dbchkerror'] . '</span>');
        $T->parse('chkrepresult', 'dbresult', true);
        COM_errorLog("An error occured while checking the database",1);
    } elseif (($errstatus == 0) && ($repstatus == 0)){
        $T->set_var('result', '<span style="color: red;">' . $LANG_DQ['dbresultsucc'] . '</span>');
        $T->parse('chkrepresult', 'dbresult', true);
    } elseif ($errstatus == 2) {
        $repbutt = '<p style="margin-left: 15%;"><input type="submit" name="submit" value="' . $LANG_DQ['repdb'] . '" /></p>';
        $T->set_var('repbutt', $repbutt);
    }
}

//create a backup of the dailyquote database tables
function tabledump ($table) {
    global $_CONF, $_TABLES;
    $retval .= "# Dump of $table \n";
    $retval .= "# Dump DATE : " . date("d-M-Y H:i:s a") ."\n\n";

    $query = DB_query("select * from $table");
    $num_fields = DB_numFields($query);
    $numrow = DB_numRows($query);

    while ($row = DB_fetchArray($query)){
        $retval .= "INSERT INTO ".$table." VALUES(";
        for($j=0; $j<$num_fields; $j++) {
            $row[$j] = addslashes($row[$j]);
            $row[$j] = ereg_replace("\n","\\n",$row[$j]);
            if (isset($row[$j])){
                $retval .= "\"$row[$j]\"";
            } else $retval .= "\"\"";
            if ($j<($num_fields-1)) $retval .= ",";
        }
        $retval .= ");\n";
    }
    return $retval . "\n\n\n";
}

//specify tables to be backed up by tabledump
//and display resulting dump txt file.
function datadump(&$T){
    global $_TABLES, $LANG_DQ, $_CONF, $DQ_CONF;

    $content = '# dump using DailyQuote table backup utility version 0.2' . LB;
    $content .= '# use at your own risk  :P' . LB;
    foreach ($_TABLES as $table){
        $table = tabledump($table);
        $content .= $table;
    }

    //write data to file
    $filepath = $DQ_CONF['datadir'];
    if(is_writable($filepath)) {
        $filename = date("YmdHis") . '_dailyquote_db_backup.sql.gz';
        if (!$handle = gzopen($filepath . $filename, 'w')){
            return '<p style="text-align: center; color: red; font-weight: bold;">'.$LANG_DQ['fopenerr'].'</p>';
        } elseif (!$write = gzwrite($handle, $content)){
            return '<p style="text-align: center; color: red; font-weight: bold;">'.$LANG_DQ['fwriteerr'].'</p>';
        } else {
            gzclose($handle);
            //display positive result
            $link = '<a href="' . $_CONF['site_url'] . '/dailyquote/data/' . $filename . '">' . $filename . '</a>';
            $T->set_var('result', '<span style="color: red; font-weight: bold;">' . $LANG_DQ['backupsucc'] . '<br />Filename:  &gt;&gt; ' . $link . ' &lt;&lt;</span>');
            $T->parse('bakresult', 'dbresult', true);
            $res = '<p style="font-weight: bold;">' . $LANG_DQ['preview'] . '</p>';
            $res .= '<div style="margin: auto; border: 1px solid; font-size: 9pt; font-weight: normal; font-family: courier; white-space: pre; width: 80%; height: 300px; overflow: auto; background-color: #CCCCCC;">' . $content . '</div>';
            $T->set_var('bakpreview', $res);
        }
    } else {
        return $LANG_DQ['noaccess'];
        COM_errorLog ($DQ_CONF['datadir'] . ' is not accessible.', 1);
    }
}

//returns a preview of previously backed data
function previewbak(&$T, $backupfile)
{
    global $DQ_CONF, $LANG_DQ;
    $backupfile = $DQ_CONF['datadir'] . $backupfile;
    if(!$handle = gzopen($backupfile, 'r')){
        $errstatus = 1;
    } else {
        $content = gzfile($backupfile);
        foreach ($content as $line){
            $lines .= $line;
        }
        $T->set_var('preview1', $LANG_DQ['preview1']);
        $T->set_var('content', $lines);
        $T->parse('bakpreview', 'prevbak', true);
        gzclose($handle);
    }
    if($errstatus == 1){
        return $LANG_DQ['fopenerr1'];
    }
}

//return array of filenames from backup dir
function filelist()
{
    global $_CONF, $DQ_CONF;
    // Open a known directory, and proceed to read its contents 
    if (is_dir($DQ_CONF['datadir'])) {
        $retval = array();
        if ($dh = opendir($DQ_CONF['datadir'])) {
            while (($file = @readdir($dh)) !== false) {
                if (eregi('dailyquote_db_backup', $file)){
                    clearstatcache();
                    $retval[] = $file;
                }
            }
            closedir($dh);
        }
    }
    if (!empty($retval['0'])) return $retval;
}

//deletes a backup file
function deletebak($backupfile)
{
    global $DQ_CONF, $LANG_DQ;
    $backupfile = $DQ_CONF['datadir'] . $backupfile;
    if(is_writable($backupfile)) {
        if(unlink($backupfile)){
            $retval = '<span style="color: red; font-weight: bold;">' . $LANG_DQ['delfilesucc'] . '</span>';
        } else{
            $retval = '<span style="color: red; font-weight: bold;">' . $LANG_DQ['noaccess'] . '</span>';
        }
        clearstatcache();
    } else {
        $retval = '<span style="color: red; font-weight: bold;">' . $LANG_DQ['noaccess'] . '</span>';
        COM_errorLog ($DQ_CONF['datadir'] . ' is not accessible.', 1);
    }
    return $retval;
}

//restores a previously backed up db
function restorebak($backupfile)
{
    global $_CONF, $DQ_CONF, $_TABLES, $LANG_DQ;
    $errstatus = 0;

    //delete current table data before import
    foreach($_TABLES as $table){
        if(!$scrap = DB_query("DELETE FROM $table")){
            $errstatus = 1;
        }
    }
    //read backup file into tables line by line
    $bakfile = $DQ_CONF['datadir'] . $backupfile;
    if(!$handle = gzopen($bakfile, 'r')){
        $errstatus = 1;
    } else {
        $content = gzfile($bakfile);
        $errlog = 0;
        //foreach line execute statement where line doesn't begin with # or is empty
        foreach ($content as $sql){
            if(eregi('INSERT INTO', $sql)){
                if(!DB_query($sql)){
                    $errlog++;
                }
            }
        }
    }
    gzclose($handle);
    if($errstatus == '1'){
        $retval = '<span style="color: red; font-weight: bold;">' . sprintf($LANG_DQ['resterr'], $backupfile) . '</span>';
    } else {
        $retval = '<span style="color: red; font-weight: bold;">' . sprintf($LANG_DQ['restsucc'], $backupfile, $errlog) . '</span>';
    }
    return $retval;
}
?>
