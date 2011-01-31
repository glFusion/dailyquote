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
require_once('../lib-common.php');

USES_dailyquote_class_quote();
USES_dailyquote_functions();

// Clean $_POST and $_GET, in case magic_quotes_gpc is set
$_POST = DQ_stripslashes($_POST);
$_GET = DQ_stripslashes($_GET);

// Retrieve access settings
$anonview = $_CONF_DQ['default_permissions'][3];
if ($anonview < 2 && $_USER['uid'] < 2) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_DQ['access_denied']);
    $display .= $LANG_DQ['access_denied_msg1'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}


/**
*   Displays the quotes listing.
*
*   @param  string  $sort   Field to sort by
*   @param  string  $asc    Either 'ASC' or 'DESC'
*   @param  integer $page   Page number to display
*   @return string          HTML for quote listing
*/
function DQ_listQuotes($sort, $asc, $page)
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ, $_USER, $_IMAGE_TYPE,
        $LANG_ADMIN;

    $catid = isset($_REQUEST['cat']) ? (int)$_REQUEST['cat'] : 0;
    if ($asc != 'ASC') $asc = 'DESC';
    if ($page < 1) $page = 1;

    // TODO: this query only gives us the quotes with one category name.
    $sql = "SELECT 
                q.id, quote, quoted, title, source, sourcedate, dt, q.uid
            FROM 
                {$_TABLES['dailyquote_quotes']} q 
            LEFT JOIN {$_TABLES['dailyquote_quoteXcat']} l
                ON q.id = l.qid
            LEFT JOIN {$_TABLES['dailyquote_cat']} c
                ON l.cid = c.id
            WHERE 
                q.enabled = '1' 
            AND 
                (c.enabled = '1' OR c.enabled IS NULL) ";
    if ($catid > 0) {
        $sql .= " AND l.cid = $catid ";
    }

    // Just get the total possible entries, to calculage page navigation
    $result = DB_query($sql);
    $numquotes = DB_numRows($result);

    switch ($sort) {
    case 'quote':
    case 'quoted':
    case 'dt':
        $sorted = $sort;
        break;
    default:
        $sorted = 'dt';
        break;
    }
    $sql .= " GROUP BY q.id ORDER BY $sorted ";

    $sql .= $asc == 'ASC' ? ' ASC' : ' DESC';

    // Retrieve results per page setting, set to reasonable default if missing.
    $displim = (int)$_CONF_DQ['indexdisplim'];
    if ($displim < 5) $displim = 15;
    $startlimit = ($displim * $page) - $displim;
    $sql .= " LIMIT $startlimit, $displim";

    //echo $sql;die;
    $result = DB_query($sql);
    if (!$result) {
        $retval = $LANG_DQ['disperror'];
        COM_errorLog("An error occured while retrieving list of quotes",1);
        return '';
    }

    // Display quotes if any to display
    $T = new Template(DQ_PI_PATH . '/templates');
    $T->set_file('page', 'dispquotes.thtml');

    // Set up sorting options
    $sortby_opts = array('dt' => $LANG_DQ['date'],
                    'quote' => $LANG_DQ['quotation'],
                    'quoted' => $LANG_DQ['quoted'],
                );
    $sortby = '';
    foreach ($sortby_opts as $key=>$value) {
        $sel = $sort == $key ? ' selected="selected"' : '';
        $sortby .= '<option value="' . $key . $sel . '">' . $value . "</option>\n";
    }
    $T->set_var('sortby_opts', $sortby);
    $T ->set_var('submit', $LANG_DQ['sort']);
    if ($dir == 'ASC') {
        $T->set_var('asc_sel', 'selected="selected"');
    } else {
        $T->set_var('desc_sel', 'selected="selected"');
    }

    // Calculate page navigation
    $prevpage = $page - 1;
    $nextpage = $page + 1;
    $pagestart = ($page - 1) * $displim;
    $baseurl = DQ_URL . '/index.php?sort=' . $sort . '&asc=' . $asc;
    $numpages = ceil($numquotes / $displim);
    //$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    //$T->set_file('page', 'pagenav.thtml');
    $T->set_var('google_paging', 
            COM_printPageNavigation($baseurl, $page, $numpages));
    //$T->parse('output','page');
    //$google_pagenav = $T->finish($T->get_var('output'));
    //$retval .= $google_pagenav;

    //  Now get each quote and display it
    while ($row = DB_fetchArray($result)) {
        //$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        //$T->set_file('page', 'singlequote.thtml');
        $T->set_block('page', 'QuoteRow', 'qRow');

        $catres = DB_query("SELECT 
                c.id AS catid, c.name AS catname
            FROM {$_TABLES['dailyquote_quoteXcat']} l 
            LEFT JOIN {$_TABLES['dailyquote_cat']} c
                ON l.cid = c.id 
            WHERE
                l.qid = '{$row['id']}'");
        $catnames = array();
        while ($cats = DB_fetcharray($catres, false)) {
            $catnames[] = COM_createLink($cats['catname'],
                    DQ_URL . '?cat=' . $cats['catid']);
        }
        $catlist = join(',' , $catnames);
 
        /*if (!empty($row['catid']) && !empty($row['catname'])) {
            $T->set_var(array(
                'catid'     => $row['catid'],
                'catname'   => $row['catname'],
            ) );
        }*/
        if (!empty($row['source'])){
            $T->set_var('source', '&nbsp;--&nbsp;' . htmlspecialchars($row['source']));
        }
        if (!empty($row['sourcedate'])){
            $T->set_var('sourcedate', '&nbsp;&nbsp;(' . 
                htmlspecialchars($row['sourcedate']) . ')');
        }

        $contr = DB_query("SELECT uid, username 
                            FROM {$_TABLES['users']} 
                            WHERE uid={$row['uid']}");
        if ($contr) {
            list($uid, $username) = DB_fetchArray($contr);
            $username = DQ_linkProfile($uid, $username);
        } else {
            $username = $LANG_DQ['anonymous'];
        }

        $T->set_var(array(
            'title'         => htmlspecialchars($row['title']),
            'quote'         => htmlspecialchars($row['quote']),
            'quoted'        => DailyQuote::GoogleLink($row['quoted']),
            'catname'       => $catlist,
            'contr'         => $username,
            'datecontr'     => strftime($_CONF['shortdate'], $row['dt']),
        ) );

        if(SEC_hasRights('dailyquote.edit')) {
            $editlink = '<a href="' . DQ_ADMIN_URL . 
                        '/index.php?edit=quote&id='.$row['id'] . '">';
            $icon_url = "{$_CONF['layout_url']}/images/edit.$_IMAGE_TYPE";
            $editlink .= COM_createImage($icon_url, $LANG_ADMIN['edit']);
            $editlink .= '</a>&nbsp;';
            $editlink .= 
                COM_createLink(
                    COM_createImage(
                        $_CONF['layout_url'] . 
                                "/images/admin/delete.$_IMAGE_TYPE", 
                        $LANG_DQ['del_quote'],
                        array(
                            'onclick'=>'return confirm(\'' .
                                $LANG_DQ['del_item_conf'] . '\');',
                            'class'=> 'gl_mootip',
                        )
                    ),
                    DQ_ADMIN_URL . '/index.php?delete=x&xtype=quote&id='.$row['id']
                );
            $T->set_var('editlink', $editlink);
        }
        $T->parse('qRow', 'QuoteRow', true);
    }

    //$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    //$T->set_file('page', 'dispquotesfooter.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));
    return $retval;
}


/**
*   Display a list of categories with links
*/
function DQ_listCategories()
{
    global $_TABLES, $_CONF, $LANG_DQ;

    $retval = '';

    $sql = "SELECT DISTINCT 
                id, name
            FROM 
                {$_TABLES['dailyquote_cat']} c
            WHERE 
                c.enabled='1' 
            ORDER BY name ASC";

    $result = DB_query($sql);
    if (!$result){
        $retval = $LANG_DQ['caterror'];
        COM_errorLog("An error occured while retrieving list of categories",1);
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


/**
*   Displays a menu of sort by options for the display page
*   @return string  HTML for sort selection form
*/
function X_DQ_menuSort($sort='', $dir='')
{
    global $_CONF, $LANG_DQ, $_CONF_DQ;
    
    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'sortnav.thtml');
    $T ->set_var('site_url', $_CONF['site_url']);
    $T ->set_var('submit', $LANG_DQ['sort']);

    $sortby_opts = array('dt' => $LANG_DQ['date'],
                    'quote' => $LANG_DQ['quotation'],
                    'quoted' => $LANG_DQ['quoted'],
                );
    $sortby = '';
    foreach ($sortby_opts as $key=>$value) {
        $sel = $sort == $key ? ' selected="selected"' : '';
        $sortby .= '<option value="' . $key . $sel . '">' . $value . "</option>\n";
    }
    $T->set_var('sortby_opts', $sortby);
    if ($dir == 'ASC')
        $T->set_var('asc_sel', 'selected="selected"');
    else
        $T->set_var('desc_sel', 'selected="selected"');

    $T->parse('output','page');
    $retval = $T->finish($T->get_var('output'));
    return $retval;
}



/* 
* MAIN
*/

// Retrieve and sanitize provided parameters
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'quotes';
$qid = isset($_REQUEST['qid']) ? COM_sanitizeID($_REQUEST['qid']) : '';
$cid = isset($_REQUEST['cid']) ? (int)$_REQUEST['cid'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'dt';
$asc = isset($_GET['asc']) ? $_GET['asc'] : 'ASC';
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
    if ($_CONF_DQ['anonview'] == 0) {
        echo COM_refresh($_CONF['site_url']);
        exit;
    } elseif ($_CONF_DQ['anonadd'] == 1) {
        $access = 3;
    }
} elseif ($_CONF_DQ['loginadd'] == 1) {
    $access = 3;
}

$T->set_var('indextitle', $LANG_DQ['indextitle']);
$indexintro = $LANG_DQ['indexintro'];
if ($access == 3) {
    $indexintro .= ' ' . sprintf($LANG_DQ['indexintro_contrib'], 
            $_CONF['site_url'].'/submit.php?type='.$_CONF_DQ['pi_name']);
}
$T->set_var('indexintro', $indexintro);
$T->set_var('randomquote', DQ_random_quote($qid, $cid));

$content = '';
switch ($mode) {
case 'categories':
    $content .= DQ_listCategories();
    break;
default:
    //$content .= DQ_menuSort($sort, $asc);
    $content .= DQ_listQuotes($sort, $asc, $page);
    break;
}

//display the sort by menu
//$display .= sort_by_menu();

//display quote listing by date desc as default
//$display .= display_quote($sort, $asc, $page);
$T->set_var('content', $content);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

/*$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));*/
$display .= DQ_siteFooter();
echo $display;

?>
