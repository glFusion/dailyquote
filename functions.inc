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
*
*   Adapted for glFusion 1.1 by Lee Garner (lee@leegarner.com) 2008
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

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

//  Check and see if we need to load the plugin configuration
if (!isset($_CONF_DQ['quoteperpage'])) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';

    $dq_config = config::get_instance();
    $_CONF_DQ= $dq_config->get_config('dailyquote');
}

global $_CONF_DQ;
$_CONF_DQ['pi_version'] = '0.1';
$_CONF_DQ['pi_name'] = 'dailyquote';
$_CONF_DQ['gl_version'] = '1.1.0';
$_CONF_DQ['pi_url'] = 'http://www.leegarner.com';

//print_r($_CONF_DQ);

require_once $_CONF['path']. '/plugins/' . $_CONF_DQ['pi_name'] . '/config.php';


/**
* Language file Include
*/
$langpath = "{$_CONF['path']}/plugins/{$_CONF_DQ['pi_name']}/language";
$langfile = "{$_CONF['language']}.php";
if (!file_exists($langfile)) {
    $langfile = 'english.php';
}
include_once "$langpath/$langfile";



/**
* Returns the items for this plugin that should appear on the main menu
*
* NOTE: this MUST return the url/value pairs in the following format
* $<arrayname>[<label>] = <url>
*
* @return    array   Associative array of menu items to be added to menu
*
*/
function plugin_getmenuitems_dailyquote()
{
    global $_CONF, $_USER, $LANG_DQ, $_TABLES;

    // Retrieve access Settings
    $query = DB_query("SELECT anonview FROM {$_TABLES['dailyquote_settings']}");
    list($anonview) = DB_fetchArray($query);

    $menulabel = "{$LANG_DQ['menulabel']}";                // Put your menu label here
    $menurul = $_CONF['site_url'] . "/dailyquote/index.php";     // Menu url here
    
    $menuitems = array();

    // Check user has rights to view this cb, if so, do it.
    if ((($anonview == '1') && ($_USER['uid'] < 2)) || ($_USER['uid'] > 1)) {
        $menuitems["$menulabel"] = $menurul;
    }
    return $menuitems;
}


/**
*
* Geeklog is checking to see if this plugin supports comments, tell it no!
* NOTE: to support comments you must use the same date/time based ID for your
* widget.  In other words, to make primary keys for your plugin you should call
* makesid().  Comments are keyed off of that...it is a limitation on how geeklog
* does comments.
*
* @return   boolean true or false
*
*/
function plugin_commentsupport_dailyquote()
{
    return false;
}


/**
* Shows the statistics for the plugin on stats.php.  If
* $showsitestats is 1 then we are to only print the overall stats in the 'site
* statistics' box otherwise we show the detailed stats for the plugin
*
* @param    int showsitestate   Flag to let us know which stats to get
* @return   string  returns formatted HTML to insert in stats page
*
*/
function plugin_showstats_dailyquote($showsitestats)
{
    global $_CONF, $_USER, $_TABLES, $LANG_DQ, $_CONF_DQ;

    require_once ($_CONF['path_system'] . 'lib-admin.php');

    $retval = '';

    // Check user has rights to view this cb, if so, do it.
    if ($_CONF_DQ['default_permissions'][3] < 2 && $_USER['uid'] < 2) {
        return '';
    }

    $T = new Template($_CONF['path_layout'] . 'stats');
    $T->set_file(array('itemstats'=>'itemstatistics.thtml',
                                'statrow'=>'singlestat.thtml'));
    /*if ($showsitestats == 1) {
        $total_pages = DB_count($_TABLES['dailyquote_quotes'], 'Status', '1');
        $retval .= "<table border = '0' width='100%' cellspacing='0' cellpadding='0'>";
        $retval .= "<tr><td>" . $LANG_DQ['noquotes'] . "</td>";
        $retval .= "<td align='right'>" . $total_pages . "&nbsp;&nbsp </td></tr></table>";
        return $retval;
    }*/

    $result = DB_query("SELECT 
                            quoted, COUNT(*) 
                        FROM 
                            {$_TABLES['dailyquote_quotes']} q, 
                            {$_TABLES['dailyquote_lookup']} l 
                        WHERE 
                            q.id = l.QID 
                        AND 
                            l.Status = '1' 
                        GROUP BY Quoted 
                        ORDER BY 2 DESC, 1 ASC 
                        LIMIT 10"
    );
    $nrows  = DB_numRows($result);
    $retval .= COM_startBlock($LANG_DQ['StatsMsg1']);
    if ($nrows > 0) {
        $T->set_var('item_label', $LANG_DQ['StatsMsg3']);
        $T->set_var('stat_name', $LANG_DQ['StatsMsg4']);
        for ($i = 0; $i < $nrows; $i++) {
            $A = DB_fetchArray($result);
            //$quoted = urlencode($A['quoted']);//dunno if this is necessary just yet.
            $T->set_var('item_url', 
                        "{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/search.php?stat=" . $A['quoted']);
            $T->set_var('item_text', $A['quoted']);
            $T->set_var('item_stat', $A['COUNT(*)']);
            $T->parse('stat_row','statrow',true); 
        }
        $T->parse('output','itemstats');
        $retval .= $T->finish($T->get_var('output'));
    } else {
        $retval .= $LANG_DQ['StatsMsg2'];
    }   
    $retval .= COM_endBlock();
    return $retval;

}


function plugin_statssummary_dailyquote()
{
    global $$LANG_DQ, $_TABLES;

    // This shows in the summary box
    $total_items=DB_count($_TABLES['dailyquote_quotes']);
    //$summary_label = $LANG_PHOTO['items_we_have'];
    $summary_label = "Quotes in database";
    $retval[] = $summary_label;
    $retval[] = $total_items;
    return $retval;
}


/**
*   This will put an option for the plugin in the command and control 
*   block on moderation.php
*
*   @return   array   Array containing (plugin name, admin url, url of plugin icon)
*
*/
function plugin_cclabel_dailyquote()
{
    global $_CONF, $LANG_DQ, $_CONF_DQ;

    return array(
        $LANG_DQ['plugin_name'],
        $_CONF['site_admin_url'] . '/plugins/' . $_CONF_DQ['pi_name'] . '/index.php',
        $_CONF['site_url'] . '/' . $_CONF_DQ['pi_name']. '/images/dailyquote.gif'
    );
}


/**
* Returns the administrative option for this plugin
*
* @return   array   Array containing (plugin name, plugin admin url, # of items in plugin or '')
*
*/
function plugin_getadminoption_dailyquote()
{
    global $_CONF, $_TABLES, $LANG_DQ, $_CONF_DQ;

    if (SEC_inGroup('Root')) {
        $result = DB_query("SELECT COUNT(*) FROM {$_TABLES['dailyquote_quotes']} WHERE Status=0");
        list($submittedquotes) = DB_fetchArray($result);
        return array(
            $LANG_DQ['admin_menu'], 
            $_CONF['site_admin_url'] . '/plugins/' . $_CONF_DQ['pi_name'] . '/index.php', 
            $submittedquotes
        );
    }
}


