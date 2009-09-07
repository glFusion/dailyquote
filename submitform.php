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


/**
*   Displays the add form for single quotes.
*/
function DQ_editForm($mode='submit', $A='', $admin=false)
{
    global $_TABLES, $_CONF, $_USER, $LANG_DQ, $_CONF_DQ;

    $retval = '';

    $T = new Template("{$_CONF['path']}plugins/{$_CONF_DQ['pi_name']}/templates");
    $T->set_file('page', 'editformheader.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    //displays the add quote form for single quotations
    $T->set_file('page', 'editform.thtml');

    if ($admin) {
        $T->set_var('action_url', $_CONF['site_admin_url']. '/plugins/'. $_CONF_DQ['pi_name']. '/index.php');
    } else {
        $T->set_var('action_url', $_CONF['site_url']. '/plugins/'. $_CONF_DQ['pi_name']. '/index.php');
    }
    // Load existing values, if any
    if (is_array($A) && !empty($A)) {
        $T->set_var('quote', $A['quote']);
        $T->set_var('quoted', $A['quoted']);
        $T->set_var('title', $A['title']);
        $T->set_var('source', $A['source']);
        $T->set_var('sourcedate', $A['sourcedate']);
        $T->set_var('uid', $A['uid']);
        $T->set_var('id', $A['id']);
        $T->set_var('hidden_vars',
            '<input type="hidden" name="date" value="'.$A['dtadded']. '">');
    } else {
        $T->set_var('uid', $_USER['uid']);
        $T->set_var('id', '');
    }

    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('pi_name', $_CONF_DQ['pi_name']);

    //retrieve categories from db if any and display
    if (!$result = DB_query("SELECT id, name 
                            FROM {$_TABLES['dailyquote_cat']} 
                            WHERE status='1' 
                            ORDER BY name")) {
        $errstatus = 1;
    } else {
        $numrows = DB_numRows($result);
    }

    if ($numrows == 0) {
        //first cat must be created--doesn't matter who does it
        $T->set_var('firstcat', $LANG_DQ['firstcat']);
        $T->set_var('catinput', '<input name="cat[]" type="text" size="18" value="" />');
    } else {
        //check perm to create category
        if (!$query = DB_query("SELECT loginaddcat 
                                FROM {$_TABLES['dailyquote_settings']}")) {
            $errstatus = 1;
        } else {
            list($loginaddcat) = DB_fetchArray($query);
            if (($loginaddcat == 0) && (!SEC_hasRights('dailyquote.edit'))){
                $T->set_var('choosecat', $LANG_DQ['choosecat']);
            } else {
                $T->set_var('choosecat', $LANG_DQ['choosecat1']);
                $T->set_var('catinput', '<input name="cat[]" type="text" size="18" value="" />');
            }
        }
    }
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));


    // display $colnum vertical columns
    // if you increase or decrease this number,
    // then you'll need to adjust the cell width in the addcol and 
    // addcatcol.thtml files
    if ($numrows > 0) {
        $i = 0;
        $colnum = 5;
        $down = ceil($numrows/$colnum);
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'addcol.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));

        while ($row = DB_fetchArray($result)) {
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'catoption.thtml');
            if ($chkst = DB_query(
                    "SELECT * FROM {$_TABLES['dailyquote_lookup']} 
                    WHERE cid='{$row['id']}' 
                    AND status='0' 
                    LIMIT 1")) {
                if (DB_numRows($chkst) > 0) {
                    $T->set_var('discat', ' color: #808080;');
                }
            }
            $T->set_var('catoption', $row['name']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            $i++;
            if (($i % $down === 0) && ($i % $colnum !== 0)){
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'addcol2.thtml');
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
            }
        }

    }
    
    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'closeeditform.thtml');
    $T->set_var('catreadme', $LANG_DQ['catreadme']);
    $T->set_var('submit', $LANG_DQ['submitquote']);
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    if ($mode == 'editsubmission') {
        $T->set_file('page', 'closeeditsubmission.thtml');
    } else {
        $T->set_file('page', 'addformfooter.thtml');
    }
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}

?>
