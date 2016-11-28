<?php
/**
*   Common functions for the DailyQuote plugin
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion functions */
require_once('../lib-common.php');

USES_dailyquote_class_quote();

/**
*   Displays the quotes listing.
*
*   @param  string  $sort   Field to sort by
*   @param  string  $dir    Either 'ASC' or 'DESC'
*   @param  integer $page   Page number to display
*   @return string          HTML for quote listing
*/
function DQ_listQuotes($sort, $dir, $page)
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ, $_USER, $_IMAGE_TYPE,
        $LANG_ADMIN;

    $catid = isset($_REQUEST['cat']) ? (int)$_REQUEST['cat'] : 0;
    if ($dir != 'ASC') $dir = 'DESC';
    if ($page < 1) $page = 1;

    // TODO: this query only gives us the quotes with one category name.
    $sql = "SELECT 
                q.id, quote, quoted, title, source, sourcedate, dt, q.uid
            FROM {$_TABLES['dailyquote_quotes']} q 
            LEFT JOIN {$_TABLES['dailyquote_quoteXcat']} x
                ON q.id = x.qid
            LEFT JOIN {$_TABLES['dailyquote_cat']} c
                ON x.cid = c.id
            WHERE q.enabled = '1' 
            AND (c.enabled = '1' OR c.enabled IS NULL) ";
    if ($catid > 0) {
        $sql .= " AND x.cid = $catid ";
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
    $sql .= $dir == 'ASC' ? ' ASC' : ' DESC';

    // Retrieve results per page setting, set to reasonable default if missing.
    $displim = (int)$_CONF_DQ['indexdisplim'];
    if ($displim <= 0) $displim = 15;
    $startlimit = ($displim * $page) - $displim;
    $sql .= " LIMIT $startlimit, $displim";

    //echo $sql;die;
    $result = DB_query($sql);
    if (!$result) {
        COM_errorLog("An error occured while retrieving list of quotes",1);
        return $LANG_DQ['disperror'];
    }

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
    $T->set_var('pi_url', DQ_URL);
    if ($dir == 'ASC') {
        $T->set_var('asc_sel', 'selected="selected"');
    } else {
        $T->set_var('desc_sel', 'selected="selected"');
    }

    // Calculate page navigation
    $prevpage = $page - 1;
    $nextpage = $page + 1;
    $pagestart = ($page - 1) * $displim;
    $baseurl = DQ_URL . '/index.php?sort=' . $sort . '&dir=' . $dir;
    $numpages = ceil($numquotes / $displim);
    $T->set_var('google_paging', 
            COM_printPageNavigation($baseurl, $page, $numpages));

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

        $dt = new Date($row['dt'], $_CONF['timezone']);
        $T->set_var(array(
            'title'         => htmlspecialchars($row['title']),
            'quote'         => htmlspecialchars($row['quote']),
            'quoted'        => dqQuote::GoogleLink($row['quoted']),
            'catname'       => $catlist,
            'contr'         => $username,
            'datecontr'     => $dt->format($_CONF['shortdate'], true),
        ) );

        if(SEC_hasRights('dailyquote.edit')) {
            $editlink = '<a href="' . DQ_ADMIN_URL . 
                        '/index.php?edit=quote&id='.$row['id'] . '">';
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
                            'class'=> 'gl_mootip',
                        )
                    ),
                    DQ_ADMIN_URL . '/index.php?delete=x&xtype=quote&id='.$row['id']
                );
            $T->set_var('editlink', $editlink);
        }
        $T->parse('qRow', 'QuoteRow', true);
    }

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

    $sql = "SELECT DISTINCT id, name
            FROM {$_TABLES['dailyquote_cat']} c
            WHERE c.enabled='1' 
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

$expected = array(
    'savesubmission',
    'categories', 'quotes',
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

$T->set_var('indextitle', $LANG_DQ['indextitle']);
$indexintro = $LANG_DQ['indexintro'];
if ($access == 3) {
    $indexintro .= ' ' . sprintf($LANG_DQ['indexintro_contrib'], 
            $_CONF['site_url'].'/submit.php?type='.$_CONF_DQ['pi_name']);
}
$T->set_var('indexintro', $indexintro);
$T->set_var('randomquote', DQ_random_quote($qid, $cid));

$content = '';
switch ($action) {
case 'categories':
    $content .= DQ_listCategories();
    break;

case 'savesubmission':
    USES_dailyquote_class_quote();
    $Q = new dqQuote();
    $message = $Q->SaveSubmission($_POST);
    if (empty($message)) $message = sprintf($LANG12[25], $_CONF_DQ['pi_name']);
    LGLIB_storeMessage($message);
    COM_refresh(DQ_URL);
    break;

default:
    $content .= DQ_listQuotes($sort, $dir, $page);
    break;
}

$T->set_var('content', $content);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));
$display .= DQ_siteFooter();
echo $display;

?>
