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
USES_lib_admin();

/**
 * Create the administrators' menu.
 *
 * @param   string  $mode   Selected view
 * @return  string  HTML for the admin menu
 */
function DQ_adminMenu($mode='')
{
    global $_CONF, $LANG_ADMIN, $LANG_DQ;

    $menu_arr = array();

    if (isset($LANG_DQ['hlp_admin_' . $mode])) {
        $hlp_text = $LANG_DQ['hlp_admin_' . $mode];
    } else {
        $hlp_text = $LANG_DQ['hlp_admin_dailyquote'];
    }

    if ($mode == 'quotes') {
        $menu_arr[] = array('text' => $LANG_DQ['newquote'],
                'url' => DQ_ADMIN_URL . '/index.php?editquote=x');
    } else {
        $menu_arr[] = array('text' => $LANG_DQ['glsearchlabel'],
                'url' => DQ_ADMIN_URL . '/index.php?quotes=x');
    }

    if ($mode == 'categories') {
        $menu_arr[] = array('text' => 'New Category',
                'url' => DQ_ADMIN_URL . '/index.php?editcat=x');
    } else {
        $menu_arr[] = array('text' => $LANG_DQ['manage_cats'],
                'url' => DQ_ADMIN_URL . '/index.php?categories=x');
    }

    $menu_arr[] = array('url' => DQ_ADMIN_URL . '/index.php?batchform=x',
              'text' => $LANG_DQ['batchaddlink']);
    $menu_arr[] = array('url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home']);

    $retval = ADMIN_createMenu($menu_arr, $hlp_text, plugin_geticon_dailyquote());

    return $retval;
}


/**
 * Create an admin list of quotes.
 *
 * @return  string  HTML for list
 */
function DQ_adminList()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
    global $_CONF_DQ, $LANG_DQ;

    $retval = '';

    $header_arr = array(      # display 'text' and use table field 'field'
        array(
            'field' => 'edit',
            'text' => $LANG_ADMIN['edit'],
            'sort' => false,
        ),
        array(
            'field' => 'enabled',
            'text' => $LANG_DQ['enabled'],
            'sort' => false,
        ),
        array(
            'field' => 'id',
            'text' => 'Quote ID',
            'sort' => true,
        ),
        array(
            'field' => 'dt',
            'text' => $LANG_DQ['date'],
            'sort' => true,
        ),
        array(
            'field' => 'quoted',
            'text' => $LANG_DQ['quoted'],
            'sort' => true,
        ),
        array(
            'field' => 'title',
            'text' => $LANG_DQ['title'],
            'sort' => true,
        ),
        array(
            'field' => 'quote',
            'text' => $LANG_DQ['quote'],
            'sort' => true,
        ),
        array(
            'field' => 'delete',
            'text' => $LANG_ADMIN['delete'],
            'sort' => false,
        ),
    );

    $defsort_arr = array('field' => 'dt', 'direction' => 'desc');
    $text_arr = array(
        'has_extras' => true,
        'form_url' => "{$_CONF['site_admin_url']}/plugins/{$_CONF_DQ['pi_name']}/index.php?type=quote"
    );

    $options = array('chkdelete' => 'true', 'chkfield' => 'id');

    $query_arr = array('table' => 'dailyquote',
        'sql' => "SELECT * FROM {$_TABLES['dailyquote_quotes']} ",
        'query_fields' => array('title', 'quotes', 'quoted'),
        'default_filter' => 'WHERE 1=1'
    );
    $form_arr = array();
    return ADMIN_list('dailyquote', 'DQ_admin_getListField', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '',
                    $options, $form_arr);
}


/**
 * Display a single formatted field in the admin quote list.
 *
 * @param   string  $fieldname  Name of the field
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Name->Value array of all fields
 * @param   array   $icon_arr   System icon array
 * @return  string              HTML for the field display
 */
function DQ_admin_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_DQ, $_CONF_DQ, $LANG_ADMIN;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        if ($_CONF_DQ['_is_uikit']) {
            $retval .= COM_createLink('',
                DQ_ADMIN_URL . "/index.php?editquote=x&amp;id={$A['id']}",
                array('class' => 'uk-icon uk-icon-edit')
            );
        } else {
            $retval .= COM_createLink(
                $icon_arr['edit'],
                DQ_ADMIN_URL . "/index.php?editquote=x&amp;id={$A['id']}"
            );
        }
        break;

    case 'enabled':
        $value = $fieldvalue == 1 ? 1 : 0;
        $chk = $fieldvalue == 1 ? ' checked="checked" ' : '';
        $retval .= '<input type="checkbox" id="togena' . $A['id'] . '"' .
            $chk . 'onclick=\'DQ_toggleEnabled(this, "' . $A['id'] .
                '", "quote");\' />';
        break;

    case 'title':
    case 'quote':
        $max_len = 40;
        $ellipses = strlen($fieldvalue) > $max_len ? ' ...' : '';
        $retval = substr(stripslashes($fieldvalue), 0, $max_len) . $ellipses;
        break;

    case 'dt';
        $dt = new Date($A['dt'], $_CONF['timezone']);
        $retval = $dt->format($_CONF['shortdate'], true);
        break;

    case 'delete':
        if ($_CONF_DQ['_is_uikit']) {
            $retval = COM_createLink('',
                DQ_ADMIN_URL . '/index.php?delquote=x&id=' . $A['id'],
                array(
                    'class' => 'uk-icon uk-icon-trash dq-icon-danger',
                    'onclick' => 'return confirm(\'' . $LANG_DQ['confirm_delitem'] . '\');',
                    'title' => $LANG_ADMIN['delete'],
                )
            );
        } else {
             $retval = COM_createLink('<img src='. $_CONF['layout_url'] .
                '/images/admin/delete.png>',
                DQ_ADMIN_URL . "/index.php?delquote=x&amp;id={$A['id']}");
        }
        break;

    default:
        $retval = strip_tags($fieldvalue);
        break;
    }

    return $retval;
}



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

$item_id = isset($_REQUEST['id']) ? COM_sanitizeID($_REQUEST['id'], false) : '';

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
    $Q = new DailyQuote\Quote($item_id);
    $message = $Q->Save($_POST, 'dailyquote_quotes');
    if (!empty($message)) {
        LGLIB_storeMessage($message);
    }
    echo COM_refresh(DQ_ADMIN_URL);
    break;

case 'savemoderation':
    // Save the quote to the prod table and delete from the queue
    $Q = new DailyQuote\Quote($item_id);
    $message = $Q->Save($_POST, 'dailyquote_quotes');
    if (!empty($message)) {
        // Error saving
        LGLIB_storeMessage($message);
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
    USES_dailyquote_batch();
    $content = DQ_process_batch();
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
    $Q = new DailyQuote\Quote($item_id);
    $content .= $Q->Edit();
    break;

case 'editcat':
    $C = new DailyQuote\Category($item_id);
    $content .= $C->EditForm();
    break;

//case 'editsubmission':
case 'moderate':
    $Q = new DailyQuote\Quote($item_id, 'submission');
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
    USES_dailyquote_batch();
    $content .= DQ_batch_form();
    break;

case 'none':
    // Used if the action sets the entire page content
    break;

case 'quotes':
default:
    $page = 'quotes';
    $content .= DQ_adminList();
    break;
}

$display = COM_siteHeader();
$display .= COM_startBlock($_CONF_DQ['pi_display_name'] . ' ver. ' . $_CONF_DQ['pi_version'], '',
                COM_getBlockTemplate('_admin_block', 'header'));
$display .= DQ_adminMenu($page);
$display .= $content;
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display .= COM_siteFooter();

echo $display;

?>
