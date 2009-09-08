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

/** Import core glFusion functions */
require_once('../../../lib-common.php');
/** Import database functions - DEPRECATED */
require_once('dqdatabase.php');

USES_dailyquote_class_quote();

/**
*   Create an admin list of quotes
*   @return string  HTML for list
*/
function DQ_adminList()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
    global $_CONF_DQ, $LANG_DQ;

    USES_lib_admin();

    $pi_admin_url = "{$_CONF['site_admin_url']}/plugins/{$_CONF_DQ['pi_name']}/index.php";
    $retval = '';

    $header_arr = array(      # display 'text' and use table field 'field'
        array('text' => $LANG_ADMIN['edit'], 'field' => 'edit', 'sort' => false),
        array('text' => 'Quote ID', 'field' => 'id', 'sort' => true),
        array('text' => 'Date', 'field' => 'dt', 'sort' => true),
        array('text' => 'Quoted', 'field' => 'quoted', 'sort' => true),
        array('text' => 'Title', 'field' => 'title', 'sort' => true),
        array('text' => 'Content', 'field' => 'quotes', 'sort' => true),
        //array('text' => $LANG_ACCESS['access'], 'field' => 'access', 'sort' => false)
    );

    $menu_arr = array (
        array('url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home']),
        array('url' => DQ_ADMIN_URL . '/index.php?mode=edit',
              'text' => $LANG_DQ['newquote']),
        array('url' => DQ_ADMIN_URL . '/index.php?mode=categories',
              'text' => $LANG_DQ['manage_cats']),
    );

    $defsort_arr = array('field' => 'dt', 'direction' => 'desc');

    $retval .= COM_startBlock('WhereAmI', '', COM_getBlockTemplate('_admin_block', 'header'));

    $retval .= ADMIN_createMenu($menu_arr, $LANG_DQ['admin_hdr'], plugin_geticon_dailyquote());

    $text_arr = array(
        'has_extras' => true,
        'form_url' => "{$_CONF['site_admin_url']}/plugins/{$_CONF_DQ['pi_name']}/index.php"
    );

    $query_arr = array('table' => 'dailyquote',
        'sql' => "SELECT * FROM {$_TABLES['dailyquote_quotes']} ",
        'query_fields' => array('title', 'quotes', 'quoted'),
        'default_filter' => 'WHERE 1=1'
        //'default_filter' => COM_getPermSql ()
    );

    $retval .= ADMIN_list('dailyquote', 'DQ_admin_getListField', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);
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
        if ($A['status'] == 1) {
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
        $retval .= COM_createLink(COM_createImage(
                $_CONF['site_url'].'/dailyquote/images/deleteitem.png',
                'Delete this quote',
            array('class'=>'gl_mootip',
                'onclick'=>'return confirm(\'Do you really want to delete this item?\');',
                'title' => 'Delete this quote',
            )),
            $pi_admin_url . '?mode=deletequote&id=' . $A['id']
        );
/*        $retval .= '<form action={action_url} method="post">
            <input type=hidden name="id" value="' . $A['id'] . '">
            <input type=hidden name="action" value="deletequote">
            <input type="image" 
              src="'{site_url}/photocomp/images/deleteitem.png" 
          height="16" width="16" border="0" 
          alt="{$LANG_PHOTO['delete']}" 
          title="{$LANG_PHOTO['delete_item']}"
          onclick="return confirm('Do you really want to delete this item?');" 
          class="gl_mootip">
      </form>*/
        break;
    case 'title':
        $retval = stripslashes($A['title']);
        break;
    case 'quote':
        $retval = substr(stripslashes($A['quote']), 0, 100);
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
if (!SEC_inGroup('Root')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote Admin page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_PL00['access_denied']);
    $display .= $LANG_DQ00['access_denied_msg'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}

if (isset($_REQUEST['mode'])) {
    $mode = COM_applyFilter($_REQUEST['mode']);
} else {
    $mode = 'adminquotes';
}

$q_id = isset($_REQUEST['id']) ? COM_sanitizeID($_REQUEST['id']) : '';

if (isset($_REQUEST['page'])) {
    $page = COM_applyFilter($_REQUEST['page']);
} else {
    $page = $mode;
}

$content = '';      // initialize variable for page content
$A = array();       // initialize array for form vars

$admin_url = $_CONF['site_admin_url']. '/plugins/'.
        $_CONF_DQ['pi_name'] . '/index.php';


switch ($mode) {
case $LANG_ADMIN['save']:
case $LANG12[8]:
    if ($q_id != '') {
        USES_dailyquote_functions();
        DQ_updateQuote($_POST);
    }
    break;

case 'deletequote':
    DailyQuote::Delete($_REQUEST['id']);
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
    if ($q_id != '') {
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

case 'adminlist':
default:
    $content .= DQ_adminList();
    break;
}

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqheader.thtml');
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('site_admin_url', $_CONF['site_admin_url']);
$T->set_var('gimmebreak', $LANG_DQ['gimmebreak']);
$T->set_var('indextitle', $LANG_DQ['admintitle']);
$T->set_var('indexintro', $LANG_DQ['adminintro']);
$T->parse('output','page');

$display = COM_siteHeader();
//$display .= $T->finish($T->get_var('output'));

/*if (isset($_POST['submit'])){
    if ($_POST['submit'] == $LANG_DQ['update']){
        $display .= update_config();
    } elseif ($_POST['submit'] == $LANG_DQ['default']){
        $display .= default_cfg();
    }
}*/
//$display .= link_row();

//$display .= config_form();
$display .= $content;

/*$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));*/

//$display .= COM_endBlock();
$display .= COM_siteFooter();

echo $display;

?>