/**
* Returns the user option for this plugin
*
* @return   array   Array containing (plugin name, plugin user url, 0)
*
*/
function plugin_getuseroption_dailyquote()
{
    global $_CONF, $_USER, $LANG_DQ, $_TABLES, $_CONF_DQ;

    // Retrieve access settings
    $query = DB_query("SELECT anonadd, loginadd FROM {$_TABLES['dailyquote_settings']}");
    list($anonadd,$loginadd) = DB_fetchArray($query);

    if (SEC_hasRights('dailyquote.edit')) {
        return array(
            $LANG_DQ['user_menu2'], 
            $_CONF['site_url'] . '/' . $_CONF_DQ['pi_name'] . '/manage.php', 
            0
        );
    } elseif (($anonadd == '1') && ($_USER['uid'] < 2) || 
            ($loginadd == '1' && $_USER['uid'] > 2) || 
            SEC_hasRights('dailyquote.add')
        ) {
        return array(
            $LANG_DQ['user_menu1'], 
            $_CONF['site_url'] . '/' . $_CONF_DQ['pi_name'] . '/add.php', 
            0
        );
    }
}


//adds variables to the user's profile
function plugin_profilevariablesdisplay_dailyquote($uid, &$template)
{
    global $_TABLES, $_CONF, $LANG_DQ, $_USER, $_CONF_DQ;

    // Retrieve access Settings
    $anonview = DB_getItem($_TABLES['dailyquote_settings'],'anonview');

    // Check user has rights to view this, if so, do it.
    if (($anonview == '1') || ($_USER['uid'] >= '2')) {
        $postlimit="10";        // How many posts you want displayed

        $query = DB_query ("SELECT id, Quotes, Date 
                FROM {$_TABLES['dailyquote_quotes']} 
                WHERE UID='$uid' AND Status='1' 
                ORDER BY Date DESC 
                LIMIT {$postlimit}");
        //************************
        $template->set_var ('end_block', COM_endBlock());
        $template->parse ('story_row', 'strow', true);
        //*************************

        $template->set_var ('start_block_last10plugin', COM_startBlock( $LANG_DQ['proflastqtxt'] . DB_getItem ($_TABLES['users'], 'username', "uid = $uid")));
        $numq = 0;
        if (DB_numRows($query) > 0) {
            while (list($qid,$quote,$date) = DB_fetchArray($query)) {
                if (strlen($quote) > 32) {
                    $quote = substr($quote, 0, 32) . '...';
                }
                $numq++;
                $postdate = COM_getUserDateTimeFormat ($date);
                $template->set_var ('row_number', $numq . '.');
                $template->set_var ('plugin_begin_href', 
                        '<a href="' . $_CONF['site_url'] . '/' . 
                        $_CONF_DQ['pi_name'] . '/search.php?qid=' . $qid . 
                        '&amp;show=quote">');
                $template->set_var ('plugin_title', $quote);
                $template->set_var ('plugin_end_href', '</a>');
                $template->set_var ('plugin_date', $postdate[0]);
                $template->parse ('pluginpostrow', 'plgprow', true);
            }
        } else {
            $template->set_var ('plugin_row', '<tr><td>' .$LANG_DQ['profnone']. '</td></tr>');
            //$template->parse ('pluginpostrow', 'plgprow', true);
        }
        
        $template->set_var ('end_block', COM_endBlock());
        $template->parse ('plugin_last10_table', 'plgtable', true);
    }
}


//addds total stats to the user's profile
function plugin_profilestatsvariablesdisplay_dailyquote ($uid, &$template)
{
    global $LANG_DQ, $_TABLES, $_USER;

    // Retrieve access Settings
    $anonview = DB_getItem($_TABLES['dailyquote_settings'],'anonview');

    // Check user has rights to view this, if so, do it.
    if (($anonview == '1') || ($_USER['uid'] > '1')) {
        $count = DB_count ($_TABLES['dailyquote_quotes'], array('UID', 'Status'), array($uid, '1'));
        $template->set_var ('lang_number_plugin', $LANG_DQ['profnumqtxt']);
        $template->set_var ('number_plugin', $count);
        $template->parse ('plugin_stats_number_row', 'plgsrow', true);
    }
}


/**
* Geeklog is asking us to provide any new items that show up in the type drop-down
* on search.php.  Let's let users search the Filelistings in the Filemgmt Plugin
*
*/
function plugin_searchtypes_dailyquote() 
{
    global $LANG_DQ;

    $tmp['dailyquote'] = $LANG_DQ['glsearchlabel'];
    return $tmp;
}


/**
* this searches for pages matching the user query and returns an array of 
* for the header and table rows back to search.php where it will be formated and
* printed 
*
* @query            string          Keywords user is looking for
* @datestart        date/time       Start date to get results for
* @dateend          date/time       End date to get results for
* @topic            string          The topic they were searching in
* @type             string          Type of items they are searching
* @author           string          Get all results by this author
*
*/
function plugin_dopluginsearch_dailyquote(
    $query, 
    $datestart, 
    $dateend, 
    $topic, 
    $type, 
    $author,
    $keyType,
    $page,
    $perpage)
{
    global $_TABLES, $_CONF, $_USER, $LANG_DQ00, $LANG_DQ, $_CONF_DQ;

    // Retrieve access Settings
    //$access = DB_query("SELECT anonview FROM {$_TABLES['dailyquote_settings']}");
    //list($anonview) = DB_fetchArray($access);
    $anonview = 1;

    // Check user has rights to view this results, if so, do it.
    if ($anonview != 1  && COM_isAnonUser()) {
        return '';
    }

    $htmlquery = urlencode($query);

    // Build search SQL
    $sql = "SELECT 
            q.id as id, q.Quotes as description, 
            q.Title as title, q.UID as uid, 
            q.Date as date,
            CONCAT('/{$_CONF_DQ['pi_name']}/index.php?mode=detail&id=',q.id,'&query=$htmlquery') as url
        FROM 
            {$_TABLES['dailyquote_quotes']} q
        WHERE 
            q.Status = 1
    ";

    if (!empty($datestart) && !empty($dateend)) {
            $delim = substr($datestart, 4, 1);
            $DS = explode($delim,$datestart);
            $DE = explode($delim,$dateend);
            $startdate = mktime(0,0,0,$DS[1],$DS[2],$DS[0]);
            $enddate = mktime(0,0,0,$DE[1],$DE[2],$DE[0]) + 3600;
            $sql .= " AND (q.Date BETWEEN '$startdate' AND '$enddate') ";
    }

    if (!empty($author)) {
        $sql .= " AND (q.UID='$author') ";
    }

    $search = new SearchCriteria($_CONF_DQ['pi_name'], $LANG_DQ['plugin_name']);
    $columns = array('q.Title', 'q.Quotes', 'q.Quoted');
    list($sql, $ftsql) = $search->buildSearchSQL($keyType, $query, $columns, $sql);
    //echo $sql;
    $search->setSQL($sql);

    return $search;

}


/**
  * Whats New Block API Support
*/
function plugin_whatsnewsupported_dailyquote()
{
    global $_CONF, $_TABLES, $LANG_DQ, $_CONF_DQ, $_USER;

    $result = DB_query("SELECT anonview, whatsnew FROM {$_TABLES['dailyquote_settings']}");
    list($anonview,$whatsnew) = DB_fetchArray($result);

    if ((!$result) || ($whatsnew == '0') || (($anonview == '0') && ($_USER['uid'] < 2))) {
        return;
    } else {
        return array($LANG_DQ['whatsnewlabel'],sprintf($LANG_DQ['whatsnewperiod'], $_CONF_DQ['whatsnewperioddays']));
    }
}


/**
  * API function provides the content of our "What's New" feed
*/
function plugin_getwhatsnew_dailyquote()
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;

    $sql = "SELECT DISTINCT 
                q.id, q.Quoted 
            FROM 
                {$_TABLES['dailyquote_quotes']} q, 
                {$_TABLES['dailyquote_lookup']} l
            WHERE 
                q.Date >= DATE_SUB(NOW(), INTERVAL {$_CONF_DQ['whatsnewperioddays']} DAY )
            AND 
                q.Status='1' AND l.QID=q.id 
            ORDER BY Date DESC 
            LIMIT 15 ";
    $result = DB_query( $sql );
    $nrows = DB_numRows( $result );
    if ( $nrows == 0 ) {
        $items = $LANG_DQ['newempty'] . '<br />' . LB;
    } else {
        $items = array();
        for( $i = 0; $i < $nrows; $i++ ) {
            list($qid, $quoted) = DB_fetchArray( $result );
            $str = "<a href=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/search.php?qid=$qid&show=quote\">";
            $str .= stripslashes(substr($quoted,0,$_CONF_DQ['whatsnewnamelength']));
            $str .= '</a>';
            $items[] = $str;
        }
    }
    return $items;
}


