<?php
/**
 * Common functions for the DailyQuote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2017 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
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
require_once __DIR__ . '/dailyquote.php';
if (file_exists(__DIR__ . '/config.php')) {
    /** Include local configuration overrides */
    include_once __DIR__ . '/config.php';
}

// Mark whether this is a uikit theme. Save to global conf var
// so it's available where needed.
$_CONF_DQ['_is_uikit'] = $_SYSTEM['framework'] == 'uikit' ? true : false;

define('DQ_PI_PATH', __DIR__);
define('DQ_ADMIN_URL',
    $_CONF['site_url'].'/admin/plugins/'.$_CONF_DQ['pi_name']);
define('DQ_URL',
    $_CONF['site_url'].'/'.$_CONF_DQ['pi_name']);


/** Language file Include */
$langpath = DQ_PI_PATH . '/language';
$langfile = "{$_CONF['language']}.php";
if (!file_exists("$langpath/$langfile")) {
    $langfile = 'english.php';
}
require_once "$langpath/$langfile";

/**
 * Import the batch upload function.
 */
function USES_dailyquote_batch()
{   require_once DQ_PI_PATH . '/batch.php'; }

spl_autoload_register(function ($class)
{
    if (strpos($class, 'DailyQuote\\') === 0) {
        $class = str_replace('DailyQuote\\', '', $class);
        $path = __DIR__ . '/classes/' . $class . '.class.php';
        if (file_exists($path)) {
            include $path;
        }
    }
});


/**
 * Returns the items for this plugin that should appear on the main menu.
 *
 * @return  array   Associative array of menu items to be added to menu
 */
function plugin_getmenuitems_dailyquote()
{
    global $_CONF, $_CONF_DQ;

    $menuitems = array(
        $_CONF_DQ['pi_display_name'] => DQ_URL . '/index.php',
    );
    return $menuitems;
}


/**
 * Get additional Javascript into the site header.
 *
 * @return  array   List of paths to Javascript files
 */
function plugin_getheaderjs_dailyquote()
{
    return array(
        __DIR__ . '/js/toggleEnabled.js',
    );
}


