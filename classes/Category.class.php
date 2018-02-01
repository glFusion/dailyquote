<?php
/**
*   Class to handle quote categories
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace DailyQuote;

/**
*   Define a class to deal with quote categories
*   @package dailyquote
*/
class Category
{
    /** Category ID
    *   @var integer */
    public $id;

    /** Category Name
    *   @var string */
    public $name;

    /** Status.  1=enabled, 0=disabled
    *   @var boolean */
    public $enabled;

 
    /**
    *   Constructor
    *
    *   @param  string  $id     Category ID to retrieve, blank for new record
    */
    public function __construct($id=0)
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
    *   Read a category record from the database
    *
    *   @param  string  $id     Category ID to read (required)
    */
    public function Read($id)
    {
        global $_TABLES;

        $res = DB_query("SELECT * FROM {$_TABLES['dailyquote_cat']}
                WHERE id='".(int)$id."'");
        if ($res) {
            $A = DB_fetchArray($res, false);
            $this->setVars($A);
            return true;
        }
        return false;
    }


    /**
    *   Set the category variables from the supplied array.
    *   The array may be from a form ($_POST) or database record
    *
    *   @param  array   $A  Array of values
    */
    public function setVars($A)
    {
        if (!is_array($A))
            return;

        $this->id = (int)$A['id'];
        $this->name = $A['name'];
        $this->enabled = $A['enabled'] == 1 ? 1 : 0;
    }


    /**
    *   Update the 'enabled' value for a category.
    *
    *   @param  integer $newval     New value to set (1 or 0)
    *   @param  string  $id         Category ID.
    */
    public static function toggleEnabled($newval, $id)
    {
        global $_TABLES;

        $newval = $newval == 0 ? 0 : 1;
        DB_change($_TABLES['dailyquote_cat'],
                'enabled', $newval,
                'id', (int)$id);
    }


    /**
    *   Delete a category.
    *
    *   @param  string  $bid    Optional category ID to delete
    */
    public static function Delete($id)
    {
        global $_TABLES;

        $id = (int)$id;

        // Can't delete category 1
        if ($id == 1) return;

        DB_delete($_TABLES['dailyquote_cat'],
            'id', $id);

        // Also delete from lookup table
        DB_delete($_TABLES['dailyquote_quoteXcat'],
            'cid', $id);
    }


    /**
    *   Save the current category object using the supplied values.
    *
    *   @param  array   $A  Array of values from $_POST or database
    *   @return string      Empty string on success, error message on failure
    */
    public function Save($A = array())
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $LANG_DQ, $_CONF_DQ;

        if (is_array($A) && !empty($A))
            $this->setVars($A);

        // Determine if this is an INSERT or UPDATE
        if ($this->id == 0) {
            $sql = "INSERT INTO {$_TABLES['dailyquote_cat']}
                    (name, enabled)
                VALUES (
                    '" . DB_escapeString($this->name) . "',
                    1)";
        } else {
            $sql = "UPDATE {$_TABLES['dailyquote_cat']} SET
                        name = '" . DB_escapeString($this->name). "',
                        enabled = {$this->enabled}
                    WHERE id = {$this->id}";
        }
        $res = DB_query($sql);
        if (DB_error()) {
            return $LANG_DQ['err_saving_cat'];
        } else {
            return '';
        }
    }


    /**
    *   Administrator menu for categories
    *
    *   @return string      HTML for menu block
    */
    public function AdminMenu()
    {
        global $_CONF, $LANG_ADMIN, $LANG_DQ;

        USES_lib_admin();

        $menu_arr = array (
            array('url' => $_CONF['site_admin_url'],
                    'text' => $LANG_ADMIN['admin_home']),
            array('url' => DQ_ADMIN_URL . '/index.php?editcat=x',
                    'text' => 'New Category'),
            array('url' => DQ_ADMIN_URL,
                  'text' => $LANG_DQ['user_menu2']),
        );
        return $menu_arr;
    }

 
    /**
    *   Create an admin list of quotes
    *
    *   @return string  HTML for list
    */
    public static function AdminList()
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
        global $_CONF_DQ, $LANG_DQ;

