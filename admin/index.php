<?php
//  $Id$
/**
*   Administrative entry point for the DailyQuote plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.1.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion functions */
require_once('../../../lib-common.php');

USES_lib_admin();
USES_dailyquote_class_quote();
USES_dailyquote_functions();


function DQ_adminMenu()
{
    global $_CONF, $LANG_ADMIN, $LANG_DQ;

    $menu_arr = array (
        array('url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home']),
        array('url' => DQ_ADMIN_URL . '/index.php?mode=edit',
              'text' => $LANG_DQ['newquote']),
        array('url' => DQ_ADMIN_URL . '/index.php?mode=categories',
              'text' => $LANG_DQ['manage_cats']),
        array('url' => DQ_ADMIN_URL . '/index.php?mode=batchform',
              'text' => $LANG_DQ['batchaddlink']),
    );
    $retval = ADMIN_createMenu($menu_arr, $LANG_DQ['admin_hdr'], 
            plugin_geticon_dailyquote());

    return $retval;

}


/**
*   Create an admin list of quotes
*   @return string  HTML for list
*/
function DQ_adminList()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
    global $_CONF_DQ, $LANG_DQ;

    $retval = '';

    $header_arr = array(      # display 'text' and use table field 'field'
        array('field' => 'edit', 
            'text' => $LANG_ADMIN['edit'], 'sort' => false),
        array('field' => 'enabled', 
            'text' => $LANG_DQ['enabled'], 'sort' => false),
        array('field' => 'id', 
            'text' => 'Quote ID', 'sort' => true),
        array('field' => 'dt', 
            'text' => $LANG_DQ['date'], 'sort' => true),
        array('field' => 'quoted', 
            'text' => $LANG_DQ['quoted'], 'sort' => true),
        array('field' => 'title', 
            'text' => $LANG_DQ['title'], 'sort' => true),
        array('field' => 'quote', 
            'text' => $LANG_DQ['quote'], 'sort' => true),
    );

    $defsort_arr = array('field' => 'dt', 'direction' => 'desc');

    $retval .= COM_startBlock('WhereAmI', '', COM_getBlockTemplate('_admin_block', 'header'));

    $retval .= DQ_adminMenu();

    $text_arr = array(
        'has_extras' => true,
        'form_url' => "{$_CONF['site_admin_url']}/plugins/{$_CONF_DQ['pi_name']}/index.php?type=quote"
    );

    $options = array('chkdelete' => 'true', 'chkfield' => 'id');

    $query_arr = array('table' => 'dailyquote',
        'sql' => "SELECT * FROM {$_TABLES['dailyquote_quotes']} ",
        'query_fields' => array('title', 'quotes', 'quoted'),
        'default_filter' => 'WHERE 1=1'
        //'default_filter' => COM_getPermSql ()
    );

    $retval .= ADMIN_list('dailyquote', 'DQ_admin_getListField', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', 
                    $options, $form_arr);
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}


function DQ_admin_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_DQ, $_CONF_DQ;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval .= COM_createLink(
            $icon_arr['edit'],
            "{$_CONF['site_admin_url']}/plugins/dailyquote/index.php?mode=edit&amp;id={$A['id']}"
        );
        break;

    case 'enabled':
        if ($fieldvalue == 1) {
            $ena_icon = 'on.png';
            $enabled = 0;
        } else {
            $ena_icon = 'off.png';
            $enabled = 1;
        }
        $retval .= "<span id=togena{$A['id']}>\n" .
                "<img src=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}" . 
                    "/images/{$ena_icon}\" ".
                "onclick='DQ_toggleEnabled({$enabled}, \"{$A['id']}\", ".
                "\"quote\", \"{$_CONF['site_url']}\");'>\n" .
                "</span>\n";
        break;
    case 'title':
        $retval = stripslashes($A['title']);
        break;
    case 'quote':
        $max_len = 40;
        $ellipses = strlen($A['quote']) > $max_len ? ' ...' : '';
        $retval = substr(stripslashes($A['quote']), 0, $max_len) . $ellipses;
        break;
    case 'dt';
        $retval = date('Y-m-d', $A['dt']);
        break;
    default:
        $retval = $fieldvalue;
        break;
    }

    return $retval;
}



/**
*   MAIN
*/
// If plugin is installed but not enabled, display an error and exit gracefully
if (!in_array('dailyquote', $_PLUGINS)) {
    $display = COM_siteHeader();
    $display .= "<span class=\"alert\">";
    $display .= COM_startBlock ('Alert');
    $display .= 'This function is not available.';
    $display .= COM_endBlock();
    $display .= "</span>";
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}

