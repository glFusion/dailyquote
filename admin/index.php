<?php

require_once('../../../lib-common.php');
require_once('dqdatabase.php');


function X_config_form(){
    global $_TABLES, $_CONF, $LANG_DQ, $_POST;

    //get config
    if (!$result = DB_query("SELECT * FROM {$_TABLES['dailyquote_settings']}")){
        $retval = $LANG_DQ['configerror'];
        COM_errorLog("An error occured while retrieving config",1);
    } else {
        $row = DB_fetchArray($result);
    }

    //display form
    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file(array ('page' => 'admin.thtml','dbresult' => 'dbresult.thtml', 'oldbaks' => 'oldbaks.thtml', 'oldbaksli' => 'oldbaksli.thtml', 'prevbak' => 'preview.thtml'));
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('site_admin_url', $_CONF['site_admin_url']);
    $T->set_var('start_genblock', COM_startBlock($LANG_DQ['general']));
    $T->set_var('end_block', COM_endBlock());
    //$T->set_var('general', $LANG_DQ['general']);
    $T->set_var('disprand', $LANG_DQ['disprand']);
    $T->set_var('hour', $LANG_DQ['hour']);
    $T->set_var('hours', $LANG_DQ['hours']);
    $T->set_var('pgrefresh', $LANG_DQ['pagerefresh']);
    if ($row['cacheperiod'] == '3600'){
        $T->set_var('currand1', 'SELECTED');
    } elseif ($row['cacheperiod'] == '21600'){
        $T->set_var('currand6', 'SELECTED');
    } elseif ($row['cacheperiod'] == '43200'){
        $T->set_var('currand12', 'SELECTED');
    } elseif ($row['cacheperiod'] == '86400'){
        $T->set_var('currand24', 'SELECTED');
    } elseif ($row['cacheperiod'] == '172800'){
        $T->set_var('currand1', 'SELECTED');
    } elseif ($row['cacheperiod'] == '0'){
        $T->set_var('currand0', 'SELECTED');
    }
    $T->set_var('displim', $LANG_DQ['displim']);
    if ($row['indexdisplim'] == '5'){
        $T->set_var('currdisp5', 'SELECTED');
    } elseif ($row['indexdisplim'] == '10'){
        $T->set_var('currdisp10', 'SELECTED');
    } elseif ($row['indexdisplim'] == '25'){
        $T->set_var('currdisp25', 'SELECTED');
    } elseif ($row['indexdisplim'] == '50'){
        $T->set_var('currdisp50', 'SELECTED');
    }
    $T->set_var('srchdisplim', $LANG_DQ['srchdisplim']);
    if ($row['searchdisplim'] == '5'){
        $T->set_var('currsrch5', 'SELECTED');
    } elseif ($row['searchdisplim'] == '10'){
        $T->set_var('currsrch10', 'SELECTED');
    } elseif ($row['searchdisplim'] == '25'){
        $T->set_var('currsrch25', 'SELECTED');
    } elseif ($row['searchdisplim'] == '50'){
        $T->set_var('currsrch50', 'SELECTED');
    }
    $T->set_var('whatsnew', $LANG_DQ['whatsnew']);
    if ($row['whatsnew'] == '1'){
        $T->set_var('currwhatsnewy', 'CHECKED');
    } elseif ($row['whatsnew'] == '0'){
        $T->set_var('currwhatsnewn', 'CHECKED');
    }
    $T->set_var('gglink', $LANG_DQ['gglink']);
    if ($row['gglink'] == '1'){
        $T->set_var('gglinky', 'CHECKED');
    } elseif ($row['gglink'] == '0'){
        $T->set_var('gglinkn', 'CHECKED');
    }
    $T->set_var('cntrblk', $LANG_DQ['cntrblk']);
    $T->set_var('yes', $LANG_DQ['yes']);
    $T->set_var('no', $LANG_DQ['no']);
    if ($row['cntrblkenable'] == '1'){
        $T->set_var('currblky', 'CHECKED');
    } elseif ($row['cntrblkenable'] == '0'){
        $T->set_var('currblkn', 'CHECKED');
    }
    $T->set_var('cntrblkhom', $LANG_DQ['cntrblkhom']);
    if ($row['cntrblkhome'] == '1'){
        $T->set_var('currhomy', 'CHECKED');
    } elseif ($row['cntrblkhome'] == '0'){
        $T->set_var('currhomn', 'CHECKED');
    }
    $T->set_var('cntrblkpos', $LANG_DQ['cntrblkpos']);
    $T->set_var('top', $LANG_DQ['top']);
    $T->set_var('below', $LANG_DQ['below']);
    $T->set_var('bottom', $LANG_DQ['bottom']);
    if ($row['cntrblkpos'] == '1'){
        $T->set_var('currpos1', 'SELECTED');
    } elseif ($row['cntrblkpos'] == '2'){
        $T->set_var('currpos2', 'SELECTED');
    } elseif ($row['cntrblkpos'] == '3'){
        $T->set_var('currpos3', 'SELECTED');
    }
    $T->set_var('cntrlink', $LANG_DQ['cntrlink']);
    if ($row['cntrlink'] == '1'){
        $T->set_var('currlinkon', 'CHECKED');
    } elseif ($row['cntrlink'] == '0'){
        $T->set_var('currlinkoff', 'CHECKED');
    }
    //block component config
    $T->set_var('start_compblock', COM_startBlock($LANG_DQ['components']));
    //$T->set_var('components', $LANG_DQ['components']);
    $T->set_var('cntrcomp', $LANG_DQ['cntrcomp']);
    $T->set_var('phpcomp', $LANG_DQ['phpcomp']);
    $T->set_var('rndmcomp', $LANG_DQ['rndmcomp']);
    $T->set_var('compinstr', $LANG_DQ['compinstr']);
    $T->set_var('blksrc', $LANG_DQ['blksrc']);
    $T->set_var('blkcnt', $LANG_DQ['blkcnt']);
    $T->set_var('blkcat', $LANG_DQ['blkcat']);
    $T->set_var('blkadd', $LANG_DQ['blkadd']);
    $T->set_var('blktit', $LANG_DQ['blktit']);
    if ($row['blk1'] == '1'){//centerblock source/date
        $T->set_var('cntrsrcon', 'CHECKED');
    } elseif ($row['blk1'] == '0'){
        $T->set_var('cntrsrcoff', 'CHECKED');
    }
    if ($row['blk2'] == '1'){//centerblock contributor/date
        $T->set_var('cntrcnton', 'CHECKED');
    } elseif ($row['blk2'] == '0'){
        $T->set_var('cntrcntoff', 'CHECKED');
    }
    if ($row['blk3'] == '1'){//centerblock categories
        $T->set_var('cntrcaton', 'CHECKED');
    } elseif ($row['blk3'] == '0'){
        $T->set_var('cntrcatoff', 'CHECKED');
    }
    if ($row['blk4'] == '1'){//centerblock add link
        $T->set_var('cntraddon', 'CHECKED');
    } elseif ($row['blk4'] == '0'){
        $T->set_var('cntraddoff', 'CHECKED');
    }
    if ($row['blk5'] == '1'){//phpblock source/date
        $T->set_var('phpsrcon', 'CHECKED');
    } elseif ($row['blk5'] == '0'){
        $T->set_var('phpsrcoff', 'CHECKED');
    }
    if ($row['blk6'] == '1'){//phpblock contributor/date
        $T->set_var('phpcnton', 'CHECKED');
    } elseif ($row['blk6'] == '0'){
        $T->set_var('phpcntoff', 'CHECKED');
    }
    if ($row['blk7'] == '1'){//phpblock categories
        $T->set_var('phpcaton', 'CHECKED');
    } elseif ($row['blk7'] == '0'){
        $T->set_var('phpcatoff', 'CHECKED');
    }
    if ($row['blk8'] == '1'){//phpblock add link
        $T->set_var('phpaddon', 'CHECKED');
    } elseif ($row['blk8'] == '0'){
        $T->set_var('phpaddoff', 'CHECKED');
    }
    if ($row['blk9'] == '1'){//random block source/date
        $T->set_var('rndmsrcon', 'CHECKED');
    } elseif ($row['blk9'] == '0'){
        $T->set_var('rndmsrcoff', 'CHECKED');
    }
    if ($row['blk10'] == '1'){//random block contributor/date
        $T->set_var('rndmcnton', 'CHECKED');
    } elseif ($row['blk10'] == '0'){
        $T->set_var('rndmcntoff', 'CHECKED');
    }
    if ($row['blk11'] == '1'){//random block categories
        $T->set_var('rndmcaton', 'CHECKED');
    } elseif ($row['blk11'] == '0'){
        $T->set_var('rndmcatoff', 'CHECKED');
    }
    if ($row['blk12'] == '1'){//centerblock Title
        $T->set_var('cntrtiton', 'CHECKED');
    } elseif ($row['blk12'] == '0'){
        $T->set_var('cntrtitoff', 'CHECKED');
    }
    if ($row['blk13'] == '1'){//phpblock Title
        $T->set_var('phptiton', 'CHECKED');
    } elseif ($row['blk13'] == '0'){
        $T->set_var('phptitoff', 'CHECKED');
    }
    if ($row['blk14'] == '1'){//random block Title
        $T->set_var('rndmtiton', 'CHECKED');
    } elseif ($row['blk14'] == '0'){
        $T->set_var('rndmtitoff', 'CHECKED');
    }
    //end block component config
    $T->set_var('linkon', $LANG_DQ['linkon']);
    $T->set_var('start_permsblock', COM_startBlock($LANG_DQ['perms']));
    //$T->set_var('perms', $LANG_DQ['perms']);
    $T->set_var('queue', $LANG_DQ['queue']);
    $T->set_var('queueoff', $LANG_DQ['queueoff']);
    $T->set_var('on', $LANG_DQ['on']);
    $T->set_var('off', $LANG_DQ['off']);
    if ($row['queue'] == '1'){
        $T->set_var('currqueueon', 'CHECKED');
    } elseif ($row['queue'] == '0'){
        $T->set_var('currqueueoff', 'CHECKED');
    }
    $T->set_var('queuenumtxt', $LANG_DQ['queuenumtxt']);
    $mod = "&nbsp;&nbsp;<a href=\"" . $_CONF['site_url'] . "/dailyquote/moderate.php\">";
    //$mod .= "<img src=\"" . $_CONF['site_url'] . "/dailyquote/images/moderate.jpg" . "\" alt=\"";
    $mod .= $LANG_DQ['moderationlink'] . "?";
    //$mod .= "\" border=\"0\" />";
    $mod .= "</a>&nbsp;&nbsp;";
    //chk queue
    if (!$modnum = DB_count($_TABLES['dailyquote_quotes'], 'Status', '0')) {
        //$retval = $LANG_DQ['configerror'];
        //COM_errorLog("An error occured while retrieving config",1);
        $T->set_var('queuenum', '0');
    } elseif ($modnum > '0') {
        $T->set_var('queuenum', $modnum);
        $T->set_var('moderationlink', $mod);
    }
    $T->set_var('anonview', $LANG_DQ['anonview']);
    if ($row['anonview'] == '1'){
        $T->set_var('curranony', 'CHECKED');
    } elseif ($row['anonview'] == '0'){
        $T->set_var('curranonn', 'CHECKED');
    }
    $T->set_var('anonadd', $LANG_DQ['anonadd']);
    if ($row['anonadd'] == '1'){
        $T->set_var('curranonaddy', 'CHECKED');
    } elseif ($row['anonadd'] == '0'){
        $T->set_var('curranonaddn', 'CHECKED');
    }
    $T->set_var('logadd', $LANG_DQ['logadd']);
    if ($row['loginadd'] == '1'){
        $T->set_var('currlogy', 'CHECKED');
    } elseif ($row['loginadd'] == '0'){
        $T->set_var('currlogn', 'CHECKED');
    }
    $T->set_var('logaddcat', $LANG_DQ['logaddcat']);
    if ($row['loginaddcat'] == '1'){
        $T->set_var('currlogcaty', 'CHECKED');
    } elseif ($row['loginaddcat'] == '0'){
        $T->set_var('currlogcatn', 'CHECKED');
    }
    $T->set_var('logbatchinstr', $LANG_DQ['logbatchinstr']);
    $T->set_var('logbatch', $LANG_DQ['logbatch']);
    if ($row['loginbatch'] == '1'){
        $T->set_var('currbatchy', 'CHECKED');
    } elseif ($row['loginbatch'] == '0'){
        $T->set_var('currbatchn', 'CHECKED');
    }
    $T->set_var('update', $LANG_DQ['update']);
    $T->set_var('reset', $LANG_DQ['reset']);
    $T->set_var('default', $LANG_DQ['default']);
    //buttons for db check and repair and backup
    $T->set_var('start_dbblock', COM_startBlock($LANG_DQ['dbman']));
    //$T->set_var('dbman', $LANG_DQ['dbman']);
    $T->set_var('dbinstr', $LANG_DQ['dbinstr']);
    $T->set_var('chkdb', $LANG_DQ['chkdb']);
    $T->set_var('dbbackup', $LANG_DQ['dbbackup']);
    if ($_POST['submit'] == $LANG_DQ['chkdb']){
        checkdb(&$T);
    } elseif ($_POST['submit'] == $LANG_DQ['repdb']){
        checkdb(&$T,1);
    } elseif ($_POST['submit'] == $LANG_DQ['dbbackup']){
        datadump(&$T);
    } elseif ($_POST['submit'] == $LANG_DQ['preview1']){
        previewbak(&$T, $_POST['bak']);
    } elseif (($_POST['submit'] == $LANG_DQ['delete']) && (!empty($_POST['bak']))){
        $res = deletebak($_POST['bak']);
        $T->set_var('result', $res, true);
        $T->parse('delresult', 'dbresult', true);
    } elseif (($_POST['submit'] == $LANG_DQ['restore']) && (!empty($_POST['bak']))){
        $res = restorebak($_POST['bak']);
        $T->set_var('result', $res, true);
        $T->parse('restresult', 'dbresult', true);
    }
    //return backup filelist if backups exist
    if (is_array($filelist = filelist())){
        $T->set_var('head', $LANG_DQ['oldbaks']);
        foreach ($filelist as $bakfile){
            $T->set_var('bakfile', $bakfile);
            $filesize = filesize($_CONF['path_html'] . 'dailyquote/data/' . $bakfile);
            $T->set_var('filesize', $filesize);
            $T->parse('fileli', 'oldbaksli', true);
            clearstatcache();
        }
        $T->set_var('preview', $LANG_DQ['preview1']);
        $T->set_var('restore', $LANG_DQ['restore']);
        $T->set_var('delete', $LANG_DQ['delete']);
        $T->parse('oldbackups_list', 'oldbaks');
    }

    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}

