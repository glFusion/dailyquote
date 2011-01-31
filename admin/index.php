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

// Clean $_POST and $_GET, in case magic_quotes_gpc is set
$_POST = DQ_stripslashes($_POST);
$_GET = DQ_stripslashes($_GET);


/**
*   Create the administrators' menu.
*
*   @return string  HTML for the admin menu
*/
function DQ_adminMenu($mode='dailyquote')
{
    global $_CONF, $LANG_ADMIN, $LANG_DQ;

    $menu_arr = array();

    if (isset($LANG_DQ['hlp_admin_' . $mode])) {
        $hlp_text = $LANG_DQ['hlp_admin_' . $mode];
    } else {
        $hlp_text = $LANG_DQ['hlp_admin_dailyquote'];
    }

    if ($mode == 'dailyquote') {
        $menu_arr[] = array('text' => $LANG_DQ['newquote'],
                'url' => DQ_ADMIN_URL . '/index.php?edit=quote');
    } else {
        $menu_arr[] = array('text' => $LANG_DQ['glsearchlabel'],
                'url' => DQ_ADMIN_URL . '/index.php?page=quotes');
    }

    if ($mode == 'categories') {
        $menu_arr[] = array('text' => 'New Category',
                'url' => DQ_ADMIN_URL . '/index.php?edit=category');
    } else {
        $menu_arr[] = array('text' => $LANG_DQ['manage_cats'],
                'url' => DQ_ADMIN_URL . '/index.php?page=categories');
    }
              
    $menu_arr[] = array('url' => DQ_ADMIN_URL . '/index.php?page=batchform',
              'text' => $LANG_DQ['batchaddlink']);
    $menu_arr[] = array('url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home']);

    $retval = ADMIN_createMenu($menu_arr, $hlp_text, 
                plugin_geticon_dailyquote());

    return $retval;

}


/**
*   Create an admin list of quotes.
*
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
        array('field' => 'delete',
            'text' => $LANG_ADMIN['delete'], 'sort' => false),
    );

    $defsort_arr = array('field' => 'dt', 'direction' => 'desc');

    $retval .= COM_startBlock($_CONF_DQ['pi_display_name'], '', 
                COM_getBlockTemplate('_admin_block', 'header'));

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


/**
*   Display a single formatted field in the admin quote list.
*
*   @param  string  $fieldname  Name of the field
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Name->Value array of all fields
*   @param  array   $icon_arr   System icon array
*   @return string              HTML for the field display
*/
function DQ_admin_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_DQ, $_CONF_DQ;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval .= COM_createLink(
            $icon_arr['edit'],
            DQ_ADMIN_URL . "/index.php?edit=quote&amp;id={$A['id']}"
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

    case 'delete':
        $retval = COM_createLink('<img src='. $_CONF['layout_url'] .
            '/images/admin/delete.png>',
            DQ_ADMIN_URL . "/index.php?delete=dailyquote&amp;id={$A['id']}");
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
    COM_404();
    exit;
}

// Only let admin users access this page
if (!SEC_hasRights('dailyquote.admin,dailyquote.edit', 'OR')) {
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

$action = '';
$expected = array(
    'edit', 'moderate', 'cancel', 'save', 
    'delete', 'delitem', 'validate', 'mode', 
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $var = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
    	$action = $provided;
        $var = $_GET[$provided];
        break;
    }
}

/*
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
*/

$q_id = isset($_REQUEST['id']) ? COM_sanitizeID($_REQUEST['id'], false) : '';
$type = isset($_REQUEST['xtype']) ? $_REQUEST['xtype'] : '';

/*if (isset($_POST['delete_btn']) && !empty($_POST['delete_btn'])) {
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
*/
if (isset($_REQUEST['page'])) {
    $page = COM_applyFilter($_REQUEST['page']);
} else {
    $page = $action;
}

$content = '';      // initialize variable for page content
$A = array();       // initialize array for form vars

switch ($action) {
case 'mode':
    switch ($var) {
    case 'edit':
        $page = $var;
        break;
    }
    break;

case 'categories':
case 'moderate':
    $page = $action;
    break;

/*case $LANG_ADMIN['save']:
case $LANG12[8]:
case 'savequote':*/
case 'save':
    switch ($type) {
    case 'quote':
        USES_dailyquote_class_quote();
        $Q = new DailyQuote($q_id);
        $Q->Save($_POST);
        $page = 'quotes';
    break;

    case 'submission':
        USES_dailyquote_class_quote();
        $Q = new Dailyquote();
        $status = $Q->Save($_POST);
        $q_id = $Q->GetID();
        if ($status == '' && !empty($q_id)) {
            DB_delete($_TABLES['dailyquote_submission'], 'id', $q_id);
            plugin_moderationapprove_dailyquote($q_id);
            $page = 'moderation';
        } else {
            $page = 'moderate';
            $A = $_POST;
            $content .= $status;
        }
        break;
    case 'category':
        USES_dailyquote_class_category();
        $C = new Category($_POST['id']);
        $C->Save($_POST);
        $page = 'categories';
        break;
    }
    break;

case 'delitem':
//case 'delMultiQuote':
    if (is_array($_POST['delitem'])) {
        foreach ($_POST['delitem'] as $item) {
            DailyQuote::Delete($item);
        }
        $page = 'quotes';
    }
    break;

case 'delete':
    switch ($var) {
    case 'dailyquote':
    case 'quote':
        DailyQuote::Delete($q_id);
        break;
    case 'submission':
        if ($q_id != '') {
            DB_delete($_TABLES['dailyquote_submission'], 'id', $q_id);
        }
        $page = 'moderation';
        break;
    case 'category':
        USES_dailyquote_class_category();
        Category::Delete($_REQUEST['id']);
        $page = 'categories';
        break;
    }
    break;

//case 'savecategory':

//case 'deletecat':

case 'processbatch':
    USES_dailyquote_batch();
    $content .= DQ_process_batch();
    $page = 'adminlist';
    break;

//case 'edit':
    // 
}

switch ($page) {
case 'edit':
    switch ($var) {
    case 'category':
        USES_dailyquote_class_category();
        $C = new Category($q_id);
        $content .= $C->EditForm();
        break;
    case 'quote':
    default:
        if ($q_id != '') {
            $A = DailyQuote::getQuote($q_id);
        }
        USES_dailyquote_submitform();
        $content .= DQ_editForm('edit', $A, true);
        break;
    }
    break;

//case 'editsubmission':
case 'moderate':
    if ($q_id != '' && empty($A)) {
        $result = DB_query("SELECT * 
                FROM {$_TABLES['dailyquote_submission']}
                WHERE id='" . DB_escapeString($q_id) . "'");
        if ($result && DB_numRows($result) == 1) {
            $A = DB_fetchArray($result);
        }
    }
    USES_dailyquote_submitform();
    $content .= DQ_editForm($action, $A, true);
    break;

case 'moderation':
    echo COM_refresh($_CONF['site_admin_url'].'/moderation.php');
    exit;

case 'categories':
    USES_dailyquote_class_category();
    $content .= Category::AdminList();
    break;

case 'batchform':
    USES_dailyquote_batch();
    $content .= DQ_adminMenu($page);
    $content .= DQ_batch_form();
    break;

case 'quotes':
default:
    $content .= DQ_adminList();
    break;
}

$T = new Template(DQ_PI_PATH . '/templates');
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
