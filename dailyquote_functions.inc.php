<?php
//  $Id: functions.inc 15 2009-09-07 05:00:31Z root $
/**
*   Plugin-specific functions for the DailyQuote plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2010 Lee Garner
*   @package    dailyquote
*   @version    0.1.2
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


/**
*   Strips slashes if magic_quotes_gpc is on.
*
*   @since  version 0.1.2
*   @param  mixed   $var    Value or array of values to strip.
*   @return mixed           Stripped value or array of values.
*/
function DQ_stripslashes($var)
{
    if (get_magic_quotes_gpc()) {
        if (is_array($var)) {
            return array_map('DQ_stripslashes', $var);
        } else {
            return stripslashes($var);
        }
    } else {
        return $var;
    }
}


?>