//updates the settings table with new prefs
function Xupdate_config(){
    global $_TABLES, $_CONF, $LANG_DQ, $_POST;

    //gather the posted settings into query
    $sql = "UPDATE {$_TABLES['dailyquote_settings']} SET";
    $sql .= " indexdisplim={$_POST['displim']}";
    $sql .= ", searchdisplim={$_POST['srchdisplim']}";
    $sql .= ", anonadd={$_POST['anonadd']}";
    $sql .= ", anonview={$_POST['anonview']}";
    $sql .= ", loginadd={$_POST['logadd']}";
    $sql .= ", loginaddcat={$_POST['logaddcat']}";
    $sql .= ", loginbatch={$_POST['loginbatch']}";
    $sql .= ", cntrblkenable={$_POST['cntrblk']}";
    $sql .= ", cntrblkpos={$_POST['cntrblkpos']}";
    $sql .= ", cntrblkhome={$_POST['cntrblkhom']}";
    $sql .= ", blk1={$_POST['cntrsrc']}";
    $sql .= ", blk2={$_POST['cntrcnt']}";
    $sql .= ", blk3={$_POST['cntrcat']}";
    $sql .= ", blk4={$_POST['cntradd']}";
    $sql .= ", blk5={$_POST['phpsrc']}";
    $sql .= ", blk6={$_POST['phpcnt']}";
    $sql .= ", blk7={$_POST['phpcat']}";
    $sql .= ", blk8={$_POST['phpadd']}";
    $sql .= ", blk9={$_POST['rndmsrc']}";
    $sql .= ", blk10={$_POST['rndmcnt']}";
    $sql .= ", blk11={$_POST['rndmcat']}";
    $sql .= ", blk12={$_POST['cntrtit']}";
    $sql .= ", blk13={$_POST['phptit']}";
    $sql .= ", blk14={$_POST['rndmtit']}";
    $sql .= ", gglink={$_POST['gglink']}";
    $sql .= ", whatsnew={$_POST['whatsnew']}";

    //deal with the cached quote before updating the rest
    if (!$chkcache = DB_query("SELECT cacheperiod FROM {$_TABLES['dailyquote_settings']}")){
        $retval = $LANG_DQ['updcfgerror'];
        COM_errorLog("An error occured while updating the configuration",1);
    } else {
        list($cacheperiod) = DB_fetchArray($chkcache);
        if (($cacheperiod != $_POST['disprand']) && ($_POST['disprand'] != '0')){//update cached quote and refresh time
            if(!$dbquote = DB_query("SELECT ID FROM {$_TABLES['dailyquote_quotes']} WHERE Status='1' ORDER BY rand() LIMIT 1")){
                $retval = $LANG_DQ['updcfgerror'];
                COM_errorLog("An error occured while updating the configuration",1);
            } else {
                list ($qid) = DB_fetchArray($dbquote);
                //$secs = time();
                $sql .= ", cacheupdate='" . time() ."', cacheperiod='{$_POST['disprand']}', cacheQID='$qid'";
            }
        } elseif (($cacheperiod != $_POST['disprand']) && ($_POST['disprand'] == '0')){//delete cached quote
            $sql .= ", cacheupdate='', cacheperiod={$_POST['disprand']}, cacheQID=''";
        }
    }

    //deal with the queue before updating the rest
    if (!$chkqueue = DB_query("SELECT queue FROM {$_TABLES['dailyquote_settings']}")){
        $retval = $LANG_DQ['updcfgerror'];
        COM_errorLog("An error occured while updating the configuration",1);
    } else {
        list($queue) = DB_fetchArray($chkqueue);
        if($queue != $_POST['queue']){
            $sql .= ", queue='{$_POST['queue']}'";
            if($_POST['queue'] == '0'){
                if(!DB_query("UPDATE {$_TABLES['dailyquote_quotes']} SET Status='1' WHERE Status='0'")){
                    $retval = $LANG_DQ['updcfgerror'];
                    COM_errorLog("An error occured while updating the configuration",1);
                }
                if(!DB_query("UPDATE {$_TABLES['dailyquote_cat']} SET Status='1' WHERE Status='0'")){
                    $retval = $LANG_DQ['updcfgerror'];
                    COM_errorLog("An error occured while updating the configuration",1);
                }
            }
        }
    }

    //update the table
    if (!DB_query($sql)){
        $retval = $LANG_DQ['updcfgerror'];
        COM_errorLog("An error occured while updating the configuration",1);
    } else {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['updsuccess'] ."</p>";
    }
    return $retval;
}

