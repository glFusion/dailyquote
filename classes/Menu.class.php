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
        global $_CONF;

        $menu_arr = array();

        $help = array(
            'dailyquote' => MO::_("To modify or delete a quotation, click on that item's edit icon below. To create a new quotation, click on \'New Quote\' above."),
            'categories' => MO::_('From this page you can edit and enable/disable categories.'),
            'batchform' => MO::_('Upload a text file containing quotes.'),
        );
        if (isset($help[$view])) {
            $hlp_text = $help[$view];
        } else {
            $hlp_text = $help['dailyquote'];
        }

        $menu_arr = array(
            array(
                'text' => MO::_('Quotes'),
                'url' => DQ_ADMIN_URL . '/index.php?quotes=x',
                'active' => $view == 'quotes',
            ),
            array(
                'text' => MO::_('Categories'),
                'url' => DQ_ADMIN_URL . '/index.php?categories=x',
                'active' => $view == 'categories',
            ),
            array(
                'text' => MO::_('Import Quotes'),
                'url' => DQ_ADMIN_URL . '/index.php?batchform=x',
                'active' => $view == 'batchform',
            ),
            array(
                'text' => MO::_('Admin Home'),
                'url' => $_CONF['site_admin_url'],
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
        $retval = '';

        switch(Config::get('displayblocks')) {
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
        $retval = '';

        switch(Config::get('displayblocks')) {
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
