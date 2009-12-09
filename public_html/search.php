<?php
//  $Id$
/**
*   Search function for the DailyQuote plugin
*
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

// Check user has rights to access this page
if (($anonview == '0') && ($_USER['uid'] < 2)) {
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

//displays the google-like page nav
function display_pagenav($categ, $keywords, $keytype, $type, $datestart, $dateend, $contr, $qid, $show, $stat, $catget, $mode, $page, $numrows)
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;

    //print page nav here
    //$query1 = DB_query ("SELECT COUNT(*) AS numquotes FROM {$_TABLES['dailyquote_quotes']} WHERE Status='1'");
    //list($numquotes) = DB_fetchArray($query1);
    //$query2 = DB_query("SELECT searchdisplim AS displim FROM {$_TABLES['dailyquote_settings']}");
    //list($displim) = DB_fetchArray($query2);
    $displim = $_CONF_DQ['indexdisplim'];
    if ($displim < 1) $displim = 15;

    if ($numrows > $_CONF_DQ['displim']) {
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'pagenav.thtml');
        $prevpage = $page - 1;
        $nextpage = $page + 1;
        $pagestart = ($page - 1) * $displim;
        if ((!empty($qid)) && (!empty($show))){
            $qidvar = "qid=".$qid."&amp;show=".$show;
        }
        elseif (!empty($stat)){
            $statvar = "stat=".$stat;
        }
        elseif (!empty($catget)){
            $catvar = "cat=".$catget;
        }
        if (!empty($categ)){
            $categvar = "categ=".$categ.'&amp;';
        }
        if (!empty($keywords)){
            $keywordsvar = "keywords=".$keywords.'&amp;';
        }
        if (!empty($keytype)){
            $keytypevar = "keytype=".$keytype.'&amp;';
        }
        if (!empty($type)){
            $typevar = "type=".$type.'&amp;';
        }
        /*if (!empty($datestart)){
            $datestartvar = "datestart=".$datestart.'&amp;';
        }
        if (!empty($dateend)){
            $dateendvar = "dateend=".$dateend.'&amp;';
        }*/
        if (!empty($contr)){
            $contrvar = "contr=".$contr.'&amp;';
        }
        $baseurl = $_CONF['site_url'] . '/dailyquote/search.php?' . $qidvar . $statvar . $catvar . $categvar;
        $baseurl .= $keywordsvar . $keytypevar . $typevar . $datestartvar . $dateendvar . $contrvar;
        $numpages = ceil ($numrows / $displim);
        $T->set_var ('google_paging',
                COM_printPageNavigation ($baseurl, $page, $numpages));
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
    }
    return $retval;
}