//applies the default prefs to the settings table
function Xdefault_cfg(){
    global $_TABLES, $_CONF, $LANG_DQ;

    //list the default settings
    $sql = "UPDATE {$_TABLES['dailyquote_settings']} SET";
    $sql .= " indexdisplim='10'";
    $sql .= ", searchdisplim='50'";
    $sql .= ", queue='1'";
    $sql .= ", anonadd='0'";
    $sql .= ", anonview='1'";
    $sql .= ", loginadd='1'";
    $sql .= ", loginaddcat='1'";
    $sql .= ", loginbatch='0'";
    $sql .= ", cntrblkenable='0'";
    $sql .= ", cntrblkpos='2'";
    $sql .= ", cntrblkhome='1'";
    $sql .= ", blk1='0'";
    $sql .= ", blk2='0'";
    $sql .= ", blk3='0'";
    $sql .= ", blk4='1'";
    $sql .= ", blk5='0'";
    $sql .= ", blk6='0'";
    $sql .= ", blk7='0'";
    $sql .= ", blk8='0'";
    $sql .= ", blk9='1'";
    $sql .= ", blk10='0'";
    $sql .= ", blk11='1'";
    $sql .= ", blk12='0'";
    $sql .= ", blk13='0'";
    $sql .= ", blk14='0'";
    $sql .= ", gglink='1'";
    $sql .= ", cacheupdate=''";
    $sql .= ", cacheperiod='0'";
    $sql .= ", cacheQID=''";
    $sql .= ", whatsnew='0'";

    //update the table
    if (!DB_query($sql)){
        $retval = $LANG_DQ['updcfgerror'];
        COM_errorLog("An error occured while updating the configuration",1);
    } else {
        $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['defaultcfg'] . "</p>";
    }
    return $retval;
}


