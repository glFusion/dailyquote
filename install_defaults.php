<?php
/**
 * Install configuration items for the Daily Quote plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}

/** @var global config data */
global $dailyquoteConfigData;
$dailyquoteConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'indexdisplim',
        'default_value' => 10,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'searchdisplim',
        'default_value' => 50,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'queue',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'submit_grp',
        'default_value' => 13,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'google_link',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 60,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'google_url',
        'default_value' => 'https://www.google.com/search?hl=%s&q=%s',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 70,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'whatsnew',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 80,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'whatsnewdays',
        'default_value' => 14,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 90,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'email_admin',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 9,
        'sort' => 100,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'displayblocks',
        'default_value' => 3,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 13,
        'sort' => 110,
        'set' => true,
        'group' => 'dailyquote',
    ),

    array(
        'name' => 'fs_cblock',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 10,
        'set' => true,
        'group' => 'dailyquote',
    ),
e   array(
        'name' => 'cb_pos',
        'default_value' => 2,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 5,
        'sort' => 20,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'cb_home',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'dailyquote',
    ),
    array(
        'name' => 'cb_replhome',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'dailyquote',
    ),
);

/**
 * Initialize the DailyQuote plugin configuration.
 *
 * @param   integer $group_id   Admin Group ID (not used)
 * @return  boolean             True
 */
function plugin_initconfig_dailyquote($group_id = 0)
{
    global $dailyquoteConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('dailyquote')) {
        USES_lib_install();
        foreach ($dailyquoteConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        glFusion\Log\Log::write('system', Log::ERROR, 'initconfig error: DailyQuote config group already exists');
    }
    return true;
}
