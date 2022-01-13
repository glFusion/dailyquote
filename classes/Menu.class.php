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
     * Get the submission URL, optionally for a specific event.
     *
     * @param   integer $ev_id      Optional event ID
     * @return  string      URL to submission form
     */
    public static function getSubmissionUrl($ev_id = 0)
    {
        $url = PHOTOCOMP_URL . '/index.php?view=submit';
        if ($ev_id > 0) {
            $url .= '&eventID=' . $ev_id;
        }
        return $url;
    }


    /**
     * Get the URL to view entries, optionally for a specific event.
     *
     * @param   integer $ev_id      Optional event ID
     * @return  string      URL to entry view screen
     */
    public static function getEntryviewUrl($ev_id = 0)
    {
        $url = PHOTOCOMP_URL . '/index.php?view=entries';
        if ($ev_id > 0) {
            $url .= '&eventID=' . $ev_id;
        }
        return $url;
    }


    /**
     * Returns the user-facing main menu.
     *
     * @param   string  $view       The menu option to set as selected
     * @param   integer $eventid    Event ID currently selected
     * @return  array       Menu array for ppNavBar()
     */
    public static function User($view='', $eventid=0)
    {
        global $LANG_PHOTO, $_TABLES, $_PHOTO_CONF, $_USER;

        USES_lib_admin();

        // Add the currently selected event for convenience.
        $url_suf = $eventid > 0 ? '&amp;eventID=' . $eventid : '';

        $menu_arr = array(
            array(
                'url' => self::getSubmissionUrl($eventid),
                'text' => $LANG_PHOTO['submit'],
                'active' => $view == 'submit' ? true : false,
            ),
        );

        // Only logged-in users can see "My Entries"
        if (!COM_isAnonUser()) {
            $menu_arr[] = array(
                'url' => self::getEntryviewUrl($eventid),
                'text' => $LANG_PHOTO['my_entries'],
                'active' => $view == 'entries' ? true : false,
            );
            // Add tab for payment status, if Shop integration is enabled
            if (PHC_shopEnabled()) {
                $menu_arr[] = array(
                    'url' => PHOTOCOMP_URL . '/index.php?view=fees',
                    'text' => $LANG_PHOTO['mnu_fees'],
                    'active' => $view == 'fees' ? true : false,
                );
            }
            // Add a tab for judging events, if applicable
            if (DB_count($_TABLES['photocomp_judgeready'], 'uid', $_USER['uid']) > 0) {
                $menu_arr[] = array(
                    'url' => self::getJudgingUrl(),
                    'text' => $LANG_PHOTO['judge'],
                    'active' => $view == 'judge' ? true : false,
                );
            }
        }
        if ($_PHOTO_CONF['helplink_entryform'] != '') {
            $menu_arr[] = array(
                'url' => $_PHOTO_CONF['helplink_entryform'],
                'text' => $LANG_PHOTO['help'],
            );
        }
        return self::ADMIN_createMenu($menu_arr, self::_getMenuText($view, 'user'));
    }


    /**
     * Create the Administrative menu.
     *
     * @param   string  $view       Current view
     * @return  string      HTML for menu
     */
    public static function Admin($view='')
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
     * Get the main plugin header and navbar menu.
     *
     * @param   string  $mnu_select Current menu selection
     * @return  string      HTML for header and navbar
     */
    public static function mainHeader($mnu_select = '')
    {
        global $_PHOTO_CONF, $LANG_PHOTO, $_CONF;

        $retval = '';
        $txt = LGLIB_getVar($LANG_PHOTO, 'hdr_' . $mnu_select, 'string', $LANG_PHOTO['plugin']);

        $T = new \Template($_PHOTO_CONF['path_template']);
        $T->set_file('page', 'index.thtml');
        $T->set_var(array(
            'header'    => $txt,
            'plugin'    => $_PHOTO_CONF['pi_name'],
            'pi_url'    => PHOTOCOMP_URL,
            'pi_icon'   => plugin_geticon_photocomp(),
            'isAdmin'   => plugin_ismoderator_photocomp(),
            'pi_admin_url' => PHOTOCOMP_ADMIN_URL . '/index.php',
        ) );
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));

        $eventid = LGLIB_getVar($_GET, 'eventID', 'integer');
        $retval .= self::User($mnu_select, $eventid);
        return $retval;
    }


    /**
     * Show the site header, with or without left blocks according to config.
     *
     * @since   v1.1.4
     * @uses    COM_siteHeader()
     * @param   string  $subject    Text for page title (ad title, etc)
     * @param   string  $meta       Other meta info
     * @param   boolean $blocks     True to show blocks according to config
     * @return  string              HTML for site header
     */
    public static function siteHeader($subject='', $meta='', $blocks=true)
    {
        global $_PHOTO_CONF, $LANG_PHOTO;

        $retval = '';

        if ($blocks) {
            $blocks = $_PHOTO_CONF['displayblocks'];
        } else {
            $blocks = 0;
        }

        switch($blocks) {
        case 2:     // right only
        case 0:     // none
            $retval .= COM_siteHeader('none', $subject, $meta);
            break;

        case 1:     // left only
        case 3:     // both
        default :
            $retval .= COM_siteHeader('menu', $subject, $meta);
            break;
        }
        return $retval;
    }


    /**
     * Show the site footer, with or without right blocks according to config.
     *
     * @since   v1.0.2
     * @uses    COM_siteFooter()
     * @param   boolean $blocks     True to show blocks according to config
     * @return  string          HTML for site header
     */
    public static function siteFooter($blocks=true)
    {
        global $_PHOTO_CONF;

        $retval = '';

        if ($blocks) {
            $blocks = $_PHOTO_CONF['displayblocks'];
        } else {
            $blocks = 0;
        }
        switch($blocks) {
        case 2 : // right only
        case 3 : // left and right
            $retval .= COM_siteFooter(true);
            break;

        case 0: // none
        case 1: // left only
        default :
            $retval .= COM_siteFooter();
            break;
        }

        return $retval;
    }


    /**
     * Display the entry form and main menu.
     * Serves as the default home page and centerblock.
     *
     * @param   integer $entryID    Entry ID being edited
     * @param   integer $eventID    Event ID for the submission form
     * @return  string  HTML for entry form.
     */
    public static function homePage($entryID=0, $eventID=0)
    {
        $Form = new Forms\Entry($entryID, $eventID);
        return $Form->Render($_POST);
    }


    /**
     * Get the help text for admin and user menus.
     * Looks for language strings, returns an empty string if none found.
     *
     * @param   string  $view   Name of menu view/option, e.g. `entries`
     * @param   string  $type   Type of menu, either `user` or `admin`
     * @return  string          Help text to be added to menu
     */
    private static function _getMenuText($view, $type)
    {
        global $LANG_PHOTO;

        $type = $type == 'user' ? 'user' : 'admin';
        $keys = array(
            'instr_' . $type . '_' . $view,
            'instr_' . $view,
        );
        foreach ($keys as $key) {
            if (isset($LANG_PHOTO[$key])) {
                return $LANG_PHOTO[$key];
            }
        }
        return '';
    }

}

?>
