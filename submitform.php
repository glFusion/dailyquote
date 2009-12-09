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

    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'editformheader.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    //displays the add quote form for single quotations
    $T->set_file('page', 'editform.thtml');
    $T->set_var('gltoken_name', CSRF_TOKEN);
    $T->set_var('gltoken', SEC_createToken());

    $action = '';
    if ($admin) {
        if ($mode == 'editsubmission') {
            $mode = 'moderation';
            $action='approve';
        }
        $action_url = DQ_ADMIN_URL . '/index.php';
    } else {
        $action_url = $_CONF['site_url']. '/submit.php';
    }
    $T->set_var('action_url', $action_url);
    $T->set_var('mode', $mode);
    $T->set_var('form_action', $action);

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
                            WHERE enabled='1' 
                            ORDER BY name")) {
        $errstatus = 1;
    } else {
        $numrows = DB_numRows($result);
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
            if ($A['id'] != '' && DB_getItem($_TABLES['dailyquote_lookup'], 'qid', 
                    "cid={$row['id']} AND qid = '{$A['id']}'") == $A['id']) {
                $T->set_var('checked', ' checked ');
            } else {
                $T->set_var('checked', '');
            }
            $T->set_var('catoption', $row['name']);
            $T->set_var('catid', $row['id']);

            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            $i++;
            if ($i % $down === 0 && $i % $colnum !== 0) {
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'addcol2.thtml');
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
            }
        }

    }
    
    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'closeeditform.thtml');
    if ($admin) {
        $T->set_var('show_delbtn', 'true');
    }
    $T->set_var('catreadme', $LANG_DQ['catreadme']);
    $T->set_var('submit', $LANG_DQ['submitquote']);
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    /*$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    if ($mode == 'editsubmission') {
        $T->set_file('page', 'closeeditsubmission.thtml');
    } else {
        $T->set_file('page', 'addformfooter.thtml');
    }
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));*/

    return $retval;
}

?>
