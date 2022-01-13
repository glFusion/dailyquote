<?php
/**
 * Common functions for the DailyQuote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     0.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion functions */
require_once('../lib-common.php');
use DailyQuote\MO;

/**
 * Displays the quotes listing.
 *
 * @param   string  $sort   Field to sort by
 * @param   string  $dir    Either 'ASC' or 'DESC'
 * @param   integer $page   Page number to display
 * @return  string          HTML for quote listing
 */
function DQ_listQuotes($sort, $dir, $page)
{
    global $_TABLES, $_CONF, $_CONF_DQ, $_USER, $_IMAGE_TYPE,
        $LANG_ADMIN;

    $QL = new DailyQuote\QuoteList;
    if (isset($_REQUEST['id'])) {
        $QL->setFilterID($_REQUEST['id']);
    }
    if (isset($_REQUEST['cat'])) {
        $QL->setFilterCat($_REQUEST['cat']);
    }
    if (isset($_REQUEST['quoted'])) {
        $QL->setFilterAuthor($_REQUEST['quoted']);
    }
    $numquotes = $QL->getPageCount();
    $rows = $QL->setSort($sort, $dir)
        ->setPage($page)
        ->getPageQuotes();

    // Display quotes if any to display
    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'dispquotes.thtml');

    // Set up sorting options
    $sortby_opts = array(
        'dt' => MO::_('Date'),
        'quote' => MO::_('Quotation'),
        'quoted' => MO::_('Person Quoted'),
    );
    $sortby = '';
    foreach ($sortby_opts as $key=>$value) {
        $sel = $sort == $key ? ' selected="selected"' : '';
        $sortby .= "<option value=\"$key\" $sel>$value</option>\n";
    }
    $T->set_var(array(
        'sortby_opts' => $sortby,
        'submit' => MO::_('Sort'),
        'pi_url' => DQ_URL,
        'lang_sortby' => MO::_('Sort Quotations by'),
        'lang_ascending' => MO::_('Ascending'),
        'lang_descending' => MO::_('Descending'),
        'lang_sort' => MO::_('Sort'),
        'lang_edit' => MO::_('Edit'),
        'lang_del_item_conf' => MO::_('Do you really want to delete this item?'),
        'lang_delete' => MO::_('Delete'),
        'lang_subm_by' => MO::_('Submitted By'),
    ) );
    if ($dir == 'ASC') {
        $T->set_var('asc_sel', 'selected="selected"');
    } else {
        $T->set_var('desc_sel', 'selected="selected"');
    }

    $T->set_var(
        'google_paging',
        $QL->getPageNavigation($numquotes)
    );

    //  Now get each quote and display it
    $count = 0;
    foreach ($rows as $Quote) {
        $T->set_block('page', 'QuoteRow', 'qRow');
        $Cats = DailyQuote\Quote::getCats($Quote->getID());
        $catnames = array();
        foreach ($Cats as $Cat) {
            $catnames[] = COM_createLink(
                $Cat->getName(),
                DQ_URL . '?cat=' . $Cat->getID()
            );
        }
        $catlist = join(',' , $catnames);

        if ($Quote->getUid() > 1) {
            $username = DQ_linkProfile($Quote->getUid(), $Quote->getUsername());
        } else {
            $username = MO::_('Anonymous');
        }

        $dt = new Date($Quote->getDate(), $_CONF['timezone']);
        if (isset($_REQUEST['query'])) {
            $title = COM_highlightQuery($Quote->getTitle(), $_REQUEST['query']);
            $quote = COM_highlightQuery($Quote->getQuote(), $_REQUEST['query']);
        } else {
            $title = $Quote->getTitle();
            $quote = $Quote->getQuote();
        }
        $T->set_var(array(
            'quote_id'      => $Quote->getID(),
            'title'         => $title,
            'quote'         => $quote,
            'quoted'        => DailyQuote\Quote::GoogleLink($Quote->getQuoted()),
            'catname'       => $catlist,
            'contr'         => $username,
            'source'        => htmlspecialchars($Quote->getSource()),
            'sourcedate'    => htmlspecialchars($Quote->getSourceDate()),
            'datecontr'     => $dt->format($_CONF['shortdate'], true),
            'adblock'       => PLG_displayAdBlock('dailyquote_list', ++$count),
            'can_edit'      => SEC_hasRights('dailyquote.edit') ? true : false,
        ) );

        if(SEC_hasRights('dailyquote.edit')) {
            $editlink = '<a href="' . DQ_ADMIN_URL .
                        '/index.php?edit=quote&id='.$Quote->getID() . '">';
            $icon_url = "{$_CONF['layout_url']}/images/edit.$_IMAGE_TYPE";
            $editlink .= COM_createImage($icon_url, $LANG_ADMIN['edit']);
            $editlink .= '</a>&nbsp;';
            $editlink .= COM_createLink(
                COM_createImage(
                    $_CONF['layout_url'] .
                            "/images/admin/delete.$_IMAGE_TYPE",
                    MO::_('Delete Quote'),
                    array(
                        'onclick'=>'return confirm(\'' .
                            MO::_('Do you really want to delete this item?') .
                            '\');',
                        'class'=> 'tooltip',
                    )
                ),
                DQ_ADMIN_URL . '/index.php?delete=x&xtype=quote&id='.$Quote->getID()
            );
            $T->set_var('editlink', $editlink);
        }
        $T->parse('qRow', 'QuoteRow', true);
    }
    $T->parse('output','page');
    return $T->finish($T->get_var('output'));
}


