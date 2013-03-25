<?php
//  $Id$
/**
*   Common functions for the DailyQuote plugin
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.1.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

/** @global array $_CONF_DQ */
global $_CONF_DQ;

//  Check and see if we need to load the plugin configuration
if (!isset($_CONF_DQ['quoteperpage'])) {
    /** Import configuration-handling class */
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    $dq_config = config::get_instance();
    $_CONF_DQ= $dq_config->get_config('dailyquote');
}

/** Additional config items and table definitions are added here */
require_once $_CONF['path'] . '/plugins/dailyquote/dailyquote.php';
if (file_exists($_CONF['path'] . '/plugins/dailyquote/config.php')) {
    /** Include local configuration overrides */
    include_once $_CONF['path'] . '/plugins/dailyquote/config.php';
}

define('DQ_PI_PATH', 
    $_CONF['path'].'plugins/'.$_CONF_DQ['pi_name']);
define('DQ_ADMIN_URL', 
    $_CONF['site_url'].'/admin/plugins/'.$_CONF_DQ['pi_name']);
define('DQ_URL', 
    $_CONF['site_url'].'/'.$_CONF_DQ['pi_name']);


/**
* Language file Include
*/
$langpath = DQ_PI_PATH . '/language';
$langfile = "{$_CONF['language']}.php";
if (!file_exists("$langpath/$langfile")) {
    $langfile = 'english.php';
}
require_once "$langpath/$langfile";


function USES_dailyquote_submitform()
{   require_once DQ_PI_PATH . '/submitform.php';    }

function USES_dailyquote_functions()
{   require_once DQ_PI_PATH . '/dailyquote_functions.inc.php';  }

function USES_dailyquote_class_quote()
{   require_once DQ_PI_PATH . '/classes/quote.class.php';   }

function USES_dailyquote_class_category()
{   require_once DQ_PI_PATH . '/classes/category.class.php';    }

function USES_dailyquote_batch()
{   require_once DQ_PI_PATH . '/batch.php'; }


/**
*   Returns the items for this plugin that should appear on the main menu.
*
*   @return    array   Associative array of menu items to be added to menu
*/
function plugin_getmenuitems_dailyquote()
{
    global $_CONF, $_CONF_DQ;

    $menuitems = array();

    // Check user has rights to view this plugin, if so, do it.
    if ($_CONF_DQ['anonview'] == '1' || !COM_isAnonUser()) {
        $menuitems[$_CONF_DQ['pi_display_name']] = 
            DQ_URL . '/index.php';
    }

    return $menuitems;
}


/**
 *  Get additional Javascript into the site header.
 *
 *  @return array List of paths to Javascript files
 */
function plugin_getheaderjs_dailyquote()
{
    global $_CONF, $_CONF_DQ; 

    $scripts = array();
    $scripts[] = DQ_PI_PATH . '/js/toggleEnabled.js';

	return $scripts;
}


