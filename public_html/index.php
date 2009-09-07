<?php
//  $Id$
/**
*   Common functions for the DailyQuote plugin
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion functions */
require_once('../lib-common.php');

// Retrieve access settings
$anonview = $_CONF_DQ['default_permissions'][3];
if ($anonview < 2 && $_USER['uid'] < 2) {
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

/**
*   Displays the google-like page nav.
*/
function display_pagenav($sort, $asc, $page)
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;

    //print page nav here
    $numquotes = DB_count($_TABLES['dailyquote_quotes'], 'status', "1");
    $displim = $_CONF_DQ['indexdisplim'];
    if ($numquotes > $displim) {
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'pagenav.thtml');
        $prevpage = $page - 1;
        $nextpage = $page + 1;
        $pagestart = ($page - 1) * 25;
        if ((isset($sort)) && (isset($asc))){
            $sortvar = "sort=".$sort."&amp;asc=".$asc;
        }
        elseif (isset($sort)){
            $sortvar = "sort=".$sort;
        }
        elseif (isset($asc)){
            $ascvar = "asc".$asc;
        }
        $baseurl = $_CONF['site_url'] . '/dailyquote/index.php?' . $sortvar . $ascvar;
        $numpages = ceil ($numquotes / $displim);
        $T->set_var ('google_paging',
                COM_printPageNavigation ($baseurl, $page, $numpages));
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
*   Displays the quotes listing.
*/
function display_quote($sort, $asc, $page){
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;
    global $_SYSTEM, $_USER;

    if ($sort == '3') {
        //this sort option to be removed ... category index page is sufficient
        $catcol = ", Name";
        $cattab = ", {$_TABLES['dailyquote_cat']} c";
        $catwh = " AND c.id=l.cid";
    }

    $sql = "SELECT DISTINCT 
        q.id, content, quoted, title, source, sourcedate, dt, q.uid";
    if ($sort == '3') {
        $sql .= $catcol;
    }
    $sql .= " FROM {$_TABLES['dailyquote_quotes']} q, 
            {$_TABLES['dailyquote_lookup']} l";
    if ($sort == '3') {
        $sql .= $cattab;
    }
    $sql .= " WHERE q.status='1'";
    if ($sort == '3') {
        $sql .= $catwh;
    }
    $sql .= " AND l.status='1' AND l.qid=q.id";

    switch ($sort) {
    case 1:
        $sorted = 'content';
        break;
    case 2:
        $sorted = 'quoted';
        break;
    case 3:
        $sorted = 'name';
        break;
    case 4:
        $sorted = 'uid';
        break;
    case 5:
    default:
        $sorted = 'dt';
        break;
    }
    $sql .= " ORDER BY $sorted";

    if ($asc == 1)
        $sql .= ' ASC';
    else
        $sql .= ' DESC';

    // Retrieve results per page setting
    //$query = DB_query("SELECT 
    //        indexdisplim AS displim 
    //        FROM {$_TABLES['dailyquote_settings']}");
    //list($displim) = DB_fetchArray($query);
    $displim = $_CONF_DQ['indexdisplim'];
    $limit = ($displim * $page) - $displim;
    $sql .= " LIMIT $limit, $displim";

    $result = DB_query($sql);
    if (!$result){
        $retval = $LANG_DQ['disperror'];
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
            $retval .= display_pagenav($sort, $asc, $page);
            while ($row = DB_fetchArray($result)){
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singlequote.thtml');
                if (!empty($row['title'])){
                    $title = '<p style="text-align: left; font-weight: bold; text-decoration: underline;">';
                    $title .= $row['title'];
                    $title .= '</p>';
                    $T->set_var('title', $title);
                }
                $T->set_var('quote', $row['content']);
                $quoted = ggllink($row['quoted']);
                $T->set_var('quoted', $quoted);
                if (!empty($row['Source'])){
                    $T->set_var('source', '&nbsp;--&nbsp;' . $row['Source']);
                }
                if (!empty($row['Sourcedate'])){
                    $T->set_var('sourcedate', '&nbsp;&nbsp;(' . $row['Sourcedate'] . ')');
                }
                $T->set_var('subm_by', $LANG_DQ['subm_by']);
                $contr = DB_query("SELECT uid, username 
                                FROM {$_TABLES['users']} 
                                WHERE uid={$row['uid']}");
                list($uid,$username) = DB_fetchArray($contr);
                $username = prflink($uid,$username);
                $T->set_var('contr', $username);
                $T->set_var('datecontr', strftime($_CONF['shortdate'], $row['Date']));
                $cat = DB_query("SELECT c.id, c.name 
                                FROM {$_TABLES['dailyquote_cat']} c, 
                                    {$_TABLES['dailyquote_lookup']} l 
                                WHERE 
                                    {$row['id']}=l.qid 
                                AND 
                                    c.id=l.cid 
                                AND 
                                    l.status='1'");
                $i = 0;
                $catlist = "";
                while ($catrow = DB_fetchArray($cat)){
                    if ($i > 0){
                        $catlist .= ", ";
                    }
                    $catlink = catlink($catrow['ID'],$catrow['Name']);
                    $catlist = $catlist . $catlink;
                    $i++;
                }
                $T->set_var('cat', $LANG_DQ['cat']);
                $T->set_var('dispcat', $catlist);
                if(SEC_hasRights('dailyquote.edit')){
                    $editlink = '<a href="' . $_CONF['site_url'] . '/dailyquote/manage.php?qid=';
                    $editlink .= $row['ID'] . '">';
                    $editlink .= '<img src="' . $_CONF['site_url'] . '/dailyquote/images/edit.gif' . '" alt="';
                    $editlink .= $LANG_DQ['editlink'];
                    $editlink .= '" border="0" />';
                    $editlink .= '</a>';
                    $T->set_var('editlink', $editlink);
                }
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
            }
            $retval .= display_pagenav($sort, $asc, $page);
        }
        else $retval .= "<p align=\"center\">".$LANG_DQ['StatsMsg2']."</p>";
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'dispquotesfooter.thtml');
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
    }
    return $retval;
}


/* 
* MAIN
*/

// Retrieve and sanitize provided parameters
$id = isset($_REQUEST['id']) ? COM_sanitizeID($_REQUEST['id']) : '';
$sort = isset($_GET['sort']) ? (int)$_GET['sort'] : 0;
$asc = isset($_GET['asc']) ? (int)$_GET['asc'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$display = COM_siteHeader();
$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqheader.thtml');
$T->set_var('header', $LANG_DQ00['plugin']);
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('plugin', 'dailyquote');

if (isset($_GET['msg'])){
    $msg = "msg" . $_GET['msg'];
    $T->set_var('msg', $LANG_DQ[$msg]);
}

$T->set_var('indextitle', $LANG_DQ['indextitle']);
$T->set_var('indexintro', $LANG_DQ['indexintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

$display .= random_quote($id);

//display the sort by menu
$display .= sort_by_menu();

//display quote listing by date desc as default
$display .= display_quote($sort, $asc, $page);

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));
$display .= COM_siteFooter();
echo $display;

?>
