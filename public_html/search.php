<?php

// +---------------------------------------------------------------------------+
// | Daily Quote Plugin v1.0.5 for Geeklog - The Ultimate Weblog                 |
// +---------------------------------------------------------------------------+
// | search.php                                                                |
// |                                                                           |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2004 by the following authors:                              |
// |                                                                           |
// | Author: Alf Deeley aka machinari - ajdeeley@summitpages.ca                |
// | Constructed with the Universal Plugin                                     |
// | Copyright (C) 2002 by the following authors:                              |
// | Tom Willett                 -    twillett@users.sourceforge.net           |
// | Blaine Lang                 -    langmail@sympatico.ca                    |
// | The Universal Plugin is based on prior work by:                           |
// | Tony Bibbs                  -    tony@tonybibbs.com                       |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+
//

require_once('../lib-common.php');

// Retrieve access settings
$query = DB_query("SELECT anonview FROM {$_TABLES['dailyquote_settings']}");
list($anonview) = DB_fetchArray($query);
// Check user has rights to access this page
if (($anonview == '0') && ($_USER['uid'] < 2)) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the dailyquote page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_DQ00['access_denied']);
    $display .= $LANG_DQ00['access_denied_msg1'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}

//displays the google-like page nav
function display_pagenav($categ, $keywords, $keytype, $type, $datestart, $dateend, $contr, $qid, $show, $stat, $catget, $mode, $page, $numrows)
{
    global $_TABLES, $_CONF, $LANG_DQ;

    //print page nav here
    //$query1 = DB_query ("SELECT COUNT(*) AS numquotes FROM {$_TABLES['dailyquote_quotes']} WHERE Status='1'");
    //list($numquotes) = DB_fetchArray($query1);
    $query2 = DB_query("SELECT searchdisplim AS displim FROM {$_TABLES['dailyquote_settings']}");
    list($displim) = DB_fetchArray($query2);
    if ($numrows > $displim) {
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
        if (!empty($datestart)){
            $datestartvar = "datestart=".$datestart.'&amp;';
        }
        if (!empty($dateend)){
            $dateendvar = "dateend=".$dateend.'&amp;';
        }
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
function search_results($categ, $keywords, $keytype, $type, $datestart, $dateend, $contr, $qid, $show, $stat, $catget, $mode, $page){
    global $_TABLES, $_CONF, $LANG_DQ;

    $sql = "SELECT DISTINCT ID, Quotes, Quoted, Title, Source, Sourcedate, Date, q.UID, username";
    $sql .= " FROM {$_TABLES['dailyquote_quotes']} q, {$_TABLES['dailyquote_lookup']} l, {$_TABLES['users']} gl";
    if (!empty($categ)){
        $cat = slashctrl($categ);
        $result = DB_query("SELECT ID FROM {$_TABLES['dailyquote_cat']} WHERE Name='$cat'");
        list($catID) = DB_fetchArray($result);
        $catwh = " AND l.CID='$catID'";
    }
    if (!empty($catget)){
        $catwh = " AND l.CID='$catget' AND l.QID=ID";
    }
    $sql .= " WHERE (q.Status='1' AND q.UID=gl.uid AND l.Status='1' AND l.QID=ID";
    if(isset($catwh)){
        $sql .= $catwh;
    }
    $sql .= ")";

    if (isset($keywords)){
        $query = trim($keywords);
    }
    if ((isset($_POST['submit'])) && (empty($query))){
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['searchmsg1'] . "</p>";
        return $retval;
    }

    if (isset($keytype)){
        if ($keytype == "phrase"){//exact phrase as query
            $mysearchterm = slashctrl(trim ($query));
            $q = "(Quotes like '%$mysearchterm%')";
            $pq = "(Quoted like '%$mysearchterm%')";
            $c = "(username like '%$mysearchterm%')";
            $s = "(Source like '%mysearchterm%')";
            $t = "(Title like '%mysearchterm%')";
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
                    $q = "(Quotes like '%$mysearchterm%')";
                    $pq = "(Quoted like '%$mysearchterm%')";
                    $c = "(username like '%$mysearchterm%')";
                    $s = "(Source like '%mysearchterm%')";
                    $t = "(Title like '%mysearchterm%')";
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
                    $q = "(Quotes like '%$mysearchterm%')";
                    $pq = "(Quoted like '%$mysearchterm%')";
                    $c = "(username like '%$mysearchterm%')";
                    $s = "(Source like '%mysearchterm%')";
                    $t = "(Title like '%mysearchterm%')";
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

    if (!empty($datestart)){
        $sql .= " AND q.Date >= '$start'";
    }
    if (!empty($dateend)){
        $sql .= " AND q.Date < '$end'";
    }

    if (!empty($contr)){
        $contr = slashctrl($contr);
        //$result = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE username='$subm'");
        //list($uid) = DB_fetchArray($result);
        $sql .= " AND username='$contr'";
    }

    // this GET stuff comes from the GL search results urls and displays here accordingly
    if ((isset($qid)) && (isset($show))){
        if ($show == "quote"){//shows a single quote on the search page
            $sql .= " AND ID='$qid'";
        } elseif ($show == "quoted"){//shows all quotes by whomever was quoted
            $result = DB_query("SELECT Quoted From {$_TABLES['dailyquote_quotes']} WHERE ID=$qid");
            list($quoted) = DB_fetchArray($result);
            $quoted = slashctrl($quoted);
            $sql .= " AND Quoted='$quoted'";
        } elseif ($show == "name"){//shows all quotes submitted by whoever submitted $qid
            $result = DB_query("SELECT UID FROM {$_TABLES['dailyquote_quotes']} WHERE ID='$qid'");
            list($uid) = DB_fetchArray($result);
            $sql .= " AND q.UID='$uid'";
        }
    }
    elseif (isset($stat)){// this comes from the GL stats page
        $stat = slashctrl($stat);
        $sql .= " AND Quoted='$stat'";
    }

    $numrows = DB_query($sql);

    // Retrieve results per page setting
    if(!$query = DB_query("SELECT searchdisplim AS displim FROM {$_TABLES['dailyquote_settings']}")){
        $displim = 10;
    } else {
        list( $displim) = DB_fetchArray($query);
        $limit = ($displim * $page) - $displim;
        $sql .= " LIMIT $limit, $displim";
    }

    $result = DB_query($sql);
    if (!$result){
        $retval .= $LANG_DQ['disperror'];
        COM_errorLog("An error occured while retrieving list of quotes",1);
    }
    //display quotes if any to display
    else {
        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'dispquotesheader.thtml');
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        $numrows = DB_numRows($numrows);
        if ($numrows>0){
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T->set_file('page', 'numresults.thtml');
            $T->set_var('numresults', $numrows);
            $T->set_var('numresultstxt', $LANG_DQ['numresultstxt']);
            if(isset($catget)){
                if(!$catgetsql = DB_query("SELECT Name FROM {$_TABLES['dailyquote_cat']} WHERE ID=$catget")){
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
                    $title .= $row['Title'];
                    $title .= '</p>';
                    $T->set_var('title', $title);
                }
                $T->set_var('quote', $row['Quotes']);
                $quoted = ggllink($row['Quoted']);
                $T->set_var('quoted', $quoted);
                if (!empty($row['Source'])){
                    $T->set_var('source', '&nbsp;--&nbsp;' . $row['Source']);
                }
                if (!empty($row['Sourcedate'])){
                    $T->set_var('sourcedate', '&nbsp;&nbsp;(' . $row['Sourcedate'] . ')');
                }
                $T->set_var('subm_by', $LANG_DQ['subm_by']);
                $contrqu = DB_query("SELECT UID, username FROM {$_TABLES['users']} WHERE uid={$row['UID']}");
                list($uid,$username) = DB_fetchArray($contrqu);
                $username = prflink($uid,$username);
                $T->set_var('contr', $username);
                $T->set_var('datecontr', $row['Date']);
                $cat = DB_query("SELECT c.ID, c.Name FROM {$_TABLES['dailyquote_cat']} c, {$_TABLES['dailyquote_lookup']} l WHERE l.QID={$row['ID']} AND c.ID=l.CID AND l.Status='1'");
                $i = 0;
                $catlist = "";
                while ($catrow = DB_fetchArray($cat)){
                    if ($i > 0){
                        $catlist .= ", ";
                    }
                    $catlink = catlink($catrow['ID'],$catrow['Name']);
                    $catlist = $catlist . $catlink;
                    $i++;
                }
                $T->set_var('cat', $LANG_DQ['cat']);
                $T->set_var('dispcat', $catlist);
                if(SEC_hasRights('dailyquote.edit')){
                    $editlink = '<a href="' . $_CONF['site_url'] . '/dailyquote/manage.php?qid=';
                    $editlink .= $row['ID'] . '">';
                    $editlink .= '<img src="' . $_CONF['site_url'] . '/dailyquote/images/edit.gif' . '" alt="';
                    $editlink .= $LANG_DQ['editlink'];
                    $editlink .= '" border="0" />';
                    $editlink .= '</a>';
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
function search_form(){
    global $_TABLES, $_CONF, $LANG_DQ;

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'searchform.thtml');
    $T ->set_var('site_url', $_CONF['site_url']);
    $T ->set_var('searchlabel', $LANG_DQ['searchlabel']);
    $T ->set_var('keyphrase', $LANG_DQ['keyphrase']);
    $T ->set_var('keyall', $LANG_DQ['keyall']);
    $T ->set_var('keyany', $LANG_DQ['keyany']);
    $T ->set_var('datestart', $LANG_DQ['date']);
    $T ->set_var('enddate', $LANG_DQ['enddate']);
    $T ->set_var('dateformat', $LANG_DQ['dateformat']);
    $T ->set_var('type', $LANG_DQ['type']);
    $T ->set_var('sortopt1', $LANG_DQ['sortopt1']);
    $T ->set_var('sortopt2', $LANG_DQ['sortopt2']);
    $T ->set_var('sortopt4', $LANG_DQ['sortopt4']);
    $T ->set_var('sortopt5', $LANG_DQ['sortopt5']);
    $T ->set_var('sortopt6', $LANG_DQ['sortopt6']);
    $T ->set_var('sortopt7', $LANG_DQ['sortopt7']);
    $T ->set_var('limit', $LANG_DQ['limit']);
    $T ->parse('output','page');
    $retval = $T->finish($T->get_var('output'));

    //retrieve contributors from db
    $result = DB_query("SELECT UID FROM {$_TABLES['dailyquote_quotes']} WHERE Status='1' GROUP BY UID ASC");
    $numrows = DB_numRows($result);
    if ($numrows>0){
        while ($row = DB_fetchArray($result)){
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T ->set_file('page', 'searchcontropt.thtml');
            $subm = DB_query("SELECT username FROM {$_TABLES['users']} WHERE uid={$row['UID']}");
            list($username) = DB_fetchArray($subm);
            $T->set_var('contr', $username);
            $T ->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
        }
    }

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'searchcontropt2.thtml');
    $T ->set_var('sortopt3', $LANG_DQ['sortopt3']);
    $T ->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    //retrieve categories from db
    $result = DB_query("Select DISTINCT Name FROM {$_TABLES['dailyquote_cat']} c, {$_TABLES['dailyquote_lookup']} l WHERE ID=l.CID AND l.Status='1' ORDER BY Name");
    $numrows = DB_numRows($result);
    if ($numrows>0){
        while ($row = DB_fetchArray($result)){
            $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
            $T ->set_file('page', 'searchcatopt.thtml');
            $T->set_var('catoption', $row['Name']);
            $T->parse('output','page');
            $retval .= $T->finish($T->get_var('output'));
        }
    }

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T ->set_file('page', 'searchcatopt2.thtml');
    $T ->set_var('submit', $LANG_DQ['go']);
    $T ->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));
    return $retval;
}
 
/* 
* Main Function
*/

if(eregi('1.3.1',VERSION)){
    $dqblockmenu = array(dqmenu);
    $display = COM_siteHeader($dqblockmenu);
} else {
    $display = COM_siteHeader();
}
$display .= COM_startBlock($LANG_DQ['indextitle']);

$T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
$T->set_file('page', 'dqheader.thtml');
$T->set_var('header', $LANG_DQ00['plugin']);
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('plugin', 'dailyquote');
$T->set_var('indextitle', $LANG_DQ['searchtitle']);
$T->set_var('indexintro', $LANG_DQ['searchintro']);
$T->parse('output','page');
$display .= $T->finish($T->get_var('output'));

//display a row of pertinent links
if(!eregi('1.3.1',VERSION)){
    $display .= link_row();
}

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

if ((!isset($_POST['submit'])) && (!isset($qid)) && (!isset($stat)) && (!isset($cat)) && (!isset($keywords))){
    $display .= random_quote();
}
elseif ((isset($_POST['submit'])) || (isset($qid)) || (isset($stat)) || (isset($cat)) || (isset($keywords))){
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
