<?php
//  $Id: functions.inc 15 2009-09-07 05:00:31Z root $
/**
*   Plugin-specific functions for the DailyQuote plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}


/**
*   Displays a quote box at the top of the regular listings.
*   If no quote id is specified, a random one is selected.  If a
*   category ID is given, then the random quote is selected from among
*   that category.
*
*   @param  string  $qid    Quote ID to retrieve
*   @param  integer $cid    Category ID for random quotes
*   @return string          HTML display for the quote
*/
function DQ_random_quote($qid='', $cid='')
{
    global $_CONF, $LANG_DQ, $_CONF_DQ;

    USES_dailyquote_class_quote();
    $A = DailyQuote::getQuote($qid, $cid);
    if (!is_array($A)) {
        return '';
    }

    if ($A['quote'] == '') {
        return '';
    }

    $T = new Template($_CONF['path'] . 'plugins/' . $_CONF_DQ['pi_name'] . '/templates');
    $T->set_file('page', 'randomquotebox.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('randomboxtitle', $LANG_DQ['randomboxtitle']);
    if ($_CONF_DQ['phpblk_title'] == 1) {
        $T->set_var('title', htmlspecialchars($A['title']));
    } else {
        $T->set_var('title', '');
    }

    $T->set_var('randomquote', htmlspecialchars($A['quote']));
    $T->set_var('quoted', htmlspecialchars($A['quoted']));
    if ($_CONF_DQ['phpblk_srcdate'] == 1) {
        if (!empty($A['source'])) {
            $dispsource = "&nbsp;--&nbsp;" . htmlspecialchars($A['source']);
            $T->set_var('source', $dispource);
        }
        if (!empty($Sourcedate)) {
            $dispsourcedate = "&nbsp;&nbsp;(" . htmlspecialchars($A['sourcedate']) . ")";
            $T->set_var('sourcedate', $dispsourcedate);
        }
    }
    if (($_CONF_DQ['phpblock_contribdate'] == 1) && ($username != $LANG_DQ['anonymous'])) {
        $T->set_var('subm_by', $LANG_DQ['subm_by'] . ':&nbsp;');
        $T->set_var('dispcontr', $username);
        $T->set_var('dispcontr', $username);
        $T->set_var('datecontr', '&nbsp;/&nbsp;' . htmlspecialchars($A['date']));
    }
/*    if ($_CONF_DQ['phpblock_categories'] == 1) {
        $T->set_var('cat', '&nbsp;&nbsp;' . $LANG_DQ['cat'] . ':&nbsp;');
        $T->set_var('dispcat', $catlist);
    }*/
    $T->parse('output','page');
    $retval = $T->finish($T->get_var('output'));
    return $retval;
}


/**
*   Create the centerblock
*
*   @see    plugin_centerblock_dailyquote()
*   @param  integer $where  Which area is being displayed now
*   @param  integer $page   Page number
*   @param  string  $topic  Topic ID, or empty string
*   @return string          HTML for centerblock
*/
function DQ_centerblock($where = 1, $page = 1, $topic = '')
{
    global $_CONF, $_USER, $_TABLES, $PHP_SELF, $LANG_DQ, $_CONF_DQ;
    
    USES_dailyquote_class_quote();

    $retval = '';
    $img_dir = $_CONF['layout_url'] . '/' . $_CONF_DQ['pi_name'] . '/image_set';

    // If centerblock not enabled, or just for homepage and
    // we're not on the homepage, just return
    if ($_CONF_DQ['cb_enable'] == 0 ||
            ($CONF_DQ['cb_homepage'] == 1 && ($page > 1 || $topic != '')) ||
            !DailyQuote::hasAccess(2))
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
        $wherenames = array('tid', 'featured', 'draft_flag');
        $wherevalues = array($topic, 0, 0);
        
//        $wheresql = "WHERE tid='$topic' AND featured > 0";
    } else {
        $wherenames = array('featured', 'draft_flag');
        $wherevalues = array(1, 0);
//        $wheresql = "WHERE featured = 1";
    }

    $story_count = DB_count($_TABLES['stories'], $wherenames, $wherevalues);
    if ($story_count == 0 && $_CONF_DQ['cb_pos'] == 2) {
        // If the centerblock comes after the featured story, and there
        // are no stories, put the centerblock at the top.
        $cntrblkpos = 1;
    }

    if ($cntrblkpos != $where) {
        return '';
    }

    $Q = DailyQuote::getQuote();
    if (empty($Q)) {
        return '';
    }

    //list($quote, $quoted, $title, $source, $sourcedate, $dt) = $dqarray;
    if (empty($Q['quote'])) {
        return '';
    }

    if ($where == 0) {
        $retval = COM_siteHeader();
    }

    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'centerblock.thtml');

    $T->set_var('dispquote', $Q['quote']);
    $T->set_var('dispquoted', $Q['quoted']);

    if (!empty($Source)) {
        $dispsource = "&nbsp;--&nbsp;" . $Q['source'];
        $T->set_var('source', $dispsource);
    }

    if ($_CONF_DQ['cb_srcdate'] && !empty($Q['sourcedate'])) {
        $dispsourcedate = "&nbsp;&nbsp;({$Q['sourcedate']})";
        $T->set_var('sourcedate', $dispsourcedate);
    }

    if ($_CONF_DQ['cb_title'] && !empty($Q['title'])) {
        $title = '<p style="text-align: left; font-weight: bold; text-decoration: underline;">';
        $title .= $Q['title'];
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

    $retval .= $T->finish($T->get_var('output'));

    if ($where == 0) {
        $retval .= COM_siteFooter();
    }

    return $retval;
}


/**
*   Gets the categories for a given quote as a comma-separated list.
*
*   @param  string  $qid    Quote ID
*   @return string          List of categories
*/
function DQ_catlistDisplay($qid)
{
    global $_TABLES;

    $catsql = "SELECT c.id, c.name 
                FROM 
                    {$_TABLES['dailyquote_cat']} c, 
                    {$_TABLES['dailyquote_quoteXcat']} l 
                WHERE 
                    l.qid = '" . addslashes($qid) . "' 
                AND 
                    c.id = l.cid 
                AND 
                    c.enabled = '1'";
    //echo $catsql;die;
    $cat = DB_query($catsql);
    $cats = array();
    while ($catrow = DB_fetchArray($cat)) {
        $cats[] = DQ_catlink($catrow['id'],$catrow['name']);
    }
    $catlist = join(',', $cats);

    return $catlist;

}


?>
