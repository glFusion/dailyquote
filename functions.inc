<?php
/**
 * Common functions for the DailyQuote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

spl_autoload_register(function ($class)
{
    $prefix = 'DailyQuote';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/classes/';

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
use glFusion\Database\Database;
use glFusion\Log\Log;
use DailyQuote\Models\DataArray;
use DailyQuote\Config;

/** Additional config items and table definitions are added here */
require_once __DIR__ . '/dailyquote.php';
if (file_exists(__DIR__ . '/config.php')) {
    /** Include local configuration overrides */
    include_once __DIR__ . '/config.php';
}

// Load language file
$langpath = __DIR__ . '/language';
$langfile = "{$_CONF['language']}.php";
if (!is_file("$langpath/$langfile")) {
    $langfile = 'english_utf-8.php';
}
require_once "$langpath/$langfile";

if (is_file("$langpath/custom/$langfile")) {
    /** Include the custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

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
    global $_CONF;

    $menuitems = array(
        Config::get('pi_display_name') => Config::get('url') . '/index.php',
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
        __DIR__ . '/js/util.js',
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
    global $_CONF, $_USER, $_TABLES, $LANG_DQ;

    USES_lib_admin();

    $retval = '';

    $T = new Template($_CONF['path_layout'] . 'stats');
    $T->set_file(array(
        'itemstats' => 'itemstatistics.thtml',
        'statrow' => 'singlestat.thtml'
    ) );

    $db = Database::getInstance();
    try {
        $stmt = $db->conn->executeQuery(
            "SELECT q.quoted, COUNT(*)
            FROM {$_TABLES['dailyquote_quotes']} q
            LEFT JOIN {$_TABLES['dailyquote_quoteXcat']} x
                ON q.id = x.qid
            LEFT JOIN {$_TABLES['dailyquote_cat']} c
                ON x.cid = c.id
            WHERE q.enabled= 1 AND c.enabled = 1 AND q.approved = 1
            GROUP BY quoted
            ORDER BY 2 DESC, 1 ASC
            LIMIT 10"
        );
        $nrows  = $stmt->rowCount();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $stmt = false;
        $nrows = false;
    }
    $retval .= COM_startBlock('Top Ten Most Quoted Personalities');

    if ($nrows > 0) {
        $T->set_var('item_label', $LANG_DQ['StatsMsg3']);
        $T->set_var('stat_name', $LANG_DQ['StatsMsg4']);

        while ($A = $stmt->fetchAssociative()) {
            $T->set_var('item_url',
                        "{$_CONF['site_url']}/{Config::PI_NAME}/index.php?quoted=" . $A['quoted']);
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
    $total_items = Database::getInstance()->getCount($_TABLES['dailyquote_quotes']);
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
    global $_CONF;

    if (SEC_hasRights('dailyquote.admin')) {
        return array(
            Config::get('pi_display_name'),
            Config::get('url') . '/index.php',
            plugin_geticon_dailyquote()
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
    global $_CONF, $_TABLES, $LANG_DQ;

    if (SEC_hasRights('dailyquote.admin')) {
        $count = Database::getInstance()->getCount(
            $_TABLES['dailyquote_quotes'],
            array('enabled', 'approved'),
            array(1, 1),
            array(Database::INTEGER, Database::INTEGER)
        );
        return array(
            $LANG_DQ['admin_menu'],
            Config::get('admin_url') . '/index.php',
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
 * Whats New Block API Support.
 *
 * @return  mixed   Array of header data for block, or fals if block disabled.
 */
function plugin_whatsnewsupported_dailyquote()
{
    global $_CONF, $LANG_DQ;

    if (!Config::get('whatsnew')) {
        return false;
    } else {
        return array(
            $LANG_DQ['whatsnewlabel'],
            sprintf($LANG_DQ['whatsnewperiod'], Config::get('whatsnewdays'))
        );
    }
}


/**
 * API function provides the content of our "What's New" feed.
 *
 * @return  array   Array of links
 */
function plugin_getwhatsnew_dailyquote()
{
    global $_TABLES, $_CONF, $LANG_DQ;

    $days = (int)Config::get('whatsnewdays');

    $db = Database::getInstance();
    $qb = $db->conn->createQueryBuilder();
    $qb->select('q.id', 'q.quote')
       ->from($_TABLES['dailyquote_quotes'], 'q')
       ->leftJoin('q', $_TABLES['dailyquote_quoteXcat'], 'x', 'q.id = x.qid')
       ->leftJoin('x', $_TABLES['dailyquote_cat'], 'c', 'c.id = x.cid')
       ->where('q.enabled = 1')
       ->andWhere('c.enabled = 1')
       ->andWhere('q.dt >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :days DAY))')
       ->orderBy('q.dt', 'DESC')
       ->setFirstResult(0)
       ->setMaxResults(15)
       ->setParameter('days', $days, Database::INTEGER);
    try {
        $stmt = $qb->execute();
        $nrows = $stmt->rowCount();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $stmt = false;
        $nrows = false;
    }
    if ($stmt && $nrows > 0) {
        $T = new Template(Config::get('path') . '/templates');
        $T->set_file('block', 'blk_whatsnew.thtml');
        while ($row = $stmt->fetchAssociative()) {
            $quote = htmlspecialchars($row['quote']);
            $itemlen = strlen($quote);
            if ($itemlen > 16) {
                $quote_text = substr($quote, 0, 16) . '...';
            } else {
                $quote_text = $row['quote'];
            }
            $T->set_var('quote_link', Config::get('url') . '/index.php?qid=' . $row['qid']);
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
    return Config::get('pi_version');
}


/**
 * Automatically uninstall the plugin.
 *
 * @return  array   Array of plugin elements to remove
 */
function plugin_autouninstall_dailyquote()
{
    $out = array (
        'tables'    => array(
            Config::PI_NAME. '_quotes',
            Config::PI_NAME. '_submission',
            Config::PI_NAME. '_cat',
            Config::PI_NAME. '_quoteXcat',
        ),
        'groups'    => array(
            Config::PI_NAME . ' Admin',
        ),
        'features'  => array(
            Config::PI_NAME . '.add',
            Config::PI_NAME . '.edit',
            Config::PI_NAME . '.view',
            Config::PI_NAME . '.submit',
            Config::PI_NAME . '.admin',
        ),
        'php_blocks' => array(
            'phpblock_' . Config::PI_NAME . '_dqmenu',
            'phpblock_' . Config::PI_NAME . '_random',
        ),
        'vars'      => array(
            Config::PI_NAME . '_gid',
        )
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
    global $_CONF, $_TABLES;

    $retval = "<a href=\"{$_CONF['site_url']}/{Config::PI_NAME}/index.php?cat=$catid\">$catname</a>";
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
    global $_CONF, $_USER, $_TABLES, $PHP_SELF, $LANG_DQ;

    $retval = '';
    $img_dir = $_CONF['layout_url'] . '/' . Config::PI_NAME . '/image_set';

    // If centerblock not enabled, or just for homepage and
    // we're not on the homepage, just return
    if (
        Config::get('cb_pos') ==  0 ||
        (Config::get('cb_home') == 1 && ($page > 1 || $topic != ''))
    ) {
        return '';
    }

    // Check access to view quotes
    if (!DailyQuote\Quote::hasAccess(2)) {
        return '';
    }

    // Get the centerblock position.  May be overridden later.
    $cntrblkpos = Config::get('cb_pos');

    // If we're not supposed to replace the homepage, then return.
    // Otherwise, do so.  When $where is zero we're at the homepage.
    if ($where == 0 && $topic == '') {
        if (!Config::get('cb_replhome')) {
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
        $wheretypes = array(Database::STRING, Database::INTEGER, Database::INTEGER);
    } else {
        $wherenames = array('featured', 'draft_flag');
        $wherevalues = array(1, 0);
        $wheretypes = array(Database::INTEGER, Database::INTEGER);
    }

    $story_count = Database::getInstance()->getCount(
        $_TABLES['stories'],
        $wherenames,
        $wherevalues,
        $wheretypes
    );
    if ($story_count == 0 && Config::get('cb_pos') == 2) {
        // If the centerblock comes after the featured story, and there
        // are no stories, put the centerblock at the top.
        $cntrblkpos = 1;
    }

    // Finally, see if we're in the right area to display our block.
    if ($cntrblkpos != $where) {
        return '';
    }

    $Q = DailyQuote\Quote::getInstance();
    if (!$Q) {
        return '';
    }

    if ($where == 0) {
        // If replacing the homepage, we're responsible for the header
        $retval = COM_siteHeader();
    }

    $T = new Template(Config::get('path') . '/templates');
    $T->set_file('page', 'centerblock.thtml');
    $T->set_var(array(
        'dispquote'     => $Q->getQuote(),
        'dispquoted'    => $Q->getQuoted(),
        'source'        => $Q->getSource(),
        'title'         => $Q->getTitle(),
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
    global $_TABLES, $_CONF, $_USER, $LANG_DQ;

    $content = '';
    $db = Database::getInstance();

    // Link to the plugin index page
    $count = $db->getCount($_TABLES['dailyquote_quotes'], 'enabled', '1', Database::INTEGER);
    $href = COM_createLink("{$LANG_DQ['indexlink']}&nbsp;($count)",
            Config::get('url') . '/index.php');
    $content .= $href . "<br />\n";

    // Link to the category index
    $count = $db->getCount($_TABLES['dailyquote_cat'], 'enabled', '1', Database::INTEGER);
    if ($count > '0') {
        $href = COM_createLink("{$LANG_DQ['catindexlink']}&nbsp;($count)",
            Config::get('url') . '/index.php?mode=categories');
        $content .= $href . "<br />\n";
    }

    //link to the search form
    $href = COM_createLink($LANG_DQ['searchlink'], Config::get('url') . '/index.php?quoted=');
    $content .= $href . "<br />\n";

    // Link to the submission form
    if (SEC_hasRights('dailyquote.submit')) {
        $href = COM_createLink(
            'Add a Quote',
            Config::get('url') . '/index.php?edit'
        );
        $content .= $href . "<br />\n";
    }

    if (SEC_hasRights('dailyquote.edit')) {
        // Link to the manage quotes page
        $href = COM_createLink($LANG_DQ['managelink'],
            Config::get('admin_url') . '/index.php');
        $content .= $href . "<br />\n";

        // Link to the category management page
        $href = COM_createLink($LANG_DQ['manage_cats'],
            Config::get('admin_url') . '/index.php?mode=categories');
        $content .= $href . "<br />\n";
    }
    return $content;
}


/**
 * Displays a random quote from your database in a block.
 *
 * @param   array   $A      Array of block configuration data
 * @param   integer $cat    Optional category ID
 * @return  string  HTML for the block contents.
 */
function phpblock_dailyquote_random($A=array(), $cat='') : string
{
    global $_CONF;

    $retval = '';

    $Coll = new DailyQuote\Collections\QuoteCollection;
    $Coll->withRandom();
    $Quotes = $Coll->getObjects();
    $Quote = reset($Quotes);
    if (!empty($Quotes)) {
        $T = new Template(Config::get('path') . '/templates');
        $T->set_file('page', 'blk_random.thtml');
        $T->set_var(array(
            'randomquote'   => $Quote->getQuote(),
            'quoted'        => $Quote->getQuoted(),
            'sourcedate'    => $Quote->getSourceDate(),
            'source'        => $Quote->getSource(),
        ) );
        $T->parse('output','page');
        $retval = $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
 * Returns list of moderation values.
 *
 * The array returned contains (in order): the row 'qid' label, main plugin
 * table, moderation fields (comma seperated), and plugin submission table
 *
 * @return  array    Returns array of useful moderation values
 */
function plugin_moderationvalues_dailyquote()
{
    global $_TABLES;

    return array(
        'qid',
        $_TABLES['dailyquote_quotes'],
        '',
        '',
    );
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
    global $_TABLES, $LANG_DQ;

    if (plugin_ismoderator_dailyquote()) {
        $plugin = new Plugin();
        $plugin->submissionlabel = Config::get('pi_display_name');
        $plugin->getsubmissionssql =
            "SELECT qid AS id, title,
                FROM_UNIXTIME(dt) as date, source, uid
                FROM {$_TABLES['dailyquote_quotes']}
                WHERE approved = 0";
        $plugin->addSubmissionHeading($LANG_DQ['title']);
        $plugin->addSubmissionHeading($LANG_DQ['date']);
        $plugin->addSubmissionHeading($LANG_DQ['source']);
        return $plugin;
    }
}


/**
 * Get the number of submissions awaiting approval.
 *
 * @return  integer     Number of submissions
 */
function plugin_submissioncount_dailyquote() : int
{
    global $_TABLES;

    return Database::getInstance()->getCount(
        $_TABLES['dailyquote_quotes'],
        array('approved'),
        array(0),
        array(Database::INTEGER)
    );
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
    $is_mod = NULL;
    if ($is_mod === NULL) {
        $is_mod = SEC_hasRights('dailyquote.admin');
    }
    return $is_mod;
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

    try {
        Database::getInstance()->conn->update(
            $_TABLES['dailyquote_quotes'],
            array('approved' => 1),
            array('qid' => $id),
            array(Database::INTEGER, Database::INTEGER)
        );
        DailyQuote\Cache::clear();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
    return '';
}


/**
 * Performs plugin exclusive work for items deleted by moderation.
 * Because this plugin doesn't use a separate submission table,
 * the actual deletion is also handled here.
 *
 * @param   string  $id     Identifying string
 * @return  string          Any wanted HTML output
 */
function plugin_moderationdelete_dailyquote($id)
{
    global $_TABLES;

    // Delete the category lookup entries for the rejected quotation.
    try {
        Database::getInstance()->conn->delete(
            $_TABLES['dailyquote_quoteXcat'],
            array('qid' => $id),
            array(Database::INTEGER)
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
    try {
        Database::getInstance()->conn->delete(
            $_TABLES['dailyquote_quotes'],
            array('qid' => $id),
            array(Database::INTEGER)
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
    return '';
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
    global $_CONF, $_TABLES;

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
            $quote = DailyQuote\Quote::getInstance('', $cat);
            // Don't cache random quote in template
            $nocache = true;
            break;

        case 'quote':
            $qid = COM_sanitizeID($qid);
            if ($qid != '') {
                $quote = DailyQuote\Quote::getInstance($qid);
            }
            break;

        } // switch ($op)

        if ($quote) {
            if (!is_file(Config::get('path') . '/templates/autotags/'.$template.'.thtml')) {
                // Revert back to default template if an invalid one is provided.
                $template = 'default';
            }
            $T = new Template(Config::get('path') . '/templates/autotags');
            $T->set_file('block', $template . '.thtml');
            $T->set_var(array(
                'quote'     => $quote->getQuote(),
                'quoted'    => $quote->getQuoted(),
                'source'    => $incl_src ? $quote->getSource() : '',
                'sourcedate' => $quote->getSourceDate(),
                'qid'       => $quote->getID(),
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
    $db = Database::getInstance();

    if ($enabled == 0) {
        $blocks = array('dailyquote_random', 'dailyquote_dgmenu');
        foreach ($blocks as $block) {
            try {
                $db->conn->update(
                    $_TABLES['blocks'],
                    array('is_enabled' => $enabled),
                    array('name' => $block),
                    array(Database::INTEGER, Database::STRING)
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }
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
    global $_CONF;

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
    $db = Database::getInstance();
    try {
        $db->update(
            $_TABLES['dailyquote_submission'],
            array('uid' => $destUID),
            array('uid' => $origUID),
            array(Database::INTEGER, Database::INTEGER)
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
    try {
        $db->update(
            $_TABLES['dailyquote_quotes'],
            array('uid' => $destUID),
            array('uid' => $origUID),
            array(Database::INTEGER, Database::INTEGER)
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
}


/**
 * Get additional CSS into the site header.
 * Allows for custom CSS to be placed by the site admin.
 *
 * @return  array   List of paths to CSS files
 */
function plugin_getheadercss_dailyquote()
{
    // When looking for CSS files, we'll check in this order:
    //  1. plugin/custom
    //  2. plugin (default distribution version)
    $pi_css = __DIR__ . '/css/';
    $files = array('style.css');
    foreach ($files as $file) {
        if (@file_exists($pi_css . 'custom/' . $file)) {
            $styles[] = $pi_css . 'custom/' . $file;
        } else {
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
        /*case 'id':
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
            break;*/
        }
    }
    /*if (count($fields) == 0) {
        return $retval;
    }
    $fields = array_unique($fields);
    $fields = implode(',', $fields);*/

    $retval = array();
    $db = Database::getInstance();
    $qb = $db->conn->createQueryBuilder();
    $qb->select('*')
       ->from($_TABLES['dailyquote_quotes']);
    if ($id == '*') {
        $qb->where('enabled = 1')
           ->andWhere('approved = 1');
    } else {
        $qb->where('qid = :qid')
           ->setParameter('qid', $id, Database::STRING);
    }
    try {
        $stmt = $qb->execute();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $stmt = false;
    }
    if ($stmt) {
        while ($A = $stmt->fetchAssociative()) {
            foreach ($properties as $p) {
                switch ($p) {
                case 'id':
                    $props[$p] = $A['qid'];
                    break;
                case 'excerpt':
                case 'description':
                case 'content':
                    $props[$p] = $A['quote'];
                    break;
                case 'title':
                    $props[$p] = empty($A['title']) ? 'Untitled' : $A['title'];
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
                    $props[$p] = Config::get('url') . '/index.php?id=' . urlencode($A['qid']);
                    break;
                default:
                    $props[$p] = isset($A[$p]) ? $A[$p] : NULL;
                    break;
                }
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
function DQ_random_quote(?int $qid=NULL, ?int $cid=NULL)
{
    global $_CONF, $LANG_DQ;

    $Coll = new DailyQuote\Collections\QuoteCollection;
    if (empty($qid)) {
        $Coll->withRandom();
    } else {
        $Coll->withQuoteId($qid);
    }
    $Quotes = $Coll->getObjects();

    if (empty($Quotes)) {
        return '';
    }
    $Quote = reset($Quotes);
    $T = new Template($_CONF['path'] . 'plugins/' . Config::PI_NAME . '/templates');
    $T->set_file('page', 'randomquotebox.thtml');
    $T->set_var(array(
        'site_url'  => $_CONF['site_url'],
        'randomboxtitle' => $LANG_DQ['randomboxtitle'],
        'title'     => $Quote->getTitle(),
        'randomquote' => $Quote->getQuote(),
        'quoted'    => $Quote->getQuoted(),
        'source'    => $Quote->getSource(),
        'sourcedate' => $Quote->getSourceDate(),
    ) );
    if ($Quote->getUid() > 1) {
        $T->set_var('subm_by', $LANG_DQ['subm_by'] . ':&nbsp;');
        $T->set_var('dispcontr', COM_getDisplayName($Quote->getUid()));
        $T->set_var('datecontr', '&nbsp;/&nbsp;' . $Quote->getDate());
    }
    $T->parse('output','page');
    return $T->finish($T->get_var('output'));
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
    global $_CONF;

    $doc_path = $_CONF['path_html'] . Config::PI_NAME .
                "/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        $file .= '.html';
        if (!file_exists($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = Config::get('url') . "/docs/$doclang/$file";
         if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = Config::get('url') . "/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = Config::get('url') . "/docs/$doclang/";
    }
    return $doc_url;
}