/**
*   Shows the statistics for the plugin on stats.php.
*   If $showsitestats is 1 then we are to only print the overall stats in 
*   the 'site statistics' box otherwise we show the detailed stats for the 
*   plugin.
*
*   @param  integer  $showsitestate   Flag to let us know which stats to get
*   @return string  formatted HTML to insert in stats page
*/
function plugin_showstats_dailyquote($showsitestats)
{
    global $_CONF, $_USER, $_TABLES, $LANG_DQ, $_CONF_DQ;

    USES_lib_admin();

    $retval = '';

    // Check user has rights to view this, if so, do it.
    if ($_CONF_DQ['default_permissions'][3] < 2 && $_USER['uid'] < 2) {
        return '';
    }

    $T = new Template($_CONF['path_layout'] . 'stats');
    $T->set_file(array('itemstats'=>'itemstatistics.thtml',
                                'statrow'=>'singlestat.thtml'));
    /*if ($showsitestats == 1) {
        $total_pages = DB_count($_TABLES['dailyquote_quotes'], 'enabled', '1');
        $retval .= "<table border = '0' width='100%' cellspacing='0' cellpadding='0'>";
        $retval .= "<tr><td>" . $LANG_DQ['noquotes'] . "</td>";
        $retval .= "<td align='right'>" . $total_pages . "&nbsp;&nbsp </td></tr></table>";
        return $retval;
    }*/

    $result = DB_query("SELECT 
                            q.quoted, COUNT(*) 
                        FROM 
                            {$_TABLES['dailyquote_quotes']} q 
                        LEFT JOIN
                            {$_TABLES['dailyquote_quoteXcat']} l
                            ON q.id = l.qid
                        LEFT JOIN
                            {$_TABLES['dailyquote_cat']} c
                            ON l.cid = c.id
                        WHERE 
                            q.enabled= '1' AND c.enabled = '1' 
                        GROUP BY quoted 
                        ORDER BY 2 DESC, 1 ASC 
                        LIMIT 10"
    );
    $nrows  = DB_numRows($result);
    $retval .= COM_startBlock($LANG_DQ['StatsMsg1']);
    if ($nrows > 0) {
        $T->set_var('item_label', $LANG_DQ['StatsMsg3']);
        $T->set_var('stat_name', $LANG_DQ['StatsMsg4']);
        
        while ($A = DB_fetchArray($result)) {
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
    global $LANG_DQ, $_TABLES;

    // This shows in the summary box
    $total_items = DB_count($_TABLES['dailyquote_quotes']);
    $summary_label = $LANG_DQ['quotes_in_db'];

    $retval = array($summary_label, $total_items);
    return $retval;
}


/**
*   This will put an option for the plugin in the command and control 
*   block on moderation.php
*
*   @return   array   Array containing (pi_name, pi_admin_url, pi_icon_url)
*/
function plugin_cclabel_dailyquote()
{
    global $_CONF, $_CONF_DQ;

    if (SEC_hasRights('dailyquote.admin')) {
        return array(
            $_CONF_DQ['pi_display_name'],
            DQ_ADMIN_URL . '/index.php',
            DQ_URL . '/images/dailyquote.png'
        );
    } else {
        return '';
    }
}


/**
*   Returns the administrative menu option for this plugin.
*
*   @return mixed   Array (pi_name, pi_admin_url, # of items) or ''
*/
function plugin_getadminoption_dailyquote()
{
    global $_CONF, $_TABLES, $LANG_DQ, $_CONF_DQ;

    if (SEC_hasRights('dailyquote.admin')) {
        $count = DB_count($_TABLES['dailyquote_quotes'], 'enabled', 1);
        return array(
            $LANG_DQ['admin_menu'], 
            DQ_ADMIN_URL . '/index.php', 
            $count
        );
    } else {
        return '';
    }
}


/**
*   Provide any new items that show up in the type drop-down on search.php.i
*   @return array   Single name=>display_name value for the selection
*/
function plugin_searchtypes_dailyquote() 
{
    global $LANG_DQ;

    $tmp['dailyquote'] = $LANG_DQ['glsearchlabel'];
    return $tmp;
}


/**
*   Search for pages matching the user query and returns an array of 
*   for the header and table rows back to search.php where it will be 
*   formated and printed 
*
*   @param    string  $query      Keywords user is looking for
*   @param    date    $datestart  Start date to get results for
*   @param    date    $dateend    End date to get results for
*   @param    string  $topic      The topic they were searching in
*   @param    string  $type       Type of items they are searching, or 'all'
*   @param    int     $author     Get all results by this author
*   @param    string  $keyType    search key type: 'all', 'phrase', 'any'
*   @param    int     $page       page number of current search
*   @param    int     $perpage    number of results per page
*   @return   object              search result object
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
    global $_TABLES, $_CONF, $_USER, $LANG_DQ, $_CONF_DQ;

    // Check user has rights to view this results, if so, do it.
    if ($_CONF_DQ['anonview'] != 1  && COM_isAnonUser()) {
        return '';
    }

    $htmlquery = urlencode($query);

    $query = trim(DB_escapeString(htmlspecialchars($query)));

    // Build search SQL
    $sql = "SELECT 
            q.id as id, q.quote as description, 
            q.title as title, q.uid as uid, 
            q.dt as date,
            CONCAT('/{$_CONF_DQ['pi_name']}/index.php?qid=',q.id,'&query=$htmlquery') as url
        FROM 
            {$_TABLES['dailyquote_quotes']} q
        WHERE 
            q.enabled = 1
    ";

    if (!empty($datestart) && !empty($dateend)) {
            $delim = substr($datestart, 4, 1);
            $DS = explode($delim,$datestart);
            $DE = explode($delim,$dateend);
            $startdate = mktime(0,0,0,$DS[1],$DS[2],$DS[0]);
            $enddate = mktime(0,0,0,$DE[1],$DE[2],$DE[0]) + 3600;
            $sql .= " AND (q.dt BETWEEN '$startdate' AND '$enddate') ";
    }

    if (!empty($author)) {
        $sql .= " AND (q.uid='$author') ";
    }

    $search = new SearchCriteria($_CONF_DQ['pi_name'], $_CONF_DQ['pi_display_name']);
    $columns = array('q.title', 'q.source', 'q.quoted', 'q.quote');
    list($sql, $ftsql) = $search->buildSearchSQL($keyType, $query, $columns, $sql);
    //echo $sql;
    $search->setSQL($sql);

    return $search;

}


/**
*   Whats New Block API Support.
*
*   @return mixed   Array of header data for block, or fals if block disabled.
*/
function plugin_whatsnewsupported_dailyquote()
{
    global $_CONF, $LANG_DQ, $_CONF_DQ;

    if ($_CONF_DQ['whatsnew'] == '0' || 
            ($_CONF_DQ['anonview'] == '0') && COM_isAnonUser()) {
        return false;
    } else {
        return array(
            $LANG_DQ['whatsnewlabel'],
            sprintf($LANG_DQ['whatsnewperiod'], $_CONF_DQ['whatsnewdays']));
    }
}


/**
*   API function provides the content of our "What's New" feed.
*
*   @return array   Array of links
*/
function plugin_getwhatsnew_dailyquote()
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;

    $days = (int)$_CONF_DQ['whatsnewdays'];

    $sql = "SELECT DISTINCT 
                q.id, q.quote
            FROM 
                {$_TABLES['dailyquote_quotes']} q 
            LEFT JOIN
                {$_TABLES['dailyquote_quoteXcat']} l
                ON q.id = l.qid
            LEFT JOIN
                {$_TABLES['dailyquote_cat']} c
                ON c.id = l.cid
            WHERE
                q.enabled='1' 
            AND
                c.enabled='1'
            AND 
                q.dt >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$days} DAY))
            ORDER BY q.dt DESC 
            LIMIT 15 ";
    //echo $sql;die;
    $result = DB_query($sql);
    $nrows = DB_numRows($result);
    if ($nrows > 0) {
        $T = new Template(DQ_PI_PATH . '/templates');
        $T->set_file('block', 'blk_whatsnew.thtml');
        while ($row = DB_fetchArray($result, false)) {
            $quote = htmlspecialchars($row['quote']);
            $itemlen = strlen($quote);
            if ($itemlen > 16) {
                $quote_text = substr($quote, 0, 16) . '...';
            } else {
                $quote_text = $row['quote'];
            }
            //$items[] = '<a href="' . DQ_URL . '/index.php?qid=' . $row['id']
            //            . '" title="' . $quote . '">'
            //            . $quote_text . '</a>' . "\n";
            $T->set_var('quote_link', DQ_URL . '/index.php?qid=' . $row['id']);
            $T->set_var('quote_title', $quote);
            $T->set_var('quote_text', $quote_text);
            $T->set_var('XHTML', XHTML);
            $T->parse('output', 'block');
            $items .= $T->finish($T->get_var('output'));
        }
        return $items;
    } else {
        return $LANG_DQ['newempty'] . '<br ' . XHTML . '>' . LB;
    }
}


