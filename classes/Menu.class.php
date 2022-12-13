<?php
/**
 * Class to provide admin and user-facing menus.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.1
 * @since       v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote;

USES_lib_admin();

/**
 * Class to provide admin and user-facing menus.
 * @package photocomp
 */
class Menu
{

    /**
     * Create the Administrative menu.
     *
     * @param   string  $view       Current view
     * @return  string      HTML for menu
     */
    public static function Admin(string $view='') : string
    {
        global $_CONF, $LANG_ADMIN, $LANG_DQ;

        $menu_arr = array();

        if (isset($LANG_DQ['hlp_admin_' . $view])) {
            $hlp_text = $LANG_DQ['hlp_admin_' . $view];
        } else {
            $hlp_text = $LANG_DQ['hlp_admin_dailyquote'];
        }

        $menu_arr = array(
            array(
                'text' => $LANG_DQ['glsearchlabel'],
                'url' => DQ_ADMIN_URL . '/index.php?quotes=x',
                'active' => $view == 'quotes',
            ),
            array(
                'text' => $LANG_DQ['manage_cats'],
                'url' => DQ_ADMIN_URL . '/index.php?categories=x',
                'active' => $view == 'categories',
            ),
            array(
                'url' => DQ_ADMIN_URL . '/index.php?batchform=x',
                'text' => $LANG_DQ['batchaddlink'],
                'active' => $view == 'batchform',
            ),
            array(
                'url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home'],
            ),
        );
        return ADMIN_createMenu(
            $menu_arr,
            $hlp_text,
            plugin_geticon_dailyquote()
        );
    }

    
    /**
     * Display the site header, with or without blocks according to configuration.
     *
     * @param   string  $title  Title to put in header
     * @param   string  $meta   Optional header code
     * @return  string          HTML for site header, from COM_siteHeader()
     */
    public static function siteHeader(string $title='', string $meta='') : string
    {
        global $_CONF_DQ;

        $retval = '';

        switch($_CONF_DQ['displayblocks']) {
        case 2:     // right only
        case 0:     // none
            $retval .= COM_siteHeader('none', $title, $meta);
            break;
        case 1:     // left only
        case 3:     // both
        default :
            $retval .= COM_siteHeader('menu', $title, $meta);
            break;
        }
        return $retval;
    }


    /**
     * Display the site footer, with or without blocks as configured.
     *
     * @return  string  HTML for site footer, from COM_siteFooter()
     */
    public static function siteFooter() : string
    {
        global $_CONF_DQ;

        $retval = '';

        switch($_CONF_DQ['displayblocks']) {
        case 2 : // right only
        case 3 : // left and right
            $retval .= COM_siteFooter(true);
            break;
        case 0: // none
        case 1: // left only
            $retval .= COM_siteFooter();
            break;
        }
        return $retval;
    }

}