//displays the search results
function search_results($categ, $keywords, $keytype, $type, $datestart, $dateend, $contr, $qid, $show, $stat, $catget, $mode, $page)
{
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ, $LANG_ADMIN, $_IMAGE_TYPE;

    USES_dailyquote_functions();

    $sql = "SELECT DISTINCT 
            id, quote, quoted, title, source, sourcedate, dt, q.uid, username
        FROM 
            {$_TABLES['dailyquote_quotes']} q, 
            {$_TABLES['dailyquote_quoteXcat']} l, 
            {$_TABLES['users']} u";
    $catwh = '';
    if (!empty($categ)){
        $cat = slashctrl($categ);
        $catID = DB_getItem($_TABLES['dailyquote_cat'], 'id', "name='$cat'");
        //$result = DB_query("SELECT id 
        //    FROM {$_TABLES['dailyquote_cat']} WHERE Name='$cat'");
        //list($catID) = DB_fetchArray($result);
        $catwh = " AND l.CID='$catID'";
    }
    if (!empty($catget)) {
        $catwh = " AND l.cid='$catget' AND l.qid=id";
    }
    $sql .= " WHERE (
            q.enabled='1' 
        AND 
            q.uid=u.uid 
        AND 
            l.qid=id 
        $catwh )";

    if (isset($keywords)){
        $query = trim($keywords);
    }
    if (isset($_POST['submit']) && empty($query)) {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['searchmsg1'] . "</p>";
        return $retval;
    }

    if (isset($keytype)){
        if ($keytype == "phrase"){//exact phrase as query
            $mysearchterm = slashctrl(trim ($query));
            $q = "(quote like '%$mysearchterm%')";
            $pq = "(quoted like '%$mysearchterm%')";
            $c = "(username like '%$mysearchterm%')";
            $s = "(source like '%mysearchterm%')";
            $t = "(title like '%mysearchterm%')";
            if (!empty($type)){
                if ($type == "1"){//search within quotations
                    $sql .= " AND $q";
                } elseif ($type == "2"){//search within persons quoted
                    $sql .= " AND $pq";
                } elseif ($type == "4"){//search within contributor
                    $sql .= " AND $c";
                } elseif ($type == "5"){//search within source
                    $tmp .= " $s AND";
                } elseif ($type == "6"){//search within title
                    $tmp .= " $t AND";
                } else/*if ($type == "7")*/{//search in all of the above
                    $sql .= " AND ($q OR $pq OR $c OR $s OR $t)";
                }
            } else{//search in all of the above
                $sql .= " AND ($q OR $pq OR $c OR $s OR $t)";
            }

        } elseif ($keytype == "all"){//all the words as query
            $mywords = explode(' ', $query);
            $sql .= ' AND (';
            $tmp = '';
            foreach ($mywords AS $mysearchterm) {
                $mysearchterm = slashctrl (trim ($mysearchterm));
                if (!$mysearchterm == ""){
                    $q = "(quote like '%$mysearchterm%')";
                    $pq = "(quoted like '%$mysearchterm%')";
                    $c = "(username like '%$mysearchterm%')";
                    $s = "(source like '%mysearchterm%')";
                    $t = "(title like '%mysearchterm%')";
                    if (!empty($type)){
                        if ($type == "1"){//search within quotations
                            $tmp .= " $q AND";
                        } elseif ($type == "2"){//search within persons quoted
                            $tmp .= " $pq AND";
                        } elseif ($type == "4"){//search within contributor
                            $tmp .= " $c AND";
                        } elseif ($type == "5"){//search within source
                            $tmp .= " $s AND";
                        } elseif ($type == "6"){//search within title
                            $tmp .= " $t AND";
                        } else/*if ($type == "7")*/{//search in all of the above
                            $tmp .= " ($q OR $pq OR $c OR $s OR $t) AND";
                        }
                    } else{//search in all of the above
                        $tmp .= " ($q OR $pq OR $c OR $s OR $t) AND";
                    }
                }
            }
            $tmp = substr($tmp, 0, strlen($tmp) - 4);
            $sql .= $tmp . ")";

        } else {//any of the words as query
            $mywords = explode(' ', $query);
            $sql .= ' AND (';
            $tmp = '';
            foreach ($mywords AS $mysearchterm) {
                $mysearchterm = slashctrl (trim ($mysearchterm));
                if (!$mysearchterm == ""){
                    $q = "(quote like '%$mysearchterm%')";
                    $pq = "(quoted like '%$mysearchterm%')";
                    $c = "(username like '%$mysearchterm%')";
                    $s = "(source like '%mysearchterm%')";
                    $t = "(title like '%mysearchterm%')";
                    if (!empty($type)){
                        if ($type == "1"){//search within quotations
                            $tmp .= " $q OR";
                        } elseif ($type == "2"){//search within persons quoted
                            $tmp .= " $pq OR";
                        } elseif ($type == "4"){//search within contributor
                            $tmp .= " $c OR";
                        } elseif ($type == "5"){//search within source
                            $tmp .= " $s OR";
                        } elseif ($type == "6"){//search within title
                            $tmp .= " $t OR";
                        } else/*if ($type == "7")*/{//search in all of the above
                            $tmp .= " ($q OR $pq OR $c OR $s OR $t) OR";
                        }
                    } else{//search in all of the above
                        $tmp .= " ($q OR $pq OR $c OR $s OR $t) OR";
                    }
                }
            }
            $tmp = substr($tmp, 0, strlen($tmp) - 3);
            $sql .= $tmp . ")";
        }
    }

    /*if (!empty($datestart)){
        $sql .= " AND q.dt >= '$start'";
    }
    if (!empty($dateend)){
        $sql .= " AND q.dt < '$end'";
    }*/

    if (!empty($contr)){
        $contr = slashctrl($contr);
        //$result = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE username='$subm'");
        //list($uid) = DB_fetchArray($result);
        $sql .= " AND username='$contr'";
    }

    //  this GET stuff comes from the GL search results urls and displays 
    //  here accordingly
    if (isset($qid) && isset($show)) {
        if ($show == "quote"){
            //shows a single quote on the search page
            $sql .= " AND id='$qid'";
        } elseif ($show == "quoted") {
            //shows all quotes by whomever was quoted
            $result = DB_query("SELECT quoted From {$_TABLES['dailyquote_quotes']} WHERE id=$qid");
            list($quoted) = DB_fetchArray($result);
            $quoted = slashctrl($quoted);
            $sql .= " AND quoted='$quoted'";
        } elseif ($show == "name") {
            //shows all quotes submitted by whoever submitted $qid
            $result = DB_query("SELECT uid FROM {$_TABLES['dailyquote_quotes']} WHERE id='$qid'");
            list($uid) = DB_fetchArray($result);
            $sql .= " AND q.uid='$uid'";
        }
    }
    elseif (isset($stat)) {
        // this comes from the GL stats page
        $stat = slashctrl($stat);
        $sql .= " AND quoted='$stat'";
    }

    $numrows = DB_query($sql);

    // Retrieve results per page setting
    //if(!$query = DB_query("SELECT searchdisplim AS displim FROM {$_TABLES['dailyquote_settings']}")){
      //  $displim = 10;
    //} else {
        //list( $displim) = DB_fetchArray($query);
    $displim = $_CONF_DQ['displim'];
    if (empty($displim)) $displim = 10;
        $limit = ($displim * $page) - $displim;
        $sql .= " LIMIT $limit, $displim";
    //}

    $result = DB_query($sql);
    if (!$result){
        $retval .= $LANG_DQ['disperror'];
        COM_errorLog("An error occured while retrieving list of quotes",1);
    } else {
        //display quotes if any to display
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'dispquotesheader.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        $numrows = DB_numRows($numrows);
        if ($numrows > 0 ) {
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'numresults.thtml');
            $T->set_var('numresults', $numrows);
            $T->set_var('numresultstxt', $LANG_DQ['numresultstxt']);
            if(isset($catget)) {
                if(!$catgetsql = DB_query("SELECT name FROM {$_TABLES['dailyquote_cat']} WHERE id=$catget")) {
                } else {
                    list($cathead) = DB_fetchArray($catgetsql);
                    $T->set_var('cathead', sprintf($LANG_DQ['cathead'],$cathead));
                }
            }
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
            $retval .= display_pagenav($categ, $keywords, $keytype, $type, $datestart, $dateend, $contr, $qid, $show, $stat, $catget, $mode, $page, $numrows);
            while ($row = DB_fetchArray($result)){
                $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
                $T->set_file('page', 'singlequote.thtml');
                if (!empty($row['Title'])){
                    $title = '<p style="text-align: left; font-weight: bold; text-decoration: underline;">';
                    $title .= $row['title'];
                    $title .= '</p>';
                    $T->set_var('title', $title);
                }
                $T->set_var('quote', $row['quote']);
                $T->set_var('quoted', DailyQuote::GoogleLink($row['quoted']));
                if (!empty($row['source'])){
                    $T->set_var('source', '&nbsp;--&nbsp;' . $row['source']);
                }
                if (!empty($row['sourcedate'])){
                    $T->set_var('sourcedate', '&nbsp;&nbsp;(' . $row['sourcedate'] . ')');
                }
                $T->set_var('subm_by', $LANG_DQ['subm_by']);
                $contrqu = DB_query("SELECT uid, username FROM {$_TABLES['users']} WHERE uid={$row['uid']}");
                list($uid,$username) = DB_fetchArray($contrqu);
                $username = DQ_linkProfile($uid, $username);
                $T->set_var('contr', $username);
                $T->set_var('datecontr', date('Y-m-d', $row['dt']));

                $catlist = DQ_catlistDisplay($row['id']);
                $T->set_var('cat', $LANG_DQ['cat']);
                $T->set_var('catname', $catlist);
                if(SEC_hasRights('dailyquote.edit')){
                    $icon_url = "{$_CONF['layout_url']}/images/edit.$_IMAGE_TYPE";
                    $editlink = '<a href="' . DQ_ADMIN_URL . 
                            '/index.php?mode=edit&id=' .
                            $row['id'] . '">' .
                            COM_createImage($icon_url, $LANG_ADMIN['edit']) .
                            '</a>';
                    $T->set_var('editlink', $editlink);
                }
                $T->parse('output','page');
                $retval .= $T->finish($T->get_var('output'));
            }
            $retval .= display_pagenav($categ, $keywords, $keytype, $type, $datestart, $dateend, $contr, $qid, $show, $stat, $catget, $mode, $page, $numrows);
        }
        else {
            $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['searchmsg2'] . "</p>";
            return $retval;
        }
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'dispquotesfooter.thtml');
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
    }

    return $retval;
}