/**
* Return the current version of code. 
* Used in the Plugin Editor to show the registered version and code version
*/
function plugin_chkVersion_dailyquote()
{
    global $_CONF_DQ;

    return $_CONF_DQ['version'];
}


/**
 *  @function plugin_autouninstall_dailyquote
 *  @return array Array of plugin elements to remove
 *
 *  Automatically uninstall the plugin
 */
function plugin_autouninstall_dailyquote()
{
    global $_CONF_DQ;

    $out = array (
        'tables'    => array($_CONF_DQ['pi_name']. '_quotes',
                            $_CONF_DQ['pi_name']. '_category',
                            $_CONF_DQ['pi_name']. '_lookup',
                            $_CONF_DQ['pi_name']. '_settings',
                        ),
        'groups'    => array($_CONF_DQ['pi_name'] . ' Admin'),
        'features'  => array($_CONF_DQ['pi_name'] . '.add', 
                            $_CONF_DQ['pi_name'] . '.edit',
                            $_CONF_DQ['pi_name'] . '.view',
                        ),
        'php_blocks' => array('phpblock_' . $_CONF_DQ['pi_name'] . '_dqmenu',
                            'phpblock_' . $_CONF_DQ['pi_name'] . '_random_quote',
                        ),
        'vars'      => array($_CONF_DQ['pi_name'] . '_gid')
    );

    return $out;

}