/**
 * Shows the statistics for the plugin on stats.php.
 * If $showsitestats is 1 then we are to only print the overall stats in
 * the 'site statistics' box otherwise we show the detailed stats for the
 * plugin.
 *
 * @param   integer $showsitestats   Flag to let us know which stats to get
 * @return  string  formatted HTML to insert in stats page
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

    $result = DB_query("SELECT q.quoted, COUNT(*)
            FROM {$_TABLES['dailyquote_quotes']} q
            LEFT JOIN {$_TABLES['dailyquote_quoteXcat']} x
                ON q.id = x.qid
            LEFT JOIN {$_TABLES['dailyquote_cat']} c
                ON x.cid = c.id
            WHERE q.enabled= '1' AND c.enabled = '1'
            GROUP BY quoted
            ORDER BY 2 DESC, 1 ASC
            LIMIT 10");
    $nrows  = DB_numRows($result);
    $retval .= COM_startBlock($LANG_DQ['StatsMsg1']);
    if ($nrows > 0) {
        $T->set_var('item_label', $LANG_DQ['StatsMsg3']);
        $T->set_var('stat_name', $LANG_DQ['StatsMsg4']);

        while ($A = DB_fetchArray($result)) {
            $T->set_var('item_url',
                        "{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/index.php?quoted=" . $A['quoted']);
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


/**
 * Show the statistics summary.
 *
 * @return  array   Array of (label=>data)
 */
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
 * This will put an option for the plugin in the command and control block.
 *
 * @return  array   Array containing (pi_name, pi_admin_url, pi_icon_url)
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
 * Returns the administrative menu option for this plugin.
 *
 * @return  mixed   Array (pi_name, pi_admin_url, # of items) or ''
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
 * Provide any new items that show up in the type drop-down on search.php.
 *
 * @return  array   Single name=>display_name value for the selection
 */
function plugin_searchtypes_dailyquote()
{
    global $LANG_DQ;

    $tmp['dailyquote'] = $LANG_DQ['glsearchlabel'];
    return $tmp;
}


/**
 * Get the search configuration for the plugin.
 * Search for pages matching the user query and returns an array of
 * for the header and table rows back to search.php where it will be
 * formated and printed
 *
 * @param   string  $query      Keywords user is looking for
 * @param   date    $datestart  Start date to get results for
 * @param   date    $dateend    End date to get results for
 * @param   string  $topic      The topic they were searching in
 * @param   string  $type       Type of items they are searching, or 'all'
 * @param   integer $author     Get all results by this author
 * @param   string  $keyType    search key type: 'all', 'phrase', 'any'
 * @param   integer $page       page number of current search
 * @param   integer $perpage    number of results per page
 * @return  object              search result object
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

    $htmlquery = urlencode($query);
    $query = trim(DB_escapeString(htmlspecialchars($query)));

    // Build search SQL
    $sql = "SELECT
            q.id as id, q.quote as description,
            q.title as title, q.uid as uid,
            q.dt as date, 0 as hits,
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
 * Whats New Block API Support.
 *
 * @return  mixed   Array of header data for block, or fals if block disabled.
 */
function plugin_whatsnewsupported_dailyquote()
{
    global $_CONF, $LANG_DQ, $_CONF_DQ;

    if ($_CONF_DQ['whatsnew'] == '0') {
        return false;
    } else {
        return array(
            $LANG_DQ['whatsnewlabel'],
            sprintf($LANG_DQ['whatsnewperiod'], $_CONF_DQ['whatsnewdays']));
    }
}


/**
 * API function provides the content of our "What's New" feed.
 *
 * @return  array   Array of links
 */
function plugin_getwhatsnew_dailyquote()
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;

    $days = (int)$_CONF_DQ['whatsnewdays'];

    $sql = "SELECT DISTINCT q.id, q.quote
            FROM {$_TABLES['dailyquote_quotes']} q
            LEFT JOIN {$_TABLES['dailyquote_quoteXcat']} x
                ON q.id = x.qid
            LEFT JOIN {$_TABLES['dailyquote_cat']} c
                ON c.id = x.cid
            WHERE q.enabled='1'
            AND c.enabled='1'
            AND q.dt >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$days} DAY))
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
            $T->parse('output', 'block');
            $items .= $T->finish($T->get_var('output'));
        }
        return $items;
    } else {
        return $LANG_DQ['newempty'] . '<br />' . LB;
    }
}


/**
 * Return the current version of code.
 *
 * @return  string  Current plugin version
 */
function plugin_chkVersion_dailyquote()
{
    global $_CONF_DQ;
    return $_CONF_DQ['pi_version'];
}


/**
 * Automatically uninstall the plugin.
 *
 * @return  array   Array of plugin elements to remove
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
                            $_CONF_DQ['pi_name'] . '.submit',
                            $_CONF_DQ['pi_name'] . '.admin',
                        ),
        'php_blocks' => array('phpblock_' . $_CONF_DQ['pi_name'] . '_dqmenu',
                            'phpblock_' . $_CONF_DQ['pi_name'] . '_random',
                        ),
        'vars'      => array($_CONF_DQ['pi_name'] . '_gid')
    );
    return $out;
}


/**
 * Return the template names that support ad blocks.
 *
 * @return  array   Array of template names
 */
function plugin_supportadblock_dailyquote()
{
    return array('dailyquote_list');
}


/**
 * Converts usernames to clickable links leading to user profiles.
 *
 * @param   integer $uid        User ID
 * @param   string  $username   User Name
 * @return  string              HTML for link to user profile
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
 * Converts categories to clickable links leading to the search page.
 *
 * @param   integer $catid      Category ID
 * @param   string  $catname    Category Name
 * @return  string              HTML for link
 */
function DQ_catlink($catid, $catname)
{
    global $_CONF, $_TABLES, $_CONF_DQ;

    $retval = "<a href=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}/index.php?cat=$catid\">$catname</a>";
    return $retval;
}


