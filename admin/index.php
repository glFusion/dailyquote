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
use DailyQuote\Config;
use glFusion\Log\Log;

// Only let admin users access this page
if (!SEC_hasRights('dailyquote.admin,dailyquote.edit', 'OR')) {
    // Someone is trying to illegally access this page
    Log::write(
        'system', Log::ERROR,
        "Someone has tried to illegally access the dailyquote Admin page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR"
    );
    COM_404();
    exit;
}

$Request = DailyQuote\Models\Request::getInstance();
$expected = array(
    'editquote', 'savequote', 'delquote',
    'editcat', 'savecat', 'delcat',
    'savemoderation', 'processbatch',
    'moderate',
    'delete', 'delbutton_x', 'delitem', 'validate', 'mode',
    'quotes', 'categories', 'batchform',
);
list($action, $actionval) = $Request->getAction($expected);
$qid = $Request->getInt('qid');
$view = $Request->getString('view', $action);
$content = '';      // initialize variable for page content

switch ($action) {
case 'mode':
    $view = $actionval;
    break;

case 'savecat':
    $C = new DailyQuote\Category($Request->getInt('id'));
    $C->Save($Request);
    echo COM_refresh(Config::get('admin_url') . '/index.php?categories');
    break;

case 'savequote':
    $Q = DailyQuote\Quote::getInstance($Request->getInt('qid'));
    $message = $Q->Save($Request);
    if (!empty($message)) {
        COM_setMsg($message);
    }
    echo COM_refresh(Config::get('admin_url'));
    break;

case 'savemoderation':
    // Save the quote to the prod table and delete from the queue
    $Q = DailyQuote\Quote::getInstance($Request->getInt('qid'));
    $Request['approved'] = 1;
    $message = $Q->Save($Request);
    if (!empty($message)) {
        // Error saving
        COM_setMsg($message);
    }
    echo COM_refresh(Config::get('admin_url'));
    break;

case 'delquote':
    DailyQuote\Quote::Delete((int)$actionval);
    COM_refresh(Config::get('admin_url'));
    break;

case 'delbutton_x':
    // Handle multiple quote deletion
    $items = $Request->getArray('delitem');
    if (!empty($items)) {
        foreach ($items as $item) {
            DailyQuote\Quote::Delete((int)$item);
        }
    }
    echo COM_refresh(Config::get('admin_url'));
    break;

case 'delcat':
    DailyQuote\Category::Delete($actionval);
    echo COM_refresh(Config::get('admin_url') . '/index.php?categories');
    break;

case 'processbatch':
    $content = DailyQuote\Batch::process();
    // Don't refresh in order to show the import results.
    $view = 'none';
    break;

default:
    $view = $action;
    break;
}

switch ($view) {
case 'edit':
    // "edit" is here so this will work with submit.php
case 'editquote':
    $Q = DailyQuote\Quote::getInstance((int)$actionval);
    $content .= $Q->Edit();
    break;

case 'editcat':
    $C = new DailyQuote\Category((int)$actionval);
    $content .= $C->EditForm();
    break;

//case 'editsubmission':
case 'moderate':
    $Q = DailyQuote\Quote::getInstance((int)$qid);
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

default:
    $view = 'quotes';
    $content .= DailyQuote\Quote::adminList();
    break;
}

$display = DailyQuote\Menu::siteHeader();
$display .= COM_startBlock(
    Config::get('pi_display_name') . ' ver. ' . Config::get('pi_version'),
    '',
    COM_getBlockTemplate('_admin_block', 'header')
);
$display .= DailyQuote\Menu::Admin($view);
$display .= $content;
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display .= DailyQuote\Menu::siteFooter();
echo $display;