function DQ_adminList()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
    global $_CONF_DQ, $LANG_DQ;

    USES_lib_admin();

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
        array('url' => $_CONF['site_admin_url'] . '/plugins/' . $_CONF_DQ['pi_name'] . '/index.php?mode=edit',
              'text' => $LANG_DQ['newquote'])
    );

    $defsort_arr = array('field' => 'dt', 'direction' => 'desc');

    $menu_arr[] = array('url' => $_CONF['site_admin_url'],
                        'text' => $LANG_ADMIN['admin_home']);

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

    $retval .= ADMIN_list('dailyquote', 'plugin_getListField_dailyquote', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}

/**
*   MAIN
*/
// If plugin is installed but not enabled, display an error and exit gracefully
if (!in_array($_CONF_DQ['pi_name'], $_PLUGINS)) {
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
case 'edit':
    if ($q_id != '') {
        $result = DB_query("SELECT * from {$_TABLES['dailyquote_quotes']}
                WHERE ID='$q_id'");
        if ($result && DB_numRows($result) == 1) {
            $A = DB_fetchArray($result);
        }
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

case $LANG_ADMIN['save']:
case $LANG12[8]:
    if ($q_id != '') {
        DQ_updateQuote($_POST);
    }
    $content .= DQ_adminList();
    break;

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

if (isset($_POST['submit'])){
    if ($_POST['submit'] == $LANG_DQ['update']){
        $display .= update_config();
    } elseif ($_POST['submit'] == $LANG_DQ['default']){
        $display .= default_cfg();
    }
}
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