/**
* Removes the datastructures for this plugin from the Geeklog database.
* This routine will get called from the Plugin install program if user select De-Install or if Delete is used in the Plugin Editor.
* The Plugin Installer will also call this routine upon and install error to remove anything it has created.
* The Plugin installer will pass the optional parameter which will then double check that plugin has first been disabled. 
* 
* For this plugin, this routine will also remove the Block definition.
* 
* Returns True if all Plugin related data is removed without error
*
* @param    string   $installCheck     Defult is blank but if set, check if plugin is disabled first
* @return   boolean True if successful false otherwise
*
*/  
function plugin_uninstall_dailyquote($installCheck='')
{
    global $_TABLES, $LANG_DQ00, $LANG_DQ, $_CONF_DQ;

    $pi_name=addslashes($_CONF_DQ['pi_name']);
    $FEATURES = array ( 'dailyquote.edit', 'dailyquote.view', 'dailyquote.add');
    $TABLES = array ('dailyquote_quotes','dailyquote_cat','dailyquote_lookup','dailyquote_settings');
    
    // Check and see if plugin is still enabled - if so display warning and exit
    if ($installCheck != '' && DB_getItem($_TABLES['plugins'],'pi_enabled', 'pi_name = "' .$pi_name. '"')) {
        COM_errorLog("Plugin is installed and enabled. Disable first if you want to de-install it",1);
        $display = COM_siteHeader();
        $display .= COM_startBlock($LANG_DQ00['warning']);
        $display .= $LANG_DQ00['enabled'];
        $display .= COM_endBlock();
        $display .= COM_siteFooter();
        echo $display;
        exit;
    }
        
    // Ok to proceed and delete plugin -- Unregister the plugin with Geeklog
    COM_errorLog('Attempting to unregister the Daily Quote plugin from glFusion',1);
    DB_query("DELETE FROM {$_TABLES['plugins']} 
        WHERE pi_name = '$pi_name'",1);

    // Drop dailyquote tables
    foreach($TABLES as $table) {
        $t = $_TABLES["$table"];
        COM_errorLog("Removing Table $t",1);
        DB_query("DROP TABLE $t",1);
    }

    //remove dq blocks created by install
    COM_errorLog("Removing $pi_name blocks",1);
    DB_query("DELETE FROM {$_TABLES['blocks']} WHERE name='dqmenu' LIMIT 1",1);
    DB_query("DELETE FROM {$_TABLES['blocks']} WHERE name='dailyquote' LIMIT 1",1);

    // Remove Security for this plugin
    $grp_id = DB_getItem($_TABLES['vars'], 'value', "name = '{$pi_name}_gid'");
    
    COM_errorLog("Removing $pi_name Admin Group", 1);
    DB_query("DELETE FROM {$_TABLES['groups']} WHERE grp_id = $grp_id",1);
    DB_query("DELETE FROM {$_TABLES['vars']} WHERE name = '{$pi_name}_gid'");
    COM_errorLog("Removing root users from admin of $pi_name");
    DB_query("DELETE FROM {$_TABLES['group_assignments']} WHERE ug_main_grp_id = $grp_id",1);
    
    // Remove all the associated features -- access rights
    foreach ($FEATURES as $feature) {
        COM_errorLog("Removing $feature feature and rights to it",1);
        $feat_id = DB_getItem($_TABLES['features'], 'ft_id', "ft_name = '$feature'");
        DB_query("DELETE FROM {$_TABLES['access']} WHERE acc_ft_id = $feat_id",1);
        DB_query("DELETE FROM {$_TABLES['features']} WHERE ft_id = $feat_id",1);
    }
        
    COM_errorLog('...success',1);
    return true;
}

//chks for magic_quotes and acts accordingly. use only for gpc otherwise use addslashes
function slashctrl($str)
{
    if (!get_magic_quotes_gpc()) {  //right from the manual :P
        $str = addslashes($str);
    }
    return $str;
}


//converts usernames to clickable links leading to user profiles
function prflink($uid,$username)
{
    global $_CONF, $LANG_DQ;

    if ($uid > '1') {
        $retval = '<a href="' . $_CONF['site_url'];
        $retval .= '/users.php?mode=profile&amp;uid=' . $uid . '">';
        $retval .= $username . '</a>';
    } else {
        $retval = $LANG_DQ['anonymous'];
    }
    return $retval;
}


//converts the names of personalities quoted to clickable google links.
function ggllink($Quoted)
{
    global $_CONF, $_TABLES, $LANG_DQ, $_CONF_DQ;

    //do if based on setting
    if ($_CONF_DQ['google_link'] == 1 && !empty($_CONF_DQ['google_url'])) {
        if ($Quoted != '') {
            $gname = urlencode(trim($Quoted));
            $retval = '<a href="' . $_CONF_DQ['google_url'] . $gname . '">' . $Quoted . '</a>';
        } else {
            $retval = $LANG_DQ['unknown'];
        }
    } else {
        $retval = $Quoted;
    }

    return $retval;
}


//converts categories to clickable links leading to the search page
//which will list all quotes in that category
function catlink($catid,$catname)
{
    global $_CONF, $_TABLES, $_CONF_DQ;

    $retval = "<a href=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/search.php?cat=$catid\">$catname</a>";
    return $retval;
}


/**
 *  @function DQ_getQuote
 *  @param  string  $id     Optional quote specifier
 *  @return string  Quote Text or empty string upon failure
 *
 *  Retrieves a single quote.  If $id is empty, a randome quote is selected.
 */
function DQ_getQuote($id='')
{
    global $_TABLES;

    //get random quote
    $sql = "SELECT 
                q.Quotes, q.Quoted, q.Title, 
                q.Source, q.Sourcedate, 
                q.Date
            FROM 
                {$_TABLES['dailyquote_quotes']} q 
            WHERE 
                q.Status = '1'";
    if ($id == '') {
        $sql .= " ORDER BY rand() LIMIT 1";
    } else {
        $sql .= " AND q.id='" . mysql_real_escape_string($id). "'";
    }
    //echo $sql;

    if (!$dbquote = DB_query($sql)) {
        COM_errorLog("An error occured while retrieving your quote",1);
        return array();
    }

    $row = DB_fetchArray($dbquote, false);

    $row['Quoted'] = ggllink($row['Quoted']);
    return $row;
}


function plugin_centerblock_dailyquote($where=1,$page=1,$topic ='')
{
    global $_CONF, $_USER, $_TABLES, $PHP_SELF, $LANG_DQ, $_CONF_DQ;

    $img_dir = $_CONF['layout_url'] . '/' . $_CONF_DQ['pi_name'] . '/image_set';
    $retval = '';

    // If centerblock not enabled, or just for homepage and
    // we're not on the homepage, just return
    if ($_CONF_DQ['cb_enable'] == 0 ||
            ($CONF_DQ['cb_homepage'] == 1 && ($page > 1 || $topic != '')))
        return '';

    // Get the centerblock position.  May be overridden later
    $cntrblkpos = $_CONF_DQ['cb_pos'];

    // If we're not supposed to replace the homepage, then return.
    // Otherwise, do so.
    if ($where == 0 && $topic == '') {
        if (!$_CONF_DQ['cb_replhome']) {
            return '';
        } else {
            $cntrblkpos = 0;
        }
    }


    // Check if there are no featured articles in this topic 
    // and if so then place it at the top of the page
    if ($topic != "") {
        $wheresql = "WHERE tid='$topic' AND featured > 0";
    } else {
        $wheresql = "WHERE featured = 1";
    }

    $query = DB_query("SELECT count(*) as count FROM {$_TABLES['stories']} $wheresql");
    $result = DB_fetchArray($query);
    if ($result['count'] == 0 and $_CONF_DQ['cb_pos'] == 2) {
        $cntrblkpos = 1;
    }

    if ($cntrblkpos != $where) {
        return '';
    }

    $dqarray = DQ_getQuote();
    if (!is_array($dqarray)) {
        return '';
    }

    list($Quote, $Quoted, $Tit, $Source, $Sourcedate, $Date) = $dqarray;
    if (empty($Quote)) {
        return '';
    }

    if ($where == 0) {
        $retval = COM_siteHeader();
    }

    $T = new Template($_CONF['path'] . 'plugins/' . $_CONF_DQ['pi_name']. '/templates');
    $T->set_file('page', 'centerblock.thtml');

    $T->set_var('dispquote', $Quote);
    $T->set_var('dispquoted', $Quoted);

    if (!empty($Source)) {
        $dispsource = "&nbsp;--&nbsp;" . $Source;
        $T->set_var('source', $dispsource);
    }

    if ($_CONF_DQ['cb_srcdate'] && !empty($Sourcedate)) {
        $dispsourcedate = "&nbsp;&nbsp;(" . $Sourcedate . ')';
        $T->set_var('sourcedate', $dispsourcedate);
    }

    if ($_CONF_DQ['cb_title'] && !empty($Tit)) {
        $title = '<p style="text-align: left; font-weight: bold; text-decoration: underline;">';
        $title .= $Tit;
        $title .= '</p>';
        $T->set_var('title', $title);
    }

    $T->parse('output','page');

    if ($_CONF_DQ['anonadd'] == '1' || 
            !COM_isAnonUser() || 
            SEC_hasRights('dailyquote.add')) {
        $link = "&nbsp;&nbsp;--&nbsp;&nbsp;";
        $link .= '<a href="' . $_CONF['site_url'] . '/' . $_CONF_DQ['pi_name'] . '/add.php">';
        $link .= $LANG_DQ['addformlink'];
        $link .= "</a>";
    }

    //$retval = COM_startBlock($LANG_DQ['indextitle'] . $link);
    $retval .= $T->finish($T->get_var('output'));
    //$retval .= "<hr>where $where :: page $page :: topic $topic<hr>";
    //$retval .= COM_endBlock();

    if ($where == 0) {
        $retval .= COM_siteFooter();
    }

    return $retval;
}


//returns link or text based on curr url
function is_curpage($curpage, $lntxt)
{
    global $_CONF, $_CONF_DQ;

    $thisurl = COM_getCurrentURL();
    $dqpage = $_CONF_DQ['pi_name'] . '/' . $curpage;
    if ((eregi($dqpage, $thisurl)) && (!eregi('admin', $thisurl))) {
        $href = $lntxt;
    } else {
        $href = '<a href="' . $_CONF['site_url'] . '/' . $_CONF_DQ['pi_name'] . 
            '/' . $curpage . '">' . $lntxt . '</a>';
    }
    return $href;
}


//returns blockmenu content for dqblockmenu
function phpblock_dailyquote_dqmenu()
{
    global $_TABLES, $_CONF, $_USER, $LANG_DQ, $_CONF_DQ;

    $content = '';

    //link to the dq index page
    $href = is_curpage('index.php', $LANG_DQ['indexlink']);
    $sql = "SELECT 
                * 
            FROM 
                {$_TABLES['dailyquote_quotes']} q 
            WHERE 
                q.Status='1'";
    //echo $sql;die;
    $active = DB_query($sql);
    $numq = DB_numRows($active);
    $content = $href . '&nbsp;(' . $numq . ')<br />';
    //link to the category index
    $active = DB_query("SELECT * FROM {$_TABLES['dailyquote_lookup']} 
            WHERE Status='1' 
            GROUP BY CID");
    $catnum = DB_numRows($active);
    if ($catnum > '1') {
        $href = is_curpage('catindex.php',$LANG_DQ['catindexlink']);
        $content .= $href . '&nbsp;(' . $catnum . ')<br />';
    }
    //link to the search form
    $href = is_curpage('search.php',$LANG_DQ['searchlink']);
    $content .= $href . '<br />';
    //link to the add form
    if ((($anonadd == '1') && ($_USER['uid'] < 2)) || (($loginadd == '1') && ($_USER['uid'] > 2)) || (SEC_hasRights('dailyquote.add'))) {
        $href = is_curpage('add.php',$LANG_DQ['addformlink']);
        $content .= $href . '<br />';
    }
    //link to the batch add form
    if (SEC_hasRights('dailyquote.add') || 
        ($loginbatch == '1' && $_USER['uid'] > 2)) {
        $href = is_curpage('add2.php',$LANG_DQ['batchaddlink']);
        $content .= $href . '<br />';
    }
    //link to the moderation page
    if (SEC_hasRights('dailyquote.edit')) {
        $modnum = DB_count($_TABLES['dailyquote_quotes'], 'Status', '0');
        if ($modnum > '0') {
            $href = is_curpage('moderate.php',$LANG_DQ['moderationlink']);
            $content .= $href . '&nbsp;(' . $modnum . ')<br />';
        }
    }
    //link to the manage form
    if (SEC_hasRights('dailyquote.edit')) {
        $href = is_curpage('manage.php',$LANG_DQ['managelink']);
        $content .= $href . '<br />';
    }
    //link to the category management page
    if (SEC_hasRights('dailyquote.edit')) {
        $href = is_curpage('catman.php',$LANG_DQ['catmanlink']);
        $content .= $href . '<br />';
    }
    return $content;
}


//ensures that the dqmenu uses the same left block template files as the rest of the left blocks.
$_BLOCK_TEMPLATE['dqmenu'] = 'blockheader-left.thtml,blockfooter-left.thtml';

//returns the dq menu block and other left blocks when on plugin pages
function dqmenu()
{
    $a = array('dqmenu');
    $retval = custom_showBlocks($a);
    $retval .= COM_showBlocks( 'left', $topic );
    return $retval;
}


//displays a row of links
function link_row()
{
    global $_TABLES, $_CONF, $_USER, $LANG_DQ, $_CONF_DQ;

    // Retrieve access settings
    $query = DB_query("SELECT anonadd, anonview, loginadd, loginbatch FROM {$_TABLES['dailyquote_settings']}");
    list($anonadd,$anonview,$loginadd,$loginbatch) = DB_fetchArray($query);

    $T = new Template($_CONF['path'] . 'plugins/' . $_CONF_DQ['pi_name'] . '/templates');
    $T->set_file('page', 'linkrow.thtml');

    $index = "&nbsp;&nbsp;<a href=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/index.php\">";
    //$index .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/index.gif" . "\" alt=\"";
    $index .= '&gt;' . $LANG_DQ['indexlink'];
    //$index .= "\" border=\"0\" />";
    $index .= "</a>&nbsp;&nbsp;";

    //link to the add form
    if (!eregi('add.php', $_SERVER['REQUEST_URI'])) {
        if ((($anonadd == '1') && ($_USER['uid'] < 2)) || (($loginadd == '1') && ($_USER['uid'] > 2)) || (SEC_hasRights('dailyquote.add'))) {
            $link = "&nbsp;&nbsp;<a href=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/add.php\">";
            //$link .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/add.gif" . "\" alt=\"";
            $link .= '&gt;' . $LANG_DQ['addformlink'];
            //$link .= "\" border=\"0\" />";
            $link .= "</a>&nbsp;&nbsp;";
            $T ->set_var('addformlink', $link);
        }
    } else {
        $T->set_var('indexlink', $index);
    }
    //link to the batch add form
    if (!eregi('add2', $_SERVER['REQUEST_URI'])) {
        if (SEC_hasRights('dailyquote.add') || 
            ($loginbatch == '1' && $_USER['uid'] > 2)) {
            $batch = "&nbsp;&nbsp;<a href=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/add2.php\">";
            //$batch .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/batch.gif" . "\" alt=\"";
            $batch .= '&gt;' . $LANG_DQ['batchaddlink'];
            //$batch .= "\" border=\"0\" />";
            $batch .= "</a>&nbsp;&nbsp;";
            $T ->set_var('batchaddlink', $batch);
        }
    } else {
        $T->set_var('indexlink', $index);
    }

    //link to the manage form
    if (!eregi('manage', $_SERVER['REQUEST_URI'])) {
        if (SEC_hasRights('dailyquote.edit')) {
            $manage = "&nbsp;&nbsp;<a href=\"" . $_CONF['site_url'] . "/dailyquote/manage.php\">";
            //$manage .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/manqt.gif" . "\" alt=\"";
            $manage .= '&gt;' . $LANG_DQ['managelink'];
            //$manage .= "\" border=\"0\" />";
            $manage .= "</a>&nbsp;&nbsp;";
            $T ->set_var('managelink', $manage);
        }
    } else {
        $T->set_var('indexlink', $index);
    }

    //link to the moderation page
    if (SEC_hasRights('dailyquote.edit')) {
        $modnum = DB_count($_TABLES['dailyquote_quotes'], 'Status', '0');
        if ($modnum > '0') {
            $mod = "&nbsp;&nbsp;<a href=\"" . $_CONF['site_url'] . "/dailyquote/moderate.php\">";
            //$mod .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/mod.gif" . "\" alt=\"";
            $mod .= '&gt;' . $LANG_DQ['moderationlink'];
            //$mod .= "\" border=\"0\" />";
            $mod .= "</a>&nbsp;&nbsp;";
            $T->set_var('moderationlink', $mod);
        }
    }
    //link to the category management page
    if (!eregi('catman', $_SERVER['REQUEST_URI'])) {
        if (SEC_hasRights('dailyquote.edit')) {
            $catman = "&nbsp;&nbsp;<a href=\"" . $_CONF['site_url'] . "/dailyquote/catman.php\">";
            //$catman .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/mancat.gif" . "\" alt=\"";
            $catman .= '&gt;' . $LANG_DQ['catmanlink'];
            //$catman .= "\" border=\"0\" />";
            $catman .= "</a>&nbsp;&nbsp;";
            $T->set_var('catmanlink', $catman);
        }
    } else {
        $T->set_var('indexlink', $index);
    }
    //link to the search form
    if (!eregi('search', $_SERVER['REQUEST_URI'])) {
        $search = "&nbsp;&nbsp;<a href=\"" . $_CONF['site_url'] . "/dailyquote/search.php\">";
        //$search .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/search.gif" . "\" alt=\"";
        $search .= '&gt;' . $LANG_DQ['searchlink'];
        //$search .= "\" border=\"0\" />";
        $search .= "</a>&nbsp;&nbsp;";
        $T->set_var('searchlink', $search);
    } else {
        $T->set_var('indexlink', $index);
    }
    //link to the catlist page
    if (!eregi('catindex', $_SERVER['REQUEST_URI'])) {
        $catnum = DB_count($_TABLES['dailyquote_cat'], 'Status', '1');
        if ($catnum > '1') {
            $catlst = "&nbsp;&nbsp;<a href=\"" . $_CONF['site_url'] . "/dailyquote/catindex.php\">";
            //$catlst .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/cats.gif" . "\" alt=\"";
            $catlst .= '&gt;' . $LANG_DQ['catindexlink'];
            //$catlst .= "\" border=\"0\" />";
            $catlst .= "</a>&nbsp;&nbsp;";
            $T->set_var('catindexlink', $catlst);
        }
    } else {
        $T->set_var('indexlink', $index);
    }

    $T->parse('output','page');
    $retval = $T->finish($T->get_var('output'));
    return $retval;
}


// function that displays a random quote from your database in a phpblock if you have the dailyquote
// plugin installed.
// easily modified to use without the dailyquote plugin--just point to your own table and fields.
function phpblock_dailyquote_random_quote()
{
    global $_CONF, $_CONF_DQ;
   
    $retval = '';
 
    $row = DQ_getQuote();
    if (is_array($row)) {
        $T = new Template($_CONF['path'] . 'plugins/' . $_CONF_DQ['pi_name'] . '/templates');
        $T->set_file('page', 'blk_random.thtml');
        $T->set_var('randomquote', htmlspecialchars($row['Quotes']));
        $T->set_var('quoted', $row['Quoted']);
        $T->set_var('sourcedate', $row['Sourcedate']);
        $T->set_var('source', $row['source']);
        $T->parse('output','page');
        $retval = $T->finish($T->get_var('output'));
   }

    return $retval;

}


//displays a random quote box at the top of the regular listings
function random_quote($id='')
{
    global $_CONF, $LANG_DQ, $_CONF_DQ;

    $dqarray = DQ_getQuote($id);
    if (!is_array($dqarray)) {
        return '';
    }

    list($Quote, $Quoted, $Tit, $Source, $Sourcedate, $username, $Date, $catlist) = $dqarray;
    if (empty($Quote)) {
        return '';
    }

    $T = new Template($_CONF['path'] . 'plugins/' . $_CONF_DQ['pi_name'] . '/templates');
    $T->set_file('page', 'randomquotebox.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('randomboxtitle', $LANG_DQ['randomboxtitle']);
    if ($_CONF_DQ['phpblk_title'] == 1) {
        $T->set_var('title', $Tit);
    } else {
        $T->set_var('title', '');
    }
    $T->set_var('randomquote', $Quote);
    $T->set_var('quoted', $Quoted);
    if ($_CONF_DQ['phpblk_srcdate'] == 1) {
        if (!empty($Source)) {
            $dispsource = "&nbsp;--&nbsp;" . $Source;
            $T->set_var('source', $dispource);
        }
        if (!empty($Sourcedate)) {
            $dispsourcedate = "&nbsp;&nbsp;(" . $Sourcedate . ")";
            $T->set_var('sourcedate', $dispsourcedate);
        }
    }
    if (($_CONF_DQ['phpblock_contribdate'] == 1) && ($username != $LANG_DQ['anonymous'])) {
        $T->set_var('subm_by', $LANG_DQ['subm_by'] . ':&nbsp;');
        $T->set_var('dispcontr', $username);
        $T->set_var('dispcontr', $username);
        $T->set_var('datecontr', '&nbsp;/&nbsp;' . $Date);
    }
    if ($_CONF_DQ['phpblock_categories'] == 1) {
        $T->set_var('cat', '&nbsp;&nbsp;' . $LANG_DQ['cat'] . ':&nbsp;');
        $T->set_var('dispcat', $catlist);
    }
    $T->parse('output','page');
    $retval = $T->finish($T->get_var('output'));
    return $retval;
}


//displays a row of sort by options for the display page
function sort_by_menu()
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'sortnav.thtml');
    $T ->set_var('site_url', $_CONF['site_url']);
    $T ->set_var('sortby', $LANG_DQ['sortby']);
    $T ->set_var('sortopt1', $LANG_DQ['sortopt1']);
    $T ->set_var('sortopt2', $LANG_DQ['sortopt2']);
    $T ->set_var('sortopt3', $LANG_DQ['sortopt3']);
    $T ->set_var('sortopt4', $LANG_DQ['sortopt4']);
    $T ->set_var('sortopt8', $LANG_DQ['sortopt8']);
    $T ->set_var('ascopt1', $LANG_DQ['ascopt1']);
    $T ->set_var('ascopt2', $LANG_DQ['ascopt2']);
    $T ->set_var('submit', $LANG_DQ['sort']);

    $T->parse('output','page');
    $retval = $T->finish($T->get_var('output'));
    return $retval;
}


// Select default group for ad access (viewing and editing)
function plugin_configmanager_select_defgrp_dailyquote()
{
    return DQ_getGroups('\'All Users\'', 'NOT');
}


/**
 *  @function DQ_getGroups()
 *  @param $grplist string Comma-separated list of groups, SQL format
 *  @param $NOT string Set to "NOT" to exclude $grplist, blank to include it.
 */
function DQ_getGroups($grplist='', $NOT='')
{
    global $_TABLES;

    $A = array();

    $query = "SELECT grp_id, grp_name FROM {$_TABLES['groups']}";

    // If a group list is specified, add it as a condition with the
    // specified keyword.
    if ($grplist!= '') {
        $query .= " WHERE grp_name $NOT IN ($grplist)";
    }

    $result = DB_query($query);
    if ($result && DB_numRows($result) > 0) {
        while ($row = DB_fetchArray($result)) {
            $A[$row['grp_name']] = $row['grp_id'];
        }
    }

    return $A;

}


/**
* returns list of moderation values
*
* The array returned contains (in order): the row 'id' label, main plugin
* table, moderation fields (comma seperated), and plugin submission table
*
* @return       array        Returns array of useful moderation values
*
*/
function plugin_moderationvalues_dailyquote() 
{
    global $_TABLES;

    return array(
        'id', 
        $_TABLES['dailyquote_quotes'], 
        'id, Quotes, Quoted, Title, Source, Sourcedate, Date, UID',
        $_TABLES['dailyquote_submission']);
}


/**
* show items for moderation on submissions page
*
* Uses the Plugin class to return data required by moderation.php to list
* plugin objects that need to be moderated.
*
* @return       Plugin       return Plugin class containing submission data
*/
function plugin_itemlist_dailyquote() 
{
    global $_TABLES, $LANG_DQ;

    if (plugin_ismoderator_dailyquote()) {
        $plugin = new Plugin();
        //$plugin->submissionlabel = $LANG_DQ['menuitem'];
        $plugin->submissionlabel = 'Daily Quotes';
        $plugin->getsubmissionssql = "SELECT id, Quoted, Quotes FROM "
                                   . $_TABLES['dailyquote_submission'];
        $plugin->addSubmissionHeading('Quote From');
        $plugin->addSubmissionHeading('Quote Text');

        return $plugin;
    }
}


/**
* Returns the current user's ability to moderate the plugin
*
* Checks that the current user has the rights to moderate the
* plugin, returns true if this is the case, false otherwise
*
* @return        boolean       Returns true if moderator
*
*/
function plugin_ismoderator_dailyquote() 
{
    return SEC_hasRights('dailyquote.admin');
}


/**
* Performs plugin exclusive work for items approved by moderation
*
* While moderation.php handles the actual move from <plugin>submission
* to <plugin> tables, within the function we handle all other approval
* relate tasks
*
* @param      string       $id      Identifying string
* @return     string       Any wanted HTML output
*
*/
function plugin_moderationapprove_dailyquote($id, $owner_id=0)
{
    global $_TABLES, $_USER, $_CONF, $_CONF_DQ;

    DB_query("UPDATE {$_TABLES['dailyquote_quotes']}
            SET Status = 1
            WHERE id='$id'");

    $sql = "SELECT Name
           FROM {$_TABLES['dailyquote_cat_sub']}
           WHERE QID = '$id'";
    $result = DB_query($sql);
    while ($cats = DB_fetchArray($result, false)) {
        @DB_query("INSERT IGNORE INTO {$_TABLES['dailyquote_cat']}
                (Name, Status)
            VALUES
                ('{$cats['Name']}', 1)");

        // Now get the DB ID of the category.  If it was inserted,
        // then the insertID will have it.  If it was existing, then
        // we have to look it up the hard way.
        $catid = DB_insertID();
        if ($catid == 0) {
            $catid = DB_getItem(
                    $_TABLES['dailyquote_cat'],
                    'ID', 
                    "Name='{$cats['Name']}'");
        }
        $catlist[] = $catid;
    }

    // Now, add records to the lookup table to link the categories
    // to the quote.
    foreach($catlist as $catid) {
        $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_lookup']}
                (QID, CID, UID, Status)
            VALUES (
                '$id', $catid, $owner_id, 1
            )";
        //echo $sql;
        @DB_query($sql);
    }

    // Finally, delete the category submissions
    DB_query("DELETE FROM {$_TABLES['dailyquote_cat_sub']}
            WHERE QID='$id'");

    return '';
}


/**
* Returns the plugins submission form
* Returns a string contain the HTML of the submission form for the plugin.
*
* @return       string       The HTML form
*/
function plugin_submit_dailyquote() 
{
    global  $HTTP_POST_VARS, $LANG12, $_CONF, $_CONF_DQ; 

    // If mode = preview or editsubmission, load the ad info.
    if ($HTTP_POST_VARS['mode'] == $LANG12[32] ||
        $HTTP_POST_VARS['mode'] == 'editsubmission') {
        $A = $HTTP_POST_VARS;
    } else {
        $A = array();
    }

    require_once "{$_CONF['path']}/plugins/{$_CONF_DQ['pi_name']}/submitform.php";
    return DQ_editForm('submit', $A);

}


function plugin_savesubmission_dailyquote($A)
{
    global $_TABLES, $_USER, $_CONF, $_CONF_DQ, $LANG12, $LANG_DQ;

    // Initialize error message.  If it's still empty at the end, then
    // the save was successful.  Otherwise, redisplay the submission
    // form with the error message at the top.
    $errmsg = '';

    //print_r($A);die;
    if (empty($_USER['uid'])) {
        return false;       // Catastrophic error, just return
    } else {
        $owner_id = $_USER['uid'];
    }

    // check for missing fields
    if (empty($A['title']) || empty($A['quote'])) {
        COM_resetSpeedlimit('submit');
        $errmsg .= "<li>". $LANG_DQ['missing_req_fields']. "</li>\n";
    }

    // Determine which table and success message to display
    if (SEC_hasRights($_CONF_DQ['pi_name']. '.submit') || $_CONF_DQ['submission'] == false) {
        $table = 'dailyquote_quote';
        $msg = '02';    // "ad has been save" message
    } else {
        $table = 'dailyquote_submission';
        $msg = '01';    // "added to queue" message
    }

    if ($errmsg == '') {        // Only save if no errors so far
        if ($_POST['mode'] == $LANG12[8] ||
            $_POST['mode'] == $LANG_ADMIN['save']) {     // mode = Submit
            // This is a user-submitted ad
            $A['id'] = COM_makeSid();
            $A['uid'] = $owner_id;
            $A['dt_add'] = time();

            if (DQ_insertSubmission($A, $table))
                return false;       // Catastrophic failure
        }

    }    // if $errormsg == ''

    // If still no error message, return to the home page with
    // the 'success' message.  Otherwise, redisplay the submission form
    // with the error message
    if ($errmsg == '') {
        return COM_refresh($_CONF['site_url']
                          . "/index.php?msg=$msg&amp;plugin=". $_CONF_DQ['pi_name']);
    } else {
        $display = COM_siteHeader();
        $display .= "<span class=alert><ul>$errmsg</ul></span\n";
        $A['ad_id'] = '';   // reset so it'll still look like a new entry
        require_once "{$_CONF['path']}/plugins/{$_CONF_DQ['pi_name']}/submitform.php";
        $display .= DQ_editForm('submit', $A);
        $display .= COM_siteFooter();
        return $display;
    }

}


function DQ_updateQuote($A, $table='dailyquote_quote')
{
    global $_TABLES, $_USER;

    if (!is_array($A) || empty($A))
        return '';

    if ($table != 'dailyquote_submission') {
        $table = $_TABLES['dailyquote_quotes'];
        $cat_table = $_TABLES['dailyquote_cat'];
    } else {
        $cat_table = $_TABLES['dailyquote_cat_sub'];
        $table = $_TABLES['dailyquote_submission'];
    }

    // Delete all links from the lookup table.
    DB_query("DELETE FROM {$_TABLES['dailyquote_lookup']} WHERE QID='{$A['id']}'");

    // Insert the quote into the main submission table
    $sql = "
        UPDATE
            $table
        SET
            Quotes = '" . glfPrepareForDB(COM_checkwords($A['quote'])). "',
            Quoted = '" . glfPrepareForDB(COM_checkwords($A['quoted']) ). "',
            Title = '" . glfPrepareForDB(COM_checkwords($A['title'])) . "',
            Source = '" . glfPrepareForDB(COM_checkwords($A['source'])) . "',
            Sourcedate = '" . glfPrepareForDB(COM_checkwords($A['sourcedate'])) . "'
        WHERE
            id = '{$A['id']}'
    ";
    DB_query($sql);
    if (DB_error())
        return DB_error();

    // Add categories
    $catlist = array();     // used to populate lookup table
    foreach($A['cat'] as $key=>$catname) {
        if ($catname == '') continue;
        if ($cat_table == 'dailyquote_cat_sub') {
            $sql = "
                INSERT INTO
                    $cat_table
                    (QID, Name)
                VALUES (
                    '{$A['id']}',
                    '" . glfPrepareForDB(COM_checkwords($catname)) . "'
                )";
            DB_query($sql);
        } else {
            $sql = "
                INSERT IGNORE INTO
                    $cat_table
                    (Name)
                VALUES (
                    '" . glfPrepareForDB(COM_checkwords($catname)) . "'
                )";
            DB_query($sql);
            $catid = DB_insertID();
            if ($catid == 0) {
                $catid = DB_getItem(
                        $_TABLES['dailyquote_cat'],
                        'ID', 
                        "Name='$catname'");
            }
            $catlist[] = $catid;
        }

        if (DB_error())
            return DB_error();
    }

    // Now, add records to the lookup table to link the categories
    // to the quote.  Only if bypassing the submission queue; if
    // the queue is used $catlist will be empty.
    if (!empty($catlist)) {
    foreach($catlist as $catid) {
        $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_lookup']}
                (QID, CID, UID, Status)
            VALUES (
                '{$A['id']}', $catid, {$A['uid']}, 1
            )";
        //echo $sql;
        @DB_query($sql);
    }
    }


    return '';

}


function DQ_insertSubmission($A, $table='dailyquote_submission')
{
    global $_TABLES, $_USER;

    if ($table != 'dailyquote_submission') {
        $table = $_TABLES['dailyquote_quotes'];
        $cat_table = $_TABLES['dailyquote_cat'];
    } else {
        $cat_table = $_TABLES['dailyquote_cat_sub'];
        $table = $_TABLES['dailyquote_submission'];
    }

    // Insert the quote into the main submission table
    $sql = "
        INSERT INTO
            $table
            (id, Quotes, Quoted, Title, Source, Sourcedate, Date, UID)
        VALUES (
            '{$A['id']}',
            '" . glfPrepareForDB(COM_checkwords($A['quote'])). "',
            '" . glfPrepareForDB(COM_checkwords($A['quoted']) ). "',
            '" . glfPrepareForDB(COM_checkwords($A['title'])) . "',
            '" . glfPrepareForDB(COM_checkwords($A['source'])) . "',
            '" . glfPrepareForDB(COM_checkwords($A['sourcedate'])) . "',
            {$A['dt_add']},
            '{$A['uid']}'
        )";
    DB_query($sql);
    if (DB_error())
        return DB_error();

    // Add categories
    $catlist = array();     // used to populate lookup table
    foreach($A['cat'] as $key=>$catname) {
        if ($catname == '') continue;
        if ($cat_table == 'dailyquote_cat_sub') {
            $sql = "
                INSERT INTO
                    $cat_table
                    (QID, Name)
                VALUES (
                    '{$A['id']}',
                    '" . glfPrepareForDB(COM_checkwords($catname)) . "'
                )";
            DB_query($sql);
        } else {
            $sql = "
                INSERT IGNORE INTO
                    $cat_table
                    (Name)
                VALUES (
                    '" . glfPrepareForDB(COM_checkwords($catname)) . "'
                )";
            DB_query($sql);
            $catid = DB_insertID();
            if ($catid == 0) {
                $catid = DB_getItem(
                        $_TABLES['dailyquote_cat'],
                        'ID', 
                        "Name='$catname'");
            }
            $catlist[] = $catid;
        }

        if (DB_error())
            return DB_error();
    }

    // Now, add records to the lookup table to link the categories
    // to the quote.  Only if bypassing the submission queue; if
    // the queue is used $catlist will be empty.
    if (!empty($catlist)) {
    foreach($catlist as $catid) {
        $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_lookup']}
                (QID, CID, UID, Status)
            VALUES (
                '{$A['id']}', $catid, {$A['uid']}, 1
            )";
        //echo $sql;
        @DB_query($sql);
    }
    }


    return '';

}


function plugin_getListField_dailyquote($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_DQ;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
            $icon_arr['edit'],
            "{$_CONF['site_admin_url']}/plugins/dailyquote/index.php?mode=edit&amp;id={$A['id']}"
        );
        break;
    case 'Title':
        $retval = stripslashes($A['Title']);
        break;
    case 'Quotes':
        $retval = substr(stripslashes($A['Quotes']), 0, 100);
        break;
    case 'Date';
        $retval = date('Y-m-d', $A['Date']);
        break;
    default:
        $retval = $fieldvalue;
        break;
    }

    return $retval;
}


/**
* Returns the URL of the plugin's icon
*
* @return   string      URL of the icon
*/
function plugin_geticon_dailyquote()
{
    global $_CONF;

    return $_CONF['site_url'] . '/dailyquote/images/dailyquote.gif';
}


/**
* Implements the [dailyquote:] autotag.
*/
function plugin_autotags_dailyquote($op, $content = '', $autotag = '')
{
    global $_CONF, $_TABLES, $_CONF_DQ;

    switch ($op) {
    case 'tagname':
        $retval = 'dailyquote';
        break;
    case 'parse':
        $HTML = '';
        $option = COM_applyFilter($autotag['parm1']);

        switch ($option) {
        case 'random':
            $quote = DQ_getQuote();
            return htmlspecialchars($quote['Quotes']);
            break;

        case 'quoted':
        case 'quotes':
            $qid = COM_sanitizeID($autotag['parm2']);
            if ($qid != '') {
                $quote = DQ_getQuote($qid);
                return htmlspecialchars($quote['Quotes']);
            } else {
                return '';
            }

        } // switch($option)

        $retval = str_replace($autotag['tagstr'],$HTML,$content);
        break;

    default:
        $retval = $content;
        break;

    }   // switch ($op)

    return $retval;

}

?>
