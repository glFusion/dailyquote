<?php
//  $Id: quote.class.php 17 2009-09-07 20:26:13Z root $
/**
*   Class to handle quote categories
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*   GNU Public License v2 or later
*   @filesource
*/

/**
*   Define a class to deal with banners
*   @package banner
*/
class Category
{
    /** Category ID
     *  @var integer */
    var $id;

    /** Category Name
     *  @var string */
    var $name;

    /** Status.  1=enabled, 0=disabled
     *  @var boolean */
    var $enabled;

  
    /**
     *  Constructor
     *  @param string $bid Banner ID to retrieve, blank for empty class
     */
    function Category($id='')
    {
        global $_CONF, $_CONF_DQ;

        $id = (int)$id;
        if ($id > 0) {
            $this->Read($id);
        } else {
            $this->id = 0;
        }

    }


    /**
     *  Read a banner record from the database
     *  @param  string  $bid    Banner ID to read (required)
     */
    function Read($bid)
    {
        global $_TABLES;
        $A = DB_fetchArray(DB_query("
            SELECT * FROM {$_TABLES['dailyquote_cat']}
            WHERE id='".addslashes($id)."'"));
        $this->setVars($A);
    }


    /**
     *  Set the banner variables from the supplied array.
     *  The array may be from a form ($_POST) or database record
     *  @param  array   $A  Array of values
     */
    function setVars($A)
    {
        if (!is_array($A))
            return;

        $this->id = (int)$A['id'];
        $this->name = $A['name'];
        $this->enabled = $A['enabled'] == 1 ? 1 : 0;

    }


    /**
     *  Update the 'enabled' value for a banner ad.
     *  @param  integer $newval     New value to set (1 or 0)
     *  @param  string  $bid        Optional ad ID.  Current object if blank
     */
    function toggleEnabled($newval, $id='')
    {
        global $_TABLES;

        if ($id == '') {
            if (is_object($this)) {
                $id = $this->id;
                if ($this->Access() < 3)
                    return;
            } else {
                return;
            }
        }

        $newval = $newval == 0 ? 0 : 1;
        DB_change($_TABLES['dailyquote_cat'],
                'enabled', $newval,
                'id', addslashes(trim($id)));
    }


    /**
     *  Delete a category.
     *  Deletes the supplied quote ID if not empty, otherwise
     *  deletes the current object
     *  @param  string  $bid    Optional banner ID to delete
     */
    function Delete($id='')
    {
        global $_TABLES;

        if ($id == '') {
            if (is_object($this)) {
                $id = $this->id;
            } else {
                return;
            }
        }

        if (!DailyQuote::hasAccess(3))
            return;

        $id = COM_sanitizeID($id, false);
        DB_delete($_TABLES['dailyquote_cat'],
            'id', $id);

        // Also delete from lookup table
        DB_delete($_TABLES['dailyquote_lookup'],
            'cid', $id);
    }


    /**
     *  Returns the current user's access level to this banner
     *  @return integer     User's access level (1 - 3)
     */
    function Access()
    {
        global $_USER;

        if (SEC_hasRights('dailyquote.edit'))
            return 3;
        else
            return 2;
    }


    /**
     *  Determines whether the current user has a given level of access
     *  to this banner object.
     *  @see    Access()
     *  @param  integer $level  Minimum access level required
     *  @return boolean     True if user has access >= level, false otherwise
     */
    function hasAccess($level=3)
    {
        if (DailyQuote::Access() < $level) {
            return false;
        } else {
            return true;
        }
    }


    /**
     *  Save the current quote object using the supplied values.
     *  @param  array   $A  Array of values from $_POST or database
     */
    function Save($A = array())
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $LANG_DQ, $_CONF_DQ;

        if (is_array($A) && !empty($A))
            $this->setVars($A);

        $access = $this->hasAccess(3);
        if ($access < 3) {
            COM_errorLog("User {$_USER['username']} tried to illegally submit or edit quote {$this->id}.");
            return COM_showMessageText($MESSAGE[31], $MESSAGE[30]);
        }

        // Determine if this is an INSERT or UPDATE
        if ($this->id == 0) {
            $sql = "INSERT INTO {$_TABLES['dailyquote_cat']}
                    (name, enabled)
                VALUES (
                    '" . addslashes($this->name) . "',
                    1)";
        } else {
            $sql = "UPDATE {$_TABLES['dailyquote_cat']}
                SET
                    name = '" . addslashes($this->name). "',
                    enabled = " . (int)$this->enabled . "
                WHERE
                    id = " . $this->id;
        }
        //echo $sql;die;
        DB_query($sql);

        return '';

    }


    /**
    *   Administrator menu for categories
    *   @return string      HTML for menu block
    */
    function AdminMenu()
    {
        global $_CONF, $LANG_ADMIN, $LANG_DQ;

        USES_lib_admin();

        $menu_arr = array (
            array('url' => $_CONF['site_admin_url'],
                    'text' => $LANG_ADMIN['admin_home']),
            array('url' => DQ_ADMIN_URL . '/index.php?mode=editcategory',
                    'text' => 'New Category'),
            array('url' => DQ_ADMIN_URL,
                  'text' => $LANG_DQ['user_menu2']),
        );
        return $menu_arr;
    }

 
    /**
    *   Create an admin list of quotes
    *   @return string  HTML for list
    */
    function AdminList()
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
        global $_CONF_DQ, $LANG_DQ;

        $retval = '';

        $header_arr = array(      # display 'text' and use table field 'field'
            array('text' => $LANG_ADMIN['edit'], 'field' => 'edit', 'sort' => false),
            array('text' => 'Category ID', 'field' => 'id', 'sort' => true),
            array('text' => 'Category Name', 'field' => 'name', 'sort' => true),
        );

        $defsort_arr = array('field' => 'name', 'direction' => 'desc');

        $menu_arr = Category::AdminMenu();
        $retval .= COM_startBlock('WhereAmI', '', 
            COM_getBlockTemplate('_admin_block', 'header'));

        $retval .= ADMIN_createMenu($menu_arr, $LANG_DQ['admin_hdr'], 
            plugin_geticon_dailyquote());

        $text_arr = array(
            'has_extras' => true,
            'form_url' => DQ_ADMIN_URL . '/index.php?mode=categories'
        );

        $query_arr = array('table' => 'dailyquote_cat',
            'sql' => "SELECT * FROM {$_TABLES['dailyquote_cat']} ",
            'query_fields' => array('name'),
            'default_filter' => 'WHERE 1=1'
            //'default_filter' => COM_getPermSql ()
        );

        $retval .= ADMIN_list('dailyquote', 'DQ_cat_getListField', $header_arr,
                        $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

        return $retval;
    }


    /**
    *   Creates a form for editing or creating new categories
    *   @return string      HTML for the form
    */
    function EditForm()
    {
        global $_CONF, $LANG_DQ;

        $retval = '';

        $menu_arr = $this->AdminMenu();
        $menu_arr[] = array('url'=> DQ_ADMIN_URL . '/index.php?mode=categories',
                'text' => 'Categories');

        $retval .= COM_startBlock('WhereAmI', '', 
            COM_getBlockTemplate('_admin_block', 'header'));

        $retval .= ADMIN_createMenu($menu_arr, $LANG_DQ['admin_hdr'], 
            plugin_geticon_dailyquote());
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

        $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
        $T->set_file('page', 'catform.thtml');
        $T->set_var(array(
            'name'      => $this->name,
            'id'        => $this->id,
            'chk'       => ($this->enabled == 1 || $this->id == '') ? 
                            ' checked ' : '',
        ));
        $T->parse('output','page');

        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }

        
}   // class Category



/**
*   Display a single field in the category admin list
*   @param  string  $fieldname  Name of field
*   @param  mixed   $fieldvalue Value of field
*   @param  array   $A          Array of all fields and values
*   @param  array   $icon_arr   Array of standard icons
*   @return string              HTML to properly display field value
*/
function DQ_cat_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_DQ, $_CONF_DQ;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval .= COM_createLink(
            $icon_arr['edit'],
            DQ_ADMIN_URL . "/index.php?mode=editcategory&amp;id={$A['id']}"
        );
        if ($A['enabled'] == 1) {
            $ena_icon = 'on.png';
            $enabled = 0;
        } else {
            $ena_icon = 'off.png';
            $enabled = 1;
        }
        $retval .= "<span id=togena{$A['id']}>\n" .
                "<img src=\"{$_CONF['site_url']}/{$_CONF_DQ['pi_name']}" . 
                    "/images/{$ena_icon}\" ".
                "onclick='DQ_toggleEnabled({$enabled}, \"{$A['id']}\", ".
                "\"category\", \"{$_CONF['site_url']}\");'>\n" .
                "</span>\n";
        if ($A['id'] != 1) {
            // Cannot delete category 1 - default category.
            $retval .= COM_createLink(COM_createImage(
                $_CONF['site_url'].'/dailyquote/images/deleteitem.png',
                'Delete this quote',
                array('class'=>'gl_mootip',
                'onclick'=>'return confirm(\'Do you really want to delete this item?\');',
                'title' => 'Delete this quote',
                )),
                DQ_ADMIN_URL . '/index.php?mode=deletecat&id=' . $A['id']
            );
        }
        break;
    case 'name':
        $retval = stripslashes($A['name']);
        break;
    default:
        $retval = $fieldvalue;
        break;
    }

    return $retval;
}


?>