//displays the search form
function search_form()
{
    global $_TABLES, $_CONF, $LANG_DQ;

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'searchform.thtml');

    $T ->set_var(array(
        'site_url'      => $_CONF['site_url'],
        'searchlabel'   => $LANG_DQ['searchlabel'],
        'keyphrase'     => $LANG_DQ['keyphrase'],
        'keyall'        => $LANG_DQ['keyall'],
        'keyany'        => $LANG_DQ['keyany'],
        'datestart'     => $LANG_DQ['date'],
        'enddate'       => $LANG_DQ['enddate'],
        'dateformat'    => $LANG_DQ['dateformat'],
        'type'          => $LANG_DQ['type'],
        'sortopt1'      => $LANG_DQ['sortopt1'],
        'sortopt2'      => $LANG_DQ['sortopt2'],
        'sortopt4'      => $LANG_DQ['sortopt4'],
        'sortopt5'      => $LANG_DQ['sortopt5'],
        'sortopt6'      => $LANG_DQ['sortopt6'],
        'sortopt7'      => $LANG_DQ['sortopt7'],
        'limit', $LANG_DQ['limit'],
    ) );
//    $T ->parse('output','page');
//    $retval = $T->finish($T->get_var('output'));

    //  Create the Contributor selection
    $result = DB_query("SELECT uid
            FROM {$_TABLES['dailyquote_quotes']} 
            WHERE enabled='1' 
            GROUP BY uid ASC");
    
    $T->set_block('page', 'ContribSelect', 'Contrib');
    while ($row = DB_fetchArray($result)) {
        $username = COM_getDisplayName($row['uid']);
        $T->set_var('contr', $username);
        $T ->parse('Contrib', 'ContribSelect', true);
    }

    //$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    //$T ->set_file('page', 'searchcontropt2.thtml');
    //$T ->set_var('category', $LANG_DQ['category']);
    //$T ->parse('output','page');
    //$retval .= $T->finish($T->get_var('output'));

    //  Create the Category selection
    $result = DB_query("SELECT id, name 
                FROM  {$_TABLES['dailyquote_cat']}
                ORDER BY name");
    $T->set_block('page', 'CatSelect', 'Cat');
    while ($row = DB_fetchArray($result)) {
        //$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        //$T ->set_file('page', 'searchcatopt.thtml');
        $T->set_var(array(
            'catoption' => $row['name'],
            'catid'     => $row['id'],
        ));
        $T->parse('Cat', 'CatSelect', true);
        //$retval .= $T->finish($T->get_var('output'));
    }

    //$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
//    $T ->set_file('page', 'searchcatopt2.thtml');
//    $T ->set_var('submit', $LANG_DQ['submit']);a

    $T ->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}

 
/* 
* Main Function
*/

$display = COM_siteHeader();
$display .= COM_startBlock($LANG_DQ['indextitle']);

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqheader.thtml');
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('plugin', 'dailyquote');
$T->set_var('indextitle', $LANG_DQ['searchtitle']);
$T->set_var('indexintro', $LANG_DQ['searchintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

$display .= search_form();

if (isset($_GET['qid'])){
    $qid = $_GET['qid'];
}
if (isset($_GET['show'])){
    $show = $_GET['show'];
}
if (isset($_GET['stat'])){
    $stat = $_GET['stat'];
}
if (isset($_GET['cat'])){
    $catget = $_GET['cat'];
}
if ((isset($_GET['mode'])) && ($mode == "manage")){
    $mode = "manage";
}
if (empty($_GET['page'])){
    $page = 1;
}
if (isset($_POST['categ'])){
    $categ = $_POST['categ'];
} elseif (isset($_GET['categ'])){
    $categ = $_GET['categ'];
}
if (isset($_POST['keywords'])){
    $keywords = $_POST['keywords'];
} elseif (isset($_GET['keywords'])){
    $keywords = $_GET['keywords'];
}
if (isset($_POST['keytype'])){
    $keytype = $_POST['keytype'];
} elseif (isset($_GET['keytype'])){
    $keytype = $_GET['keytype'];
}
if (isset($_POST['type'])){
    $type = $_POST['type'];
} elseif (isset($_GET['type'])){
    $type = $_GET['type'];
}
if (isset($_POST['datestart'])){
    $datestart = $_POST['datestart'];
} elseif (isset($_GET['datestart'])){
    $datestart = $_GET['datestart'];
}
if (isset($_POST['dateend'])){
    $dateend = $_POST['dateend'];
} elseif (isset($_GET['dateend'])){
    $dateend = $_GET['dateend'];
}
if (isset($_POST['contr'])){
    $contr = $_POST['contr'];
} elseif (isset($_GET['contr'])){
    $contr = $_GET['contr'];
}

if (!isset($_POST['submit']) && 
        !isset($qid) && 
        !isset($stat) && 
        !isset($cat) && 
        !isset($catget) &&
        !isset($keywords)
) {
    USES_dailyquote_functions();
    $display .= DQ_random_quote();
} elseif (isset($_POST['submit']) || 
        isset($qid) || 
        isset($stat) || 
        isset($catget) || 
        isset($keywords)
) {
    $display .= search_results($categ, $keywords, $keytype, $type, $datestart, $dateend, $contr, $qid, $show, $stat, $catget, $mode, $page);
}

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqfooter.thtml');
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

$display .= COM_endBlock();
$display .= COM_siteFooter();
echo $display;
?>