/**
 * Display a list of categories with links.
 *
 * @return  string  HTML Category List
 */
function DQ_listCategories()
{
    global $_TABLES, $_CONF;

    $retval = '';

    $sql = "SELECT DISTINCT id, name
            FROM {$_TABLES['dailyquote_cat']} c
            WHERE c.enabled='1'
            ORDER BY name ASC";

    $result = DB_query($sql);
    if (!$result){
        $retval = MO::_('An error occurred while retrieving category list.');
        COM_errorLog($retval, 1);
        return $retval;
    }

    // Display cats if any to display
    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'dispcats.thtml');

    // display horizontal rows -- 3 cats per row
    $i = 0;
    $col = 3;
    while ($row = DB_fetchArray($result)) {
        $T->set_block('page', 'CatRow', 'cRow');
        $T->set_var(array(
            'pi_url'    => DQ_URL . '/index.php',
            'cat_id'    => $row['id'],
            'dispcat'   => $row['name'],
            'cell_width' => (int)(100 / $col),
        ) );

        // Determine if it's time for a new row
        $i++;
        if ($i % $col === 0) {
            $T->set_var('newrow', 'true');
        }
        $T->parse('cRow', 'CatRow', true);
    }

    if ($i > 0) {
        $T->parse('output', 'page');
        $retval .= $T->finish($T->get_var('output'));
    }

    return $retval;
}

$action = '';
$actionval = '';
$expected = array(
    'savesubmission',
    'categories', 'quotes', 'edit',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
    	$action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

// Retrieve and sanitize provided parameters
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'quotes';
$qid = isset($_REQUEST['qid']) ? COM_sanitizeID($_REQUEST['qid']) : '';
$cid = isset($_REQUEST['cid']) ? (int)$_REQUEST['cid'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'dt';
$dir = isset($_GET['dir']) ? $_GET['dir'] : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$display = DQ_siteHeader();
$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
//$T->set_file('page', 'dqheader.thtml');
$T->set_file('page', 'index.thtml');
$T->set_var('pi_url', DQ_URL);

if (isset($_GET['msg'])){
    $msg = "msg" . $_GET['msg'];
    $T->set_var('msg', $LANG_DQ[$msg]);
}

// Check access.  Sort of borrowing the glFusion permissions, but not really.
// If anonymous, can they view or add?  If logged in, can they add?
// Viewing is assumend for logged in users.
$access = 2;
if (COM_isAnonUser()) {
    if ($_CONF_DQ['anonadd'] == 1) {
        $access = 3;
    }
} elseif ($_CONF_DQ['loginadd'] == 1) {
    $access = 3;
}

$T->set_var('indextitle', MO::_('Quote of the Day'));
$indexintro = MO::_('History is full of stories, rants, perspectives, truths, lies, facts, details, opinions, ordinances, etc. The stories told by the men and women who were there, as well as by those who were not, and their comments are items for display in the archives of humankind.');
if ($access == 3) {
    $indexintro .= ' ' . sprintf(
        MO::_('Visit the museum and <a href="%s">contribute</a>.'),
        DQ_URL . '/index.php?edit'
    );
}

$content = '';
switch ($action) {
case 'categories':
    $content .= DQ_listCategories();
    break;

case 'savesubmission':
    $Q = new DailyQuote\Quote();
    $message = $Q->SaveSubmission($_POST);
    if (empty($message)) $message = sprintf($LANG12[25], $_CONF_DQ['pi_name']);
    LGLIB_storeMessage($message);
    COM_refresh(DQ_URL);
    break;

case 'edit':
    $q_id = isset($_GET['id']) ? $_GET['id'] : '';
    $Q = new DailyQuote\Quote($q_id);
    if ($q_id == '' || !$Q->isNew()) {
        $content .= $Q->Edit();
    }
    break;

default:
    $T->set_var('indexintro', $indexintro);
    $T->set_var('randomquote', DQ_random_quote($qid, $cid));
    $content .= DQ_listQuotes($sort, $dir, $page);
    break;
}

$T->set_var('content', $content);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));
$display .= DQ_siteFooter();
echo $display;

?>
