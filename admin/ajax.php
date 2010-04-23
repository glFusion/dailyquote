<?php
//  $Id: updatecatxml.php 101 2008-12-12 16:51:21Z root $
/**
 *  Common AJAX functions for the Daily Quote plugin.
 *
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
 *  @package    dailyquote
 *  @version    0.0.2
 *  @license    http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 *  @filesource
 */

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only
if (!SEC_hasRights('dailyquote.edit')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the dailyquote AJAX functions.");
    exit;
}

$base_url = $_CONF['site_url'];

switch ($_GET['action']) {
case 'toggleEnabled':
    $newval = $_REQUEST['newval'] == 1 ? 1 : 0;

    switch ($_GET['type']) {
    case 'quote':
        USES_dailyquote_class_quote();
        DailyQuote::toggleEnabled($newval, $_REQUEST['id']);
        break;

    case 'category':
        USES_dailyquote_class_category();
        Category::toggleEnabled($newval, $_REQUEST['id']);
        break;

     default:
        exit;
    }

    $img_url = $base_url . "/" . $_CONF_DQ['pi_name'] . "/images/";
    $img_url .= $newval == 1 ? 'on.png' : 'off.png';

    header('Content-Type: text/xml');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    echo '<?xml version="1.0" encoding="ISO-8859-1"?>
    <info>'. "\n";
    echo "<newval>$newval</newval>\n";
    echo "<id>{$_REQUEST['id']}</id>\n";
    echo "<type>{$_REQUEST['type']}</type>\n";
    echo "<imgurl>$img_url</imgurl>\n";
    echo "<baseurl>{$base_url}</baseurl>\n";
    echo "</info>\n";
    break;

}

?>