/**
 * Create the centerblock, if needed.
 *
 * @param   integer $where  Which area is being displayed now
 * @param   integer $page   Page number
 * @param   string  $topic  Topic ID, or empty string
 * @return  string          HTML for centerblock
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

    // Check access to view quotes
    if (!DailyQuote\Quote::hasAccess(2)) {
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

    $Q = DailyQuote\Quote::getQuote();
    if (!$Q) {
        return '';
    }

    if ($where == 0) {
        // If replacing the homepage, we're responsible for the header
        $retval = COM_siteHeader();
    }

    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'centerblock.thtml');
    $T->set_var(array(
        'dispquote'     => $Q->quote,
        'dispquoted'    => $Q->quoted,
        'source'        => $Q->source,
        'title'         => $Q->title,
    ) );
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));
    if ($where == 0) {
        $retval .= COM_siteFooter();
    }
    return $retval;
}


/**
 * Returns blockmenu content for dqblockmenu.
 *
 * @return  string      HTML for block menu
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
    $href = COM_createLink($LANG_DQ['searchlink'], DQ_URL . '/index.php?quoted=');
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
 * Displays a random quote from your database in a block.
 *
 * @return  string  HTML for the block contents.
 */
function phpblock_dailyquote_random()
{
    global $_CONF, $_CONF_DQ;

    $retval = '';
    $Q = DailyQuote\Quote::getQuote();
    if ($Q) {
        $T = new Template(DQ_PI_PATH . '/templates');
        $T->set_file('page', 'blk_random.thtml');
        $T->set_var(array(
            'randomquote'   => $Q->quote,
            'quoted'        => $Q->quoted,
            'sourcedate'    => $Q->sourcedate,
            'source'        => $Q->source,
        ) );
        $T->parse('output','page');
        $retval = $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
 * Returns list of moderation values.
 *
 * The array returned contains (in order): the row 'id' label, main plugin
 * table, moderation fields (comma seperated), and plugin submission table
 *
 * @return  array    Returns array of useful moderation values
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
 * Show items for moderation on submissions page.
 *
 * Uses the Plugin class to return data required by moderation.php to list
 * plugin objects that need to be moderated.
 *
 * @return  plugin  Plugin class containing submission data
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
 * Returns the current user's ability to moderate the plugin.
 *
 * Checks that the current user has the rights to moderate the
 * plugin, returns true if this is the case, false otherwise
 *
 * @return  boolean     Returns true if moderator
 */
function plugin_ismoderator_dailyquote()
{
    return SEC_hasRights('dailyquote.admin');
}


/**
 * Performs plugin exclusive work for items approved by moderation.
 * This just makes sure the 'enabled' flag is set to true.
 *
 * @param   string  $id     ID of the item
 * @return  string          Empty string to indicate success
 */
function plugin_moderationapprove_dailyquote($id)
{
    global $_TABLES;

    DB_query("UPDATE {$_TABLES['dailyquote_quotes']}
        SET enabled = 1
        WHERE id='" . DB_escapeString($id) . "'");
    return '';
}


/**
 * Performs plugin exclusive work for items deleted by moderation.
 *
 * While moderation.php handles the actual removal from the submission
 * table, within this function we handle all other deletion-related tasks.
 *
 * @param   string  $id     Identifying string
 * @return  string          Any wanted HTML output
 */
function plugin_moderationdelete_dailyquote($id)
{
    global $_TABLES;

    // Delete the category lookup entries for the rejected quotation.
    DB_delete ($_TABLES['dailyquote_quoteXcat'], 'qid', $id);
    return '';
}


/**
 * Returns the plugins submission form.
 * Returns a string contain the HTML of the submission form for the plugin.
 *
 * @return  string  The HTML form
 */
function plugin_submit_dailyquote()
{
    global  $LANG12, $_CONF, $_CONF_DQ, $_USER, $LANG_DQ;

    // if a previous submission being re-edited due to an error,
    // use the previous field values
    $A = isset($_POST['savesubmission']) ? $_POST : array();

    if (SEC_hasRights('dailyquote.submit') || $_CONF_DQ['queue'] == 0) {
        // user has submit right or submission queue is not being used
        $Q = new DailyQuote\Quote();
        $retval = $Q->Edit('', $A);   // Directly edit prod table
    } elseif ( (COM_isAnonUser() && $_CONF_DQ['anonadd'] == 1) ||
            ((int)$_USER['uid'] > 1 && $_CONF_DQ['loginadd'] == 1) ) {
        // user has regular access and queue is being used
        $Q = new DailyQuote\Quote('', 'submission');  // Send to the submission table
        $retval = $Q->Edit('submit', $A);
    } else {
        COM_errorLog("Someone has tried to illegally access the dailyquote submission page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        COM_404();
        exit;
    }
    return $retval;
}


/**
 * Saves a user submission.
 * If user is authorized, or the submission queue is not used, save directly to
 * the production table. Otherwise, save to the submission table.
 *
 * @param   array   $A  Array of items to save, from $_POST
 * @return  string      Text or HTTP redirect to display
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
        //$table = 'dailyquote_quotes';
        $msg = '02';    // "has been saved" message
        $email_admin = 0;
    } elseif ($_CONF_DQ['queue'] == 0) {
        // Same tables as above, but set $email_admin
        //$table = 'dailyquote_quotes';
        $msg = '02';    // "has been saved" message
        $email_admin = $_CONF_DQ['email_admin'] == 2 ? 1 : 0;
    } else {
        //$table = 'dailyquote_submission';
        $msg = '01';    // "added to queue" message
        $email_admin = $_CONF_DQ['email_admin'] > 0 ? 1 : 0;
    }

    if ($errmsg == '') {        // Only save if no errors so far
        if ($A['mode'] == $LANG12[8] ||
            $A['mode'] == $LANG_ADMIN['save']) {     // mode = Submit
            // This is a user-submitted ad
            //$A['id'] = COM_makeSid();
            $A['uid'] = $owner_id;
            $A['dt_add'] = time();

            $Q = new DailyQuote\Quote();
            if ($Q->SaveSubmission($A) != '') {
                return false;       // Catastrophic failure
            }
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
        // An error occurred, return to the submission form
        $display = COM_siteHeader();
        $display .= "<span class=alert><ul>$errmsg</ul></span\n";
        $Q->id = '';
        $display .= $Q->Edit('submit', $_POST);
        $display .= COM_siteFooter();
        return $display;
    }
}


/**
 * Returns the URL of the plugin's icon.
 *
 * @return  string  URL of the icon
 */
function plugin_geticon_dailyquote()
{
    global $_CONF;
    return $_CONF['site_url'] . '/dailyquote/images/dailyquote.png';
}


/**
 * Implements the [dailyquote:] autotag.
 *
 * @param   string  $op         Requested operation
 * @param   string  $content    Page content
 * @param   string  $autotag    Autotag content
 * @return  string              $content with the autotag substitutions
 */
function plugin_autotags_dailyquote($op, $content = '', $autotag = '')
{
    global $_CONF, $_TABLES, $_CONF_DQ;

    $quote = '';
    switch ($op) {
    case 'tagname':
        $retval = 'dailyquote';
        break;
    case 'parse':
        $HTML = '';
        $tag = htmlentities($autotag['tagstr'], NULL, 'utf-8');
        $tag = str_replace('&nbsp;', ' ', $tag);
        $parms = explode(' ', $tag);
        // Extra test to see if autotag was entered with a space
        // after the module name
        if (substr($parms[0], -1) == ':') {
            $startpos = strlen($parms[0]) + strlen($parms[1]) + 2;
            $label = str_replace(']', '', substr($tag, $startpos));
            $tagid = $parms[1];
        } else {
            $label = str_replace(']', '', substr($tag, strlen($parms[0]) + 1));
            $parms = explode(':', $parms[0]);
            if (count($parms) > 2) {
                // whoops, there was a ':' in the tag id ...
                array_shift($parms);
                $tagid = implode(':', $parms);
            } else {
                $tagid = $parms[1];
            }
        }
        $autotag['parm1'] = str_replace(']','',$tagid);
        $autotag['parm2'] = $label;

        $option = COM_applyFilter($autotag['parm1']);

        $cat = 0;
        $incl_src = '';
        $qid = '';
        $template = 'default';
        if (!empty($autotag['parm2'])) {
            $parm2 = explode(' ', $autotag['parm2']);
            foreach ($parm2 as $parm) {
                $kv = explode(':', $parm);
                switch ($kv[0]) {
                case 'cat':
                    if (isset($kv[1]) && !empty($kv[1])) {
                        $cat = (int)$kv[1];
                    }
                    break;
                case 'source':
                    $incl_src = true;
                    break;
                case 'template':
                    if (isset($kv[1]) && !empty($kv[1])) {
                        $template = $kv[1];
                    }
                    break;
                default:
                    $qid = $kv[0];
                    break;
                }
            }
        }
        $nocache = false;
        switch ($option) {
        case 'random':
            $quote = DailyQuote\Quote::getQuote('', $cat);
            // Don't cache random quote in template
            $nocache = true;
            break;

        case 'quote':
            $qid = COM_sanitizeID($qid);
            if ($qid != '') {
                $quote = DailyQuote\Quote::getQuote($qid);
            }
            break;

        } // switch ($op)

        if ($quote) {
            if (!is_file(DQ_PI_PATH . '/templates/autotags/'.$template.'.thtml')) {
                // Revert back to default template if an invalid one is provided.
                $template = 'default';
            }
            $T = new Template(DQ_PI_PATH . '/templates/autotags');
            $T->set_file('block', $template . '.thtml');
            $T->set_var(array(
                'quote'     => $quote->quote,
                'quoted'    => $quote->quoted,
                'source'    => $incl_src ? $quote->source : '',
                'sourcedate' => $quote->sourcedate,
                'qid'       => $quote->id,
            ), false, $nocache );
            $T->parse('output','block');
            $HTML = $T->finish($T->get_var('output'));
        } else {
            $HTML = '';
        }
        $retval = str_replace($autotag['tagstr'], $HTML, $content);
        break;

    default:
        $retval = $content;
        break;

    }   // switch ($op)

    return $retval;
}


/**
 * Disable this plugin's blocks when the plugin is changed.
 * Does not automatically enable blocks since there's no way to know
 * which blocks should be enabled.
 *
 * @param   boolean $enabled    Plugin's "enabled" state
 */
function plugin_enablestatechange_dailyquote($enabled)
{
    global $_TABLES, $_PLUGINS;

    $enabled = $enabled == true ? 1 : 0;

    // If trying to enable the plugin but lgLib is not available,
    // take no action and return
    if ($enabled && !in_array('lglib', $_PLUGINS)) {
        // Re-disable the plugin
        DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', 'dailyquote');
        $enabled = 0;
    }

    if ($enabled == 0) {
        $blocks = array('dailyquote_random', 'dailyquote_dgmenu');
        foreach ($blocks as $block) {
            DB_query("UPDATE {$_TABLES['blocks']}
                SET is_enabled=$enabled
                WHERE name='$block'");
        }
    }
}


/**
 * Receives notification of another plugin's deletion.
 * Disables this plugin if the lgLib plugin is disabled or removed.
 *
 * @param   mixed   $id     Item ID
 * @param   string  $type   Plugin Name
 */
function plugin_itemdeleted_dailyquote($id, $type)
{
    global $_TABLES;

    if ($type == 'lglib' && $id == '*') {
        // Disable blocks, etc.
        plugin_enablestatechange_dailyquote(false);
        // Disable this plugin
        DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', 'dailyquote');
    }
}


/**
 * Get the URL to the configuration help file.
 *
 * @uses    DQ_getDocURL()
 * @param   string  $option     Documentation element to show
 * @param   string  $doclang    Document language
 * @return  string              URL to document
 */
function plugin_getconfigelementhelp_dailyquote($option, $doclang='english')
{
    global $_CONF, $_CONF_DQ;

    $doc_url = DQ_getDocURL('config', $doclang);
    if (empty($doc_url)) {
        return false;
    }

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }
    return array($doc_url, 0);
}


/**
 * Upgrade the plugin.
 *
 * @return  boolean     True on success, False on failure
 */
function plugin_upgrade_dailyquote()
{
    require_once __DIR__ . '/upgrade.inc.php';
    return DQ_do_upgrade();
}


/**
 * Support merging local accounts into remote accounts.
 *
 * @param   integer $origUID    Original (local) user ID
 * @param   integer $destUID    New (remote) user ID
 */
function plugin_user_move_dailyquote($origUID, $destUID)
{
    global $_TABLES;

    $origUID = (int)$origUID;
    $destUID = (int)$destUID;

    DB_query("UPDATE {$_TABLES['dailyquote_submissions']}
            SET uid = $destUID WHERE uid = $origUID", 1);
    DB_query("UPDATE {$_TABLES['dailyquote_quotes']}
            SET uid = $destUID WHERE uid = $origUID", 1);
}


/**
 * Get additional CSS into the site header.
 * Allows for custom CSS to be placed by the site admin.
 *
 * @return  array   List of paths to CSS files
 */
function plugin_getheadercss_dailyquote()
{
    global $_CONF, $_CONF_DQ;

    // When looking for CSS files, we'll check in this order:
    //  1. layout/plugin/custom
    //  2. layout/plugin
    //  3. plugin/custom
    //  4. plugin (default distribution version)
    $pi_css = DQ_PI_PATH . '/css/';
    $layout_css = "{$_CONF['path_layout']}plugins/{$_CONF_DQ['pi_name']}/";
    $files = array('style.css');
    foreach ($files as $file) {
        if (@file_exists($layout_css . 'custom/' . $file)) {
            $styles[] = $layout_css . 'custom/' . $file;
        } elseif (@file_exists($layout_css . $file)) {
            $styles[] = $layout_css . $file;
        } elseif (@file_exists($pi_css . 'custom/' . $file)) {
            $styles[] = $pi_css . 'custom/' . $file;
        } elseif (@file_exists($pi_css . $file)) {
            $styles[] = $pi_css . $file;
        }
    }
    return $styles;
}


/**
 * Provides the getItemInfo function for daily quotes.
 *
 * @param   string  $id         Product ID
 * @param   string  $what       Comma-separated list of fields to return
 * @param   integer $uid        User ID
 * @param   array   $options    Not used
 * @return  array           Array of product information
 */
function plugin_getiteminfo_dailyquote($id, $what, $uid=0, $options=array())
{
    global $_TABLES, $_CONF, $LANG_DQ;

    $properties = explode(',', $what);
    $fields = array();
    $retval = array();
    $buildingSearchIndex = false;
    foreach ($properties as $p) {
        switch ($p) {
        case 'search_index':
            $buildingSearchIndex = true;
            break;
        case 'id':
            $fields[] = 'id';
            break;
        case 'title':
            $fields[] = 'title';
            break;
        case 'content':
        case 'description':
            $fields[] = 'quote';
            break;
        case 'searchidx':
            $fields[] = 'title';
            $fields[] = 'quote';
            break;
        case 'author':
        case 'author_name':
            $fields[] = 'uid';
            break;
        case 'date':
            $fields[] = 'dt';
            break;
        }
    }
    if (count($fields) == 0) {
        return $retval;
    }
    $fields = array_unique($fields);
    $fields = implode(',', $fields);
    $retval = array();
    $sql = "SELECT $fields
            FROM {$_TABLES['dailyquote_quotes']} ";
    if ($id == '*') {
        $where = ' WHERE enabled = 1 ';
    } else {
        $where = " WHERE (id = '" . DB_escapeString($id) . "')";
    }
    //echo $sql;die;
    $res = DB_query($sql . $where);
    while ($A = DB_fetchArray($res, false)) {
        foreach ($properties as $p) {
            switch ($p) {
            case 'id':
                $props[$p] = $A['id'];
                break;
            case 'excerpt':
            case 'description':
            case 'content':
                $props[$p] = $A['quote'];
                break;
            case 'title':
                $props[$p] = empty($A['title']) ? $LANG_DQ['untitled'] : $A['title'];
                break;
            case 'author':
                $props[$p] = $A['uid'];
                break;
            case 'author_name':
                $props[$p] = COM_getDisplayName($A['uid']);
                break;
            case 'date':
                $props[$p] = $A['dt'];
                break;
            case 'searchidx':
                $props[$p] = $A['title'] . ' ' . $A['quote'];
                break;
            case 'url':
                $props[$p] = DQ_URL . '/index.php?id=' . urlencode($A['id']);
                break;
            default:
                $props[$p] = isset($A[$p]) ? $A[$p] : NULL;
                break;
            }
        }
        $mapped = array();
        foreach ($props as $key => $value) {
            if ($id == '*') {
                if ($value != '') {
                    $mapped[$key] = $value;
                }
            } else {
                $mapped[$key] = $value;
            }
        }

        if ($id == '*') {
            $retval[] = $mapped;
        } else {
            $retval = $mapped;
            break;
        }
    }

    if (($id != '*') && (count($retval) == 1)) {
        $tRet = array_values($retval);
        $retval = $tRet[0];
    }
    if ($retval === '' || (is_array($retval) && count($retval) == 0)) {
        return NULL;
    } else {
        return $retval;
    }
}


/**
 * Displays a quote box at the top of the regular listings.
 * If no quote id is specified, a random one is selected.  If a
 * category ID is given, then the random quote is selected from among
 * that category.
 *
 * @param   string  $qid    Quote ID to retrieve
 * @param   integer $cid    Category ID for random quotes
 * @return  string          HTML display for the quote
 */
function DQ_random_quote($qid='', $cid='')
{
    global $_CONF, $LANG_DQ, $_CONF_DQ;

    $Q = DailyQuote\Quote::getQuote($qid, $cid);
    if (!$Q) {
        return '';
    }

    $T = new Template($_CONF['path'] . 'plugins/' . $_CONF_DQ['pi_name'] . '/templates');
    $T->set_file('page', 'randomquotebox.thtml');
    $T->set_var(array(
        'site_url'  => $_CONF['site_url'],
        'randomboxtitle' => $LANG_DQ['randomboxtitle'],
        'title'     => $Q->title,
        'randomquote' => $Q->quote,
        'quoted'    => $Q->quoted,
        'source'    => $Q->source,
        'sourcedate' => $Q->sourcedate,
    ) );
    if ($Q->uid > 1) {
        $T->set_var('subm_by', $LANG_DQ['subm_by'] . ':&nbsp;');
        $T->set_var('dispcontr', COM_getDisplayName($Q->uid));
        $T->set_var('datecontr', '&nbsp;/&nbsp;' . $Q->date);
    }
    $T->parse('output','page');
    return $T->finish($T->get_var('output'));
}


/**
 * Display the site header, with or without blocks according to configuration.
 *
 * @param   string  $title  Title to put in header
 * @param   string  $meta   Optional header code
 * @return  string          HTML for site header, from COM_siteHeader()
 */
function DQ_siteHeader($title='', $meta='')
{
    global $_CONF_DQ;

    $retval = '';

    switch($_CONF_DQ['displayblocks']) {
    case 2:     // right only
    case 0:     // none
        $retval .= COM_siteHeader('none', $title, $meta);
        break;

    case 1:     // left only
    case 3:     // both
    default :
        $retval .= COM_siteHeader('menu', $title, $meta);
        break;
    }
    return $retval;
}


/**
 * Display the site footer, with or without blocks as configured.
 *
 * @return  string  HTML for site footer, from COM_siteFooter()
 */
function DQ_siteFooter()
{
    global $_CONF_DQ;

    $retval = '';

    switch($_CONF_DQ['displayblocks']) {
    case 2 : // right only
    case 3 : // left and right
        $retval .= COM_siteFooter(true);
        break;

    case 0: // none
    case 1: // left only
        $retval .= COM_siteFooter();
        break;
    }
    return $retval;
}


/**
 * Get the link to a documentation file.
 * Returns the URL to the file, or to the directory if no file requested.
 *
 * @see     plugin_getconfigelementhelp_dailyquote()
 * @param   string  $file       HTML file
 * @param   string  $doclang    Documentation language
 * @return  string              URL to document directory or file
 */
function DQ_getDocURL($file='', $doclang='english')
{
    global $_CONF, $_CONF_DQ;

    $doc_path = $_CONF['path_html'] . $_CONF_DQ['pi_name'] .
                "/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        if (!$_CONF_DQ['_is_uikit']) $file .= '.legacy';
        $file .= '.html';
        if (!file_exists($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = DQ_URL . "/docs/$doclang/$file";
         if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = DQ_URL . "/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = DQ_URL . "/docs/$doclang/";
    }
    return $doc_url;
}

?>