// Only let admin users access this page
if (!SEC_inGroup('dailyquote Admin')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote Admin page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_DQ['access_denied']);
    $display .= $LANG_DQ['access_denied_msg'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}

if (isset($_REQUEST['mode'])) {
    $mode = $_REQUEST['mode'];
} elseif (isset($_POST['delitem']) && !empty($_POST['delitem'])) {
    switch($_GET['type']) {
    case 'quote':
        $mode = 'delMultiQuote';
        break;
    default:
        $mode = 'adminquotes';
        break;
    }
} else {
    $mode = 'adminquotes';
}

$q_id = isset($_REQUEST['id']) ? COM_sanitizeID($_REQUEST['id'], false) : '';

if (isset($_POST['delete_btn']) && !empty($_POST['delete_btn'])) {
    if ($mode == 'edit') {
        $table = 'dailyquote_quotes';
    } else {
        $table = 'dailyquote_submission';
    }
    DailyQuote::Delete($q_id, $table);
    $mode = '';
} elseif (isset($_POST['submit_btn']) && !empty($_POST['submit_btn']) &&
        $mode == 'edit') {
    $mode = 'savequote';
}   

if (isset($_REQUEST['page'])) {
    $page = COM_applyFilter($_REQUEST['page']);
} else {
    $page = $mode;
}

$content = '';      // initialize variable for page content
$A = array();       // initialize array for form vars

switch ($mode) {
case $LANG_ADMIN['save']:
case $LANG12[8]:
case 'savequote':
    USES_dailyquote_class_quote();
    $Q = new DailyQuote($q_id);
    $Q->Save($_POST);
    break;

case 'moderation':
    if (isset($_POST['action']) && $_POST['action'] == 'approve') {
        USES_dailyquote_class_quote();
        $Q = new Dailyquote();
        $status = $Q->Save($_POST);
        if ($status == '' ) {
            DB_delete($_TABLES['dailyquote_submission'], 'qid', $Q->GetID());
            plugin_moderationapprove_dailyquote($Q->GetID());
            $page = 'moderation';
        } else {
            $page = 'editsubmission';
            $A = $_POST;
            $content .= $status;
        }
    } else {
        $page = 'moderation';
    }
    break;

case 'delMultiQuote':
    foreach ($_POST['delitem'] as $item) {
        DailyQuote::Delete($item);
    }
    $page = 'adminquotes';
    break;

case 'deletequote':
    DailyQuote::Delete($q_id);
    break;

case 'savecategory':
    USES_dailyquote_class_category();
    $C = new Category($_POST['id']);
    $C->Save($_POST);
    $page = 'categories';
    break;

case 'deletecat':
    USES_dailyquote_class_category();
    Category::Delete($_REQUEST['id']);
    $page = 'categories';
    break;

case 'processbatch':
    USES_dailyquote_batch();
    $content .= DQ_process_batch();
    $page = 'adminlist';
    break;

case 'edit':
    // 
}


switch ($page) {
case 'edit':
    if ($q_id != '') {
        $A = DailyQuote::getQuote($q_id);
        /*$result = DB_query("SELECT * from {$_TABLES['dailyquote_quotes']}
                WHERE ID='$q_id'");
        if ($result && DB_numRows($result) == 1) {
            $A = DB_fetchArray($result);
        }*/
    }
    USES_dailyquote_submitform();
    $content .= DQ_editForm($mode, $A, true);
    break;

case 'editsubmission':
    if ($q_id != '' && empty($A)) {
        $result = DB_query("SELECT * from {$_TABLES['dailyquote_submission']}
                WHERE ID='$q_id'");
        if ($result && DB_numRows($result) == 1) {
            $A = DB_fetchArray($result);
        }
    }
    USES_dailyquote_submitform();
    $content .= DQ_editForm($mode, $A, true);
    break;

case 'categories':
    USES_dailyquote_class_category();
    $content .= Category::AdminList();
    break;

case 'editcategory':
    USES_dailyquote_class_category();
    $C = new Category($q_id);
    $content .= $C->EditForm();
    break;

case 'batchform':
    USES_dailyquote_batch();
    $content .= DQ_adminMenu();
    $content .= DQ_batch_form();
    break;

case 'adminlist':
default:
    $content .= DQ_adminList();
    break;
}

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqheader.thtml');
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('site_admin_url', $_CONF['site_admin_url']);
//$T->set_var('gimmebreak', $LANG_DQ['gimmebreak']);
$T->set_var('indextitle', $LANG_DQ['admintitle']);
$T->set_var('indexintro', $LANG_DQ['adminintro']);
$T->parse('output','page');

$display = COM_siteHeader();
$display .= $content;
$display .= COM_siteFooter();

echo $display;

?>