/**
*   Return the current version of code.
*
*   @return string      Current plugin version
*/
function plugin_chkVersion_dailyquote()
{
    global $_CONF_DQ;

    return $_CONF_DQ['pi_version'];
}


/**
 *  Automatically uninstall the plugin.
 *
 *  @return array Array of plugin elements to remove
 */
function plugin_autouninstall_dailyquote()
{
    global $_CONF_DQ;

    $out = array (
        'tables'    => array($_CONF_DQ['pi_name']. '_quotes',
                            $_CONF_DQ['pi_name']. '_submission',
                            $_CONF_DQ['pi_name']. '_cat',
                            $_CONF_DQ['pi_name']. '_quoteXcat',
                        ),
        'groups'    => array($_CONF_DQ['pi_name'] . ' Admin'),
        'features'  => array($_CONF_DQ['pi_name'] . '.add', 
                            $_CONF_DQ['pi_name'] . '.edit',
                            $_CONF_DQ['pi_name'] . '.view',
                        ),
        'php_blocks' => array('phpblock_' . $_CONF_DQ['pi_name'] . '_dqmenu',
                            'phpblock_' . $_CONF_DQ['pi_name'] . '_random',
                        ),
        'vars'      => array($_CONF_DQ['pi_name'] . '_gid')
    );

    return $out;

}


