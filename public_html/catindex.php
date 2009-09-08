<?php

// +---------------------------------------------------------------------------+
// | Daily Quote Plugin v1.0.5 for Geeklog - The Ultimate Weblog                 |
// +---------------------------------------------------------------------------+
// | catindex.php                                                                 |
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
//$query = DB_query("SELECT anonview FROM {$_TABLES['dailyquote_settings']}");
//list($anonview) = DB_fetchArray($query);
// Check user has rights to access this page
if ($_CONF_DQ['anonview'] == '0' && COM_isAnonUser()) {
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
function display_cats(){
    global $_TABLES, $_CONF, $LANG_DQ;

    $sql = "SELECT DISTINCT ID, Name";
    $sql .= " FROM {$_TABLES['dailyquote_cat']} c, {$_TABLES['dailyquote_lookup']} l";
    $sql .= " WHERE c.Status='1' AND l.CID=c.ID AND l.Status='1'";
    $sql .= " ORDER BY Name";
    $sql .= " ASC";

    $result = DB_query($sql);
    if (!$result){
        $retval = $LANG_DQ['caterror'];
        COM_errorLog("An error occured while retrieving list of categories",1);
    }
    //display cats if any to display
    else {
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'dispcatsheader.thtml');
        $T->set_var('categories', $LANG_DQ['categories']);
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        $i = 0;

        //display horizontal rows -- 3 cats per row
        //if you adjust this number, you need to adjust the cell width in the .thtml file
/*
        $col = 3;
        $numrows = DB_numRows($result);
        if ($numrows>0){
            while ($row = DB_fetchArray($result)){
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singlecat.thtml');
                $dispcat = "<a href=\"" . $_CONF['site_url'];
                $dispcat .= "/dailyquote/search.php?cat=" . $row['ID'];
                $dispcat .= "\">" . $row['Name'] . "</a>";
                $T->set_var('dispcat', $dispcat);
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
                $i++;
                if ($i % $col === 0){
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'newcatrow.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                }
            }
        }
*/

        //display vertical columns -- 3 cats per column = $colnum
        //if you increase this number, you need to adjust the cell width in the .thtml file
        $numrows = DB_numRows($result);
        if ($numrows>0){
            $colnum = 3;
            $rownum = 5;//number of cats per column per column-row-set.
            //$down is used when not dividing into col-row-sets, but returning all result rows in $colnum columns
            //if using $down uncomment the following line, comment the next one, and see IF's below for more info..
            //$down = ceil($numrows/$colnum);
            $col = $rownum * $colnum;
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'firstcatcolset.thtml');
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            while ($row = DB_fetchArray($result)) {
                $bagsql = DB_query("SELECT COUNT(*) 
                            FROM
                                {$_TABLES['dailyquote_cat']} c, 
                                {$_TABLES['dailyquote_lookup']} l 
                            WHERE 
                                c.ID={$row['ID']} 
                            AND 
                                l.CID=c.ID");
                if ($bagsql) {
                    list($catbag) = DB_fetchArray($bagsql);
                    $catbag = "(" . $catbag . ")";
                }
                $catlink = catlink($row['ID'],$row['Name']);
                $dispcat = "<li>" . $catlink;
                $dispcat .= "&nbsp;&nbsp;" . $catbag . "</li>";
                $retval .= $dispcat . "<br /><br />";
                $i++;
                //if using $down, replace the current condition with the commented one.
                if (($i % $rownum === 0) && ($i % $col !== 0)){//($i % $down === 0){
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'newcatcol.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                }
                //if using $down, just comment/delete the following IF statement altogether
                if ($i % $col === 0){
                    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                    $T->set_file('page', 'newcatcolset.thtml');
                    $T->parse('output','page');
                    $retval .= $T->finish($T->get_var('output'));
                }
            }
        }

        else $retval .= "<p align=\"center\">".$LANG_DQ['StatsMsg2']."</p>";
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'dispcatsfooter.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
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

$T->set_var('indextitle', $LANG_DQ['catlsttitle']);
$T->set_var('indexintro', $LANG_DQ['catlstintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

//display a row of pertinent links
if(!eregi('1.3.1',VERSION)){
    $display .= link_row();
}

$A = DQ_getQuote();
$display .= $A['quote'];

//display category listing
$display .= display_cats();

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));
$display .= COM_endBlock();
$display .= COM_siteFooter();
echo $display;
?>