        $header_arr = array(      # display 'text' and use table field 'field'
            array('text' => $LANG_ADMIN['edit'], 'field' => 'edit', 'sort' => false),
            array('field' => 'enabled', 
                'text' => $LANG_DQ['enabled'], 'sort' => false),
            array('text' => 'Category ID', 'field' => 'id', 'sort' => true),
            array('text' => 'Category Name', 'field' => 'name', 'sort' => true),
            array('text' => $LANG_ADMIN['delete'], 'field' => 'delete',
                'sort' => false),
        );

        $defsort_arr = array('field' => 'name', 'direction' => 'desc');

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
        $form_arr = array();
        return ADMIN_list('dailyquote', __NAMESPACE__ . '\\cat_getListField', $header_arr,
                        $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);
    }


    /**
    *   Creates a form for editing or creating new categories
    *
    *   @return string      HTML for the form
    */
    public function EditForm()
    {
        global $_CONF, $LANG_DQ, $_CONF_DQ;

        $retval = '';

        $T = new \Template(DQ_PI_PATH . '/templates');
        if ($_CONF_DQ['_is_uikit']) {
            $T->set_file('page', 'catform.uikit.thtml');
        } else {
            $T->set_file('page', 'catform.thtml');
        }
        $T->set_var(array(
            'name'      => $this->name,
            'id'        => $this->id,
            'chk'       => ($this->enabled == 1 || $this->id == 0) ? 
                            'checked="checked"' : '',
            'cancel_url' => DQ_ADMIN_URL . '/index.php?categories',
            'show_delbtn' => $this->id > 1 ? 'true' : '',
        ));
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }

}   // class Category


/**
*   Display a single field in the category admin list
*
*   @param  string  $fieldname  Name of field
*   @param  mixed   $fieldvalue Value of field
*   @param  array   $A          Array of all fields and values
*   @param  array   $icon_arr   Array of standard icons
*   @return string              HTML to properly display field value
*/
function cat_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_DQ, $_CONF_DQ, $LANG_ADMIN;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        if ($_CONF_DQ['_is_uikit']) {
            $retval .= COM_createLink('',
                DQ_ADMIN_URL . "/index.php?editcat=x&amp;id={$A['id']}",
                array('class' => 'uk-icon uk-icon-edit')
            );
        } else {
            $retval .= COM_createLink($icon_arr['edit'],
                DQ_ADMIN_URL . "/index.php?editcat=x&amp;id={$A['id']}"
            );
        }
        break;

    case 'enabled':
        $value = $fieldvalue == 1 ? 1 : 0;
        $chk = $fieldvalue == 1 ? ' checked="checked" ' : '';
        $retval .= '<input type="checkbox" id="togena' . $A['id'] . '"' .
            $chk . 'onclick=\'DQ_toggleEnabled(this, "' . $A['id'] . 
                '", "category");\' />';
        break;

    case 'delete':
        if ($A['id'] > 1) {
            if ($_CONF_DQ['_is_uikit']) {
                $retval = COM_createLink('',
                    DQ_ADMIN_URL . '/index.php?delcat=x&id=' . $A['id'],
                    array(
                        'class' => 'uk-icon uk-icon-trash dq-icon-danger',
                        'onclick' => 'return confirm(\'' . $LANG_DQ['confirm_delitem'] . '\');',
                        'title' => $LANG_ADMIN['delete'],
                    )
                );
            } else {
                $retval .= COM_createLink(COM_createImage(
                    $_CONF['layout_url'] . '/images/admin/delete.png',
                    $LANG_ADMIN['delete'],
                    array('class'=>'gl_mootip',
                    'onclick'=>'return confirm(\'' . 
                                $LANG_DQ['confirm_delitem'] .'\');',
                    'title' => $LANG_ACCESS['delete'],
                    )),
                    DQ_ADMIN_URL . '/index.php?delete=category&id=' . $A['id']
                );
            }
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