//chks for magic_quotes and acts accordingly. use only for gpc otherwise use DB_escapeString
function slashctrl($str)
{
    if (!get_magic_quotes_gpc()) {  //right from the manual :P
        $str = DB_escapeString($str);
    }
    return $str;
}


/**
*   Converts usernames to clickable links leading to user profiles.
*
*   @param  integer $uid        User ID
*   @param  string  $username   User Name
*   @return string              HTML for link to user profile
*/
function DQ_linkProfile($uid, $username)
{
    global $_CONF, $LANG_DQ;

    if ($uid > '1') {
        $retval = COM_createLink(htmlspecialchars($username),
                $_CONF['site_url'].'/users.php?mode=profile&amp;uid='.$uid);
    } else {
        $retval = $LANG_DQ['anonymous'];
    }
    return $retval;
}


/**
*   Converts categories to clickable links leading to the search page.
*
*   @param  integer $catid      Category ID
*   @param  string  $catname    Category Name
*   @return string              HTML for link
*/
function DQ_catlink($catid, $catname)
{
    global $_CONF, $_TABLES, $_CONF_DQ;

    $retval = "<a href=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/search.php?cat=$catid\">$catname</a>";
    return $retval;
}


/**
*   Create the centerblock, if needed.
*
*   @param  integer $where  Which area is being displayed now
*   @param  integer $page   Page number
*   @param  string  $topic  Topic ID, or empty string
*   @return string          HTML for centerblock
*/
function plugin_centerblock_dailyquote($where=1,$page=1,$topic ='')
{
    global $_CONF, $_USER, $_TABLES, $PHP_SELF, $LANG_DQ, $_CONF_DQ;
    
    $retval = '';
    $img_dir = $_CONF['layout_url'] . '/' . $_CONF_DQ['pi_name'] . '/image_set';

    // If centerblock not enabled, or just for homepage and
    // we're not on the homepage, just return
    if ($_CONF_DQ['cb_enable'] == 0 ||
            ($CONF_DQ['cb_homepage'] == 1 && ($page > 1 || $topic != '')) ) {
        return '';
    }

    USES_dailyquote_class_quote();

    // Check access to view quotes
    if (!DailyQuote::hasAccess(2)) {
        return '';
    }

    // Get the centerblock position.  May be overridden later.
    $cntrblkpos = $_CONF_DQ['cb_pos'];

    // If we're not supposed to replace the homepage, then return.
    // Otherwise, do so.  When $where is zero we're at the homepage.
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
        $wherenames = array('tid', 'featured', 'draft_flag');
        $wherevalues = array($topic, 0, 0);
    } else {
        $wherenames = array('featured', 'draft_flag');
        $wherevalues = array(1, 0);
    }

    $story_count = DB_count($_TABLES['stories'], $wherenames, $wherevalues);
    if ($story_count == 0 && $_CONF_DQ['cb_pos'] == 2) {
        // If the centerblock comes after the featured story, and there
        // are no stories, put the centerblock at the top.
        $cntrblkpos = 1;
    }

    // Finally, see if we're in the right area to display our block.
    if ($cntrblkpos != $where) {
        return '';
    }

    $Q = DailyQuote::getQuote();
    if (empty($Q['quote'])) {
        return '';
    }

    if ($where == 0) {
        // If replacing the homepage, we're responsible for the header
        $retval = COM_siteHeader();
    }

    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'centerblock.thtml');

    $T->set_var(array(
        'dispquote'     => $Q['quote'],
        'dispquoted'    => $Q['quoted'],
        'source'        => $Q['source'],
        'title'         => $Q['title'],a
    ) );

    $T->parse('output','page');

    $retval .= $T->finish($T->get_var('output'));

    if ($where == 0) {
        $retval .= COM_siteFooter();
    }

    return $retval;

}


