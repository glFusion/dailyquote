<?php
/**
 * Common functions for the DailyQuote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     0.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion functions */
require_once('../lib-common.php');
use DailyQuote\Config;
use DailyQuote\Models\Request;
$Request = Request::getInstance();

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
    global $_TABLES, $_CONF, $LANG_DQ, $_USER, $_IMAGE_TYPE,
        $LANG_ADMIN;

    $Coll = new DailyQuote\Collections\QuoteCollection;
    $Request = DailyQuote\Models\Request::getInstance();

    if (isset($Request['id'])) {
        $Coll->withQuoteId($Request->getInt('id'));
    }
    if (isset($Request['cat'])) {
        $Coll->withCategoryId($Request->getInt('cat'));
    }
    if (isset($Request['quoted'])) {
        $Coll->withAuthorName($Request->getString('quoted'));
    }
    $numquotes = $Coll->getCount();
    $Quotes = $Coll->orderBy($sort, $dir)
                   ->withApproved(true)
                   ->setPage($page)
                   ->createPageLimit()
                   ->getObjects();

    // Display quotes if any to display
    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'dispquotes.thtml');

    // Set up sorting options
    $sortby_opts = array(
        'dt' => $LANG_DQ['date'],
        'quote' => $LANG_DQ['quotation'],
        'quoted' => $LANG_DQ['quoted'],
    );
    $sortby = '';
    foreach ($sortby_opts as $key=>$value) {
        $sel = $sort == $key ? ' selected="selected"' : '';
        $sortby .= "<option value=\"$key\" $sel>$value</option>\n";
    }
    $T->set_var('sortby_opts', $sortby);
    $T->set_var('submit', $LANG_DQ['sort']);
    $T->set_var('pi_url', Config::get('url'));
    if ($dir == 'ASC') {
        $T->set_var('asc_sel', 'selected="selected"');
    } else {
        $T->set_var('desc_sel', 'selected="selected"');
    }

    $T->set_var(
        'google_paging',
        $Coll->getPageNavigation($sort, $dir, $numquotes)
    );

    //  Now get each quote and display it
    $count = 0;
    foreach ($Quotes as $Quote) {
        $T->set_block('page', 'QuoteRow', 'qRow');
        $Cats = DailyQuote\Quote::getCats($Quote->getID());
        $catnames = array();
        foreach ($Cats as $Cat) {
            $catnames[] = COM_createLink(
                $Cat->getName(),
                Config::get('url') . '?cat=' . $Cat->getID()
            );
        }
        $catlist = join(',' , $catnames);

        if ($Quote->getUid() > 1) {
            $username = DQ_linkProfile($Quote->getUid(), $Quote->getUsername());
        } else {
            $username = $LANG_DQ['anonymous'];
        }

        $dt = new Date($Quote->getDate(), $_CONF['timezone']);
        if (isset($Request['query'])) {
            $title = COM_highlightQuery($Quote->getTitle(), $Request->getString('query'));
            $quote = COM_highlightQuery($Quote->getQuote(), $Request->getString('query'));
        } else {
            $title = $Quote->getTitle();
            $quote = $Quote->getQuote();
        }
        $title = htmlspecialchars($title);
        $quote = htmlspecialchars($quote);
        $T->set_var(array(
            'qid'           => $Quote->getID(),
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
            $editlink = '<a href="' . Config::get('admin_url') .
                        '/index.php?edit=quote&id='.$Quote->getID() . '">';
            $icon_url = "{$_CONF['layout_url']}/images/edit.$_IMAGE_TYPE";
            $editlink .= COM_createImage($icon_url, $LANG_ADMIN['edit']);
            $editlink .= '</a>&nbsp;';
            $editlink .= COM_createLink(
                COM_createImage(
                    $_CONF['layout_url'] .
                            "/images/admin/delete.$_IMAGE_TYPE",
                    $LANG_DQ['del_quote'],
                    array(
                        'onclick'=>'return confirm(\'' .
                            $LANG_DQ['del_item_conf'] . '\');',
                        'class'=> 'tooltip',
                    )
                ),
                Config::get('admin_url') . '/index.php?delete=x&xtype=quote&id='.$Quote->getID()
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
    global $_TABLES, $_CONF, $LANG_DQ;

    $retval = '';

    $sql = "SELECT DISTINCT id, name
            FROM {$_TABLES['dailyquote_cat']} c
            WHERE c.enabled='1'
            ORDER BY name ASC";

    $result = DB_query($sql);
    if (!$result){
        $retval = $LANG_DQ['caterror'];
        glFusion\Log\Log::write('system', Log::ERROR, 'An error occured while retrieving list of categories');
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
            'pi_url'    => Config::get('url') . '/index.php',
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
list($action, $actionval) = $Request->getAction($expected);

// Retrieve and sanitize provided parameters
$mode = $Request->getString('mode', 'quotes');
$qid = $Request->getInt('qid');
$cid = $Request->getInt('cid');
$sort = $Request->getString('sort', 'dt');
$dir = $Request->getString('dir', 'ASC');
$page = $Request->getInt('page', 1);

$display = DailyQuote\Menu::siteHeader();
$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
//$T->set_file('page', 'dqheader.thtml');
$T->set_file('page', 'index.thtml');
$T->set_var(array(
    'pi_url' => Config::get('url'),
    'indextitle' => $LANG_DQ['indextitle'],
    'pi_icon' => plugin_geticon_dailyquote(),
    'is_admin' => plugin_ismoderator_dailyquote(),
) );

if (isset($Request['msg'])){
    $msg = "msg" . $Request->getInt('msg');
    $T->set_var('msg', $LANG_DQ[$msg]);
}

// Check access.  Sort of borrowing the glFusion permissions, but not really.
// If anonymous, can they view or add?  If logged in, can they add?
// Viewing is assumend for logged in users.
$access = SEC_inGroup(Config::get('submit_grp')) ? 3 : 2;
$content = '';
switch ($action) {
case 'categories':
    $content .= DailyDQ_listCategories();
    break;

case 'savesubmission':
    $Q = new DailyQuote\Quote();
    $message = $Q->saveSubmission($Request);
    if (empty($message)) {
        $message = sprintf($LANG12[25], Config::PI_NAME);
    }
    COM_setMsg($message);
    COM_refresh(Config::get('url') . '/index.php');
    break;

case 'edit':
    $q_id = $Request->getInt('id');
    $Q = DailyQuote\Quote::getInstance($q_id);
    if ($Q->isNew()) {
        $content .= $Q->Edit('submit');
    } else {
        $content .= $Q->Edit();
    }
    break;

default:
    $T->set_var('can_contrib', $access == 3);
    $content .= DQ_listQuotes($sort, $dir, $page);
    break;
}

$T->set_var('content', $content);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));
$display .= DailyQuote\Menu::siteFooter();
echo $display;
