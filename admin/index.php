<?php
/**
 * Administrative entry point for the DailyQuote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2017 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion functions */
require_once('../../../lib-common.php');

// MAIN
// If plugin is installed but not enabled, display an error and exit gracefully
if (!in_array('dailyquote', $_PLUGINS)) {
    COM_404();
    exit;
}

// Only let admin users access this page
if (!SEC_hasRights('dailyquote.admin,dailyquote.edit', 'OR')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote Admin page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    COM_404();
    exit;
}

$action = '';
$expected = array(
    'editquote', 'savequote', 'delquote',
    'editcat', 'savecat', 'delcat',
    'savemoderation', 'processbatch',
    'moderate',
    'delete', 'delitem', 'validate', 'mode',
    'quotes', 'categories', 'batchform',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionvar = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
    	$action = $provided;
        $actionvar = $_GET[$provided];
        break;
    }
}

$item_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if (isset($_REQUEST['page'])) {
    $page = COM_applyFilter($_REQUEST['page']);
} else {
    $page = $action;
}

$content = '';      // initialize variable for page content
$A = array();       // initialize array for form vars

switch ($action) {
case 'mode':
    $page = $actionvar;
    break;

case 'savecat':
    $C = new DailyQuote\Category($_POST['id']);
    $C->Save($_POST);
    $page = 'categories';
    break;

case 'savequote':
    $Q = DailyQuote\Quote::getInstance($item_id);
    $message = $Q->Save($_POST);
    if (!empty($message)) {
        COM_setMsg($message);
    }
    echo COM_refresh(DQ_ADMIN_URL);
    break;

case 'savemoderation':
    // Save the quote to the prod table and delete from the queue
    $Q = DailyQuote\Quote::getInstance($item_id);
    $message = $Q->Save($_POST, 'dailyquote_quotes');
    if (!empty($message)) {
        // Error saving
        COM_setMsg($message);
    } else {
        DB_delete($_TABLES['dailyquote_submission'], "id='" . DB_escapeString($item_id));
    }
    echo COM_refresh(DQ_ADMIN_URL);
    break;

case 'delquote':
    DailyQuote\Quote::Delete($item_id);
    COM_refresh(DQ_ADMIN_URL);
    break;

case 'delitem':
    // Handle multiple quote deletion
    if (is_array($_POST['delitem'])) {
        foreach ($_POST['delitem'] as $item) {
            DailyQuote\Quote::Delete($item);
        }
        $page = 'quotes';
    }
    break;

case 'delcat':
    DailyQuote\Category::Delete($item_id);
    $page = 'categories';
    break;

case 'processbatch':
    $content = DailyQuote\Batch::process();
    $page = 'none';
    break;

default:
    $page = $action;
    break;
}

switch ($page) {
case 'edit':
    // "edit" is here so this will work with submit.php
case 'editquote':
    $Q = DailyQuote\Quote::getInstance($item_id);
    $content .= $Q->Edit();
    break;

case 'editcat':
    $C = new DailyQuote\Category($item_id);
    $content .= $C->EditForm();
    break;

//case 'editsubmission':
case 'moderate':
    $Q = DailyQuote\Quote::getInstance($item_id);
    $Q->setTable('quotes');
    $Q->isNew = true;
    $content .= $Q->Edit($action);
    break;

case 'moderation':
    echo COM_refresh($_CONF['site_admin_url'].'/moderation.php');
    exit;

case 'categories':
    $content .= DailyQuote\Category::AdminList();
    break;

case 'batchform':
    $content .= DailyQuote\Batch::form();
    break;

case 'none':
    // Used if the action sets the entire page content
    break;

case 'quotes':
default:
    $page = 'quotes';
    $content .= DailyQuote\Quote::adminList();
    break;
}

$display = COM_siteHeader();
$display .= COM_startBlock($_CONF_DQ['pi_display_name'] . ' ver. ' . $_CONF_DQ['pi_version'], '',
                COM_getBlockTemplate('_admin_block', 'header'));
$display .= DailyQuote\Menu::Admin($page);
$display .= $content;
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display .= COM_siteFooter();
echo $display;

?>