/**
*   Returns blockmenu content for dqblockmenu
*   @return string      HTML for block menu
*/
function phpblock_dailyquote_dqmenu()
{
    global $_TABLES, $_CONF, $_USER, $LANG_DQ, $_CONF_DQ;

    $content = '';

    // Link to the plugin index page
    $count = DB_count($_TABLES['dailyquote_quotes'], 'enabled', '1');
    $href = COM_createLink("{$LANG_DQ['indexlink']}&nbsp;($count)",
            DQ_URL . '/index.php');
    $content .= $href . "<br />\n";

    // Link to the category index
    $count = DB_count($_TABLES['dailyquote_cat'], 'enabled', '1');
    if ($count > '0') {
        $href = COM_createLink("{$LANG_DQ['catindexlink']}&nbsp;($count)",
            DQ_URL . '/index.php?mode=categories');
        $content .= $href . "<br />\n";
    }

    //link to the search form
    $href = COM_createLink($LANG_DQ['searchlink'], DQ_URL . '/search.php');
    $content .= $href . "<br />\n";

    // Link to the submission form
    if (($anonadd == '1' && $_USER['uid'] < 2) || 
        ($loginadd == '1' && $_USER['uid'] > 2) || 
        SEC_hasRights('dailyquote.add')) {
        $href = COM_createLink($LANG_DQ['addformlink'],
            $_CONF['site_url'].'/submit.php?type='.$_CONF_DQ['pi_name']);
        $content .= $href . "<br />\n";
    }

    if (SEC_hasRights('dailyquote.edit')) {
        // Link to the manage quotes page 
        $href = COM_createLink($LANG_DQ['managelink'],
            DQ_ADMIN_URL . '/index.php');
        $content .= $href . "<br />\n";

        // Link to the category management page
        $href = COM_createLink($LANG_DQ['manage_cats'],
            DQ_ADMIN_URL . '/index.php?mode=categories');
        $content .= $href . "<br />\n";
    }

    return $content;
}


