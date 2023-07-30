<?php
/**
 * Administrative AJAX functions for the Daily Quote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2023 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only
if (!SEC_hasRights('dailyquote.edit')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the dailyquote AJAX functions.");
    exit;
}

use DailyQuote\Models\Request;
$Request = Request::getInstance();
if (!$Request->isAjax()) {
    exit;
}

$action = $Request->getString('action');
$component = $Request->getString('component');
$type = $Request->getString('type');
$result = array();

switch ($action) {
case 'toggle':
    $oldval = $Request->getInt('oldval');
    $id = $Request->getInt('id');
    switch ($component) {
    case 'quote':
        $newval = DailyQuote\Quote::toggle($oldval, $id, $type);
        break;
    case 'category':
        $newval = DailyQuote\Category::toggleEnabled($oldval, $id);
        break;
     default:
        exit;
    }

    // Common output for all toggle functions.
    $result = array(
        'id'    => $Request->getInt('id'),
        'type'  => $Request->getString('type'),
        'component' => $Request->getString('component'),
        'newval'    => $newval,
        'statusMessage' => $newval != $Request->getInt('oldval') ?
            $LANG_DQ['msg_updated'] : $LANG_DQ['msg_nochange'],
        //'title' => $title,
    );
    break;
}

$result = json_encode($result);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
// A date in the past to force no caching
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo $result;