/**
*   Displays a random quote from your database in a block.
*   @return string  HTML for the block contents.
*/
function phpblock_dailyquote_random()
{
    global $_CONF, $_CONF_DQ;

    USES_dailyquote_class_quote();
 
    $retval = '';

    $row = DailyQuote::getQuote();
    if (!empty($row)) {
        $T = new Template(DQ_PI_PATH . '/templates');
        $T->set_file('page', 'blk_random.thtml');
        $T->set_var(array(
            'randomquote'   => $row['quote'],
            'quoted'        => $row['quoted'],
            'sourcedate'    => $row['sourcedate'],
            'source'        => $row['source'],
        ) );
        $T->parse('output','page');
        $retval = $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
*   Returns list of moderation values.
*
*   The array returned contains (in order): the row 'id' label, main plugin
*   table, moderation fields (comma seperated), and plugin submission table
*
*   @return array    Returns array of useful moderation values
*/
function plugin_moderationvalues_dailyquote() 
{
    global $_TABLES;

    return array(
        'id', 
        $_TABLES['dailyquote_quotes'], 
        'id, quote, quoted, title, source, sourcedate, dt, uid',
        $_TABLES['dailyquote_submission']);
}


/**
*   Show items for moderation on submissions page.
*
*   Uses the Plugin class to return data required by moderation.php to list
*   plugin objects that need to be moderated.
*
*   @return plugin  Plugin class containing submission data
*/
function plugin_itemlist_dailyquote() 
{
    global $_TABLES, $LANG_DQ, $_CONF_DQ;

    if (plugin_ismoderator_dailyquote()) {
        $plugin = new Plugin();
        $plugin->submissionlabel = $_CONF_DQ['pi_display_name'];
        $plugin->getsubmissionssql = 
                "SELECT id, quoted, title, source, uid
                FROM {$_TABLES['dailyquote_submission']}";
        $plugin->addSubmissionHeading($LANG_DQ['quoted']);
        $plugin->addSubmissionHeading($LANG_DQ['title']);
        $plugin->addSubmissionHeading($LANG_DQ['source']);

        return $plugin;
    }
}


/**
*   Returns the current user's ability to moderate the plugin.
*
*   Checks that the current user has the rights to moderate the
*   plugin, returns true if this is the case, false otherwise
*
*   @return        boolean       Returns true if moderator
*/
function plugin_ismoderator_dailyquote() 
{
    return SEC_hasRights('dailyquote.admin');
}


/**
*   Performs plugin exclusive work for items approved by moderation.
*   This just makes sure the 'enabled' flag is set to true.
*
*   @param  string  $id     ID of the item
*   @return string          Empty string to indicate success
*/
function plugin_moderationapprove_dailyquote($id)
{
    global $_TABLES;

    $id = COM_sanitizeID($id, false);
    DB_query("UPDATE {$_TABLES['dailyquote_quotes']}
            SET enabled = 1
            WHERE id='$id'");

    return '';
}


/**
*   Performs plugin exclusive work for items deleted by moderation
*
*   While moderation.php handles the actual removal from the submission
*   table, within this function we handle all other deletion
*   relate tasks
*
*   @param  string  $id     Identifying string
*   @return string          Any wanted HTML output
*/
function plugin_moderationdelete_dailyquote($id)
{
    global $_TABLES;

    // Delete the category lookup entries for the rejected quotation.
    DB_delete ($_TABLES['dailyquote_quoteXcat'], 'qid', DB_escapeString($id));

    return '';
}


/**
*   Returns the plugins submission form.
*   Returns a string contain the HTML of the submission form for the plugin.
*
*   @return       string       The HTML form
*/
function plugin_submit_dailyquote() 
{
    global  $HTTP_POST_VARS, $LANG12, $_CONF, $_CONF_DQ, $_USER, $LANG_DQ;

    // If mode = preview or editsubmission, load the ad info.
    if ($HTTP_POST_VARS['mode'] == $LANG12[32] ||
        $HTTP_POST_VARS['mode'] == 'editsubmission') {
        $A = $HTTP_POST_VARS;
    } else {
        $A = array();
    }

    if (SEC_hasRights('dailyquote.submit') ||
        (COM_isAnonUser && $_CONF_DQ['anonadd'] == 1) ||
        ((int)$_USER['uid'] > 1 && $_CONF_DQ['loginadd'] == 1)) {
        USES_dailyquote_submitform();
        return DQ_editForm($LANG12['8'], $A);
    } else {
        COM_errorLog("Someone has tried to illegally access the dailyquote submission page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        $display = COM_siteHeader();
        $display .= COM_startBlock($LANG_DQ['access_denied']);
        $display .= $LANG_DQ['access_denied_msg'];
        $display .= COM_endBlock();
        $display .= COM_siteFooter(true);
        echo $display;
        exit;
    }

}


/**
*   Saves a user submission.  If user is authorized, or the submission queue
*   is not used, save directly to the production table.  Otherwise, save
*   to the submission table.
*
*   @param  array   $A  Array of items to save, from $_POST
*   @return string      Text or HTTP redirect to display
*/
function plugin_savesubmission_dailyquote($A)
{
    global $_TABLES, $_USER, $_CONF, $_CONF_DQ, $LANG12, $LANG_DQ;

    // Initialize error message.  If it's still empty at the end, then
    // the save was successful.  Otherwise, redisplay the submission
    // form with the error message at the top.
    $errmsg = '';

    //var_dump($A);die;
    if (empty($_USER['uid'])) {
        return false;       // Catastrophic error, just return
    } else {
        $owner_id = $_USER['uid'];
    }

    // check for missing fields
    //if (empty($A['title']) || empty($A['quote'])) {
    if (empty($A['quote'])) {
        COM_resetSpeedlimit('submit');
        $errmsg .= "<li>". $LANG_DQ['missing_req_fields']. "</li>\n";
    }

    // If this user is an authorized submitter, or we're not using the
    // submission queue, then put it directly into the quotes table.
    if (SEC_hasRights($_CONF_DQ['pi_name']. '.submit')) {
        $table = 'dailyquote_quotes';
        $msg = '02';    // "has been saved" message
        $email_admin = 0;
    } elseif ($_CONF_DQ['queue'] == 0) {
        // Same tables as above, but set $email_admin
        $table = 'dailyquote_quotes';
        $msg = '02';    // "has been saved" message
        $email_admin = $_CONF_DQ['email_admin'] == 2 ? 1 : 0;
    } else {
        $table = 'dailyquote_submission';
        $msg = '01';    // "added to queue" message
        $email_admin = $_CONF_DQ['email_admin'] > 0 ? 1 : 0;
    }

    if ($errmsg == '') {        // Only save if no errors so far
        if ($_POST['mode'] == $LANG12[8] ||
            $_POST['mode'] == $LANG_ADMIN['save']) {     // mode = Submit
            // This is a user-submitted ad
            //$A['id'] = COM_makeSid();
            $A['uid'] = $owner_id;
            $A['dt_add'] = time();

            //USES_dailyquote_functions();
            //if (DQ_insertSubmission($A, $table))
            USES_dailyquote_class_quote();
            $Q = new DailyQuote();
            if ($Q->Save($A, $table) != '')
                return false;       // Catastrophic failure
        }

    }    // if $errormsg == ''

    // If still no error message, return to the home page with
    // the 'success' message.  Otherwise, redisplay the submission form
    // with the error message
    if ($errmsg == '') {
        // Send notification, if configured
        if ($email_admin == 1) {
            $T = new Template(DQ_PI_PATH . '/templates');
            $T->set_file('msg', 'email_admin.thtml');
            $T->set_var(array(
                'title'     => $A['title'],
                'quote'     => $A['quote'],
                'quoted'    => $A['quoted'],
                'subm_by'   => COM_getDisplayName($A['uid']),
            ) );
            $T->parse('output','msg');
            COM_mail($_CONF['site_mail'], $LANG_DQ['email_subject'],
                $T->finish($T->get_var('output')));
        }
        return COM_refresh($_CONF['site_url']
                    . "/index.php?msg=$msg&amp;plugin=". $_CONF_DQ['pi_name']);
    } else {
        $display = COM_siteHeader();
        $display .= "<span class=alert><ul>$errmsg</ul></span\n";
        $A['ad_id'] = '';   // reset so it'll still look like a new entry
        USES_dailyquote_submitform();
        $display .= DQ_editForm($LANG12['8'], $A);
        $display .= COM_siteFooter();
        return $display;
    }

}


/**
* Returns the URL of the plugin's icon
*
* @return   string      URL of the icon
*/
function plugin_geticon_dailyquote()
{
    global $_CONF;

    return $_CONF['site_url'] . '/dailyquote/images/dailyquote.png';
}


/**
*   Implements the [dailyquote:] autotag.
*   @param  string  $op         Requested operation
*   @param  string  $content    Page content
*   @param  string  $autotag    Autotag content
*   @return string              $content with the autotag substitutions
*/
function plugin_autotags_dailyquote($op, $content = '', $autotag = '')
{
    global $_CONF, $_TABLES, $_CONF_DQ;

    USES_dailyquote_class_quote();

    $quote = '';
    switch ($op) {
    case 'tagname':
        $retval = 'dailyquote';
        break;
    case 'parse':
        $HTML = '';
        $option = COM_applyFilter($autotag['parm1']);
        $T = new Template(DQ_PI_PATH . '/templates');
        $T->set_file('block', 'autotag.thtml');

        switch ($option) {
        case 'random':
            $quote = DailyQuote::getQuote();
            break;

        case 'quote':
            $qid = COM_sanitizeID($autotag['parm2']);
            if ($qid != '') {
                $quote = DailyQuote::getQuote($qid);
            }
            break;

        } // switch ($op)
        
        if (is_array($quote) && !empty($quote)) {
            $T->set_var(array(
                'quote'     => $quote['quote'],
                'quoted'    => $quote['quoted'],
                'source'    => $quote['source'],
                'sourcedate' => $quote['sourcedate'],
                'qid'       => $quote['id'],
            ) );
            $T->parse('output','block');
            $HTML = $T->finish($T->get_var('output'));
            $retval = str_replace($autotag['tagstr'], $HTML, $content);
        } else {
            $retval = $content;
        }
        break;

    default:
        $retval = $content;
        break;

    }   // switch ($op)

    return $retval;

}


/**
*   Enable or disable this plugin's blocks when the plugin is changed.
*
*   @param  boolean $enabled    Plugin's "enabled" state
*/
function plugin_enablestatechange_dailyquote($enabled)
{
    global $_TABLES;

    $enabled = $enabled == true ? 1 : 0;
    if ($enabled == 1) return;  // Never automatically enable

    $blocks = array('dailyquote_random', 'dailyquote_dgmenu');
    foreach ($blocks as $block) {
        DB_query("UPDATE {$_TABLES['blocks']}
                SET is_enabled=$enabled
                WHERE name='$block'");
    }
}


/**
*   Get the URL to the configuration help file
*
*   @param  string  $doclang    Document language
*   @return string              URL to document
*/
function plugin_getconfigelementhelp_dailyquote($option, $doclang='english')
{
    global $_CONF, $_CONF_DQ;

    $doc_path = $_CONF['path_html'] . '/' . $_CONF_DQ['pi_name'] . '/docs/';
    if (file_exists("{$doc_path}config_{$doclang}.thtml")) {
        $file  = "config_{$doclang}.thtml";
    } else {
        $file = 'config_english.html';
    }

    if (!empty($option)) {
        $file .= '#desc_' . $option;
    }

    return array(DQ_URL . '/docs/' . $file, 0);
}


/**
*   Upgrade the plugin.
*
*   @return boolean True on success, code for error message otherwise
*/
function plugin_upgrade_dailyquote()
{
    global $_CONF_DQ, $_TABLES;
    
    // Get the current version, compare to the version in the plugin code
    $current_ver = DB_getItem($_TABLES['plugins'], 'pi_version',
        "pi_name = '{$_CONF_DQ['pi_name']}'");
    if (empty($current_ver)) {
        COM_errorLog("Error getting the {$_CONF_DQ['pi_name']} plugin version",1);
        return '03';
    }

    if ($current_ver >= $_CONF_DQ['pi_version']) {
        return '06';
    }

    // Perform the upgrade
    require_once 'upgrade.inc.php';
    $error = DQ_do_upgrade($current_ver);
    if ($error) {
        COM_errorLog("Error updating the {$_CONF_DQ['pi_name']} plugin",1);
        return '04';
    }

    // now update the current version number.
    $sql = "UPDATE
        {$_TABLES['plugins']}
    SET
        pi_version = '{$_CONF_DQ['pi_version']}',
        pi_gl_version = '{$_CONF_DQ['gl_version']}',
        pi_homepage = '{$_CONF_DQ['pi_url']}'
    WHERE
        pi_name = '{$_CONF_DQ['pi_name']}';";
    DB_query($sql);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_CONF_DQ['pi_name']} plugin version",1);
        return '05';
    }

    COM_errorLog("Succesfully updated the {$_CONF_DQ['pi_name']} plugin!",1);
    return true;
}


?>
