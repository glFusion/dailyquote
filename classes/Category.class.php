<?php
/**
 * Class to handle quote categories.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote;

/**
 * Define a class to deal with quote categories.
 * @package dailyquote
 */
class Category
{
    /** Table key.
     * @var string */
    private static $TABLE = 'dailyquote_cat';

    /** Category ID.
     * @var integer */
    public $id = 0;

    /** Category Name.
     * @var string */
    public $name = '';

    /** Status.
     * 1=enabled, 0=disabled
     * @var boolean */
    public $enabled = 1;


    /**
     * Constructor.
     *
     * @param   string  $id     Category ID to retrieve, blank for new record
     */
    public function __construct($id=0)
    {
        global $_CONF, $_CONF_DQ;

        if (is_array($id)) {
            // record already read
            $this->setVars($id);
        } else {
            $id = (int)$id;
            if ($id > 0) {
                $this->Read($id);
            } else {
                $this->id = 0;
            }
        }
    }


    /**
     * Read a category record from the database.
     *
     * @param   string  $id     Category ID to read (required)
     * @return  boolean     True if found, False if not
     */
    public function Read(int $id) : bool
    {
        global $_TABLES;

        $res = DB_query("SELECT * FROM {$_TABLES[self::$TABLE]}
                WHERE id='".(int)$id."'");
        if ($res && DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
            $this->setVars($A);
            return true;
        }
        return false;
    }


    /**
     * Set the category variables from the supplied array.
     * The array may be from a form ($_POST) or database record
     *
     * @param   array   $A  Array of values
     */
    public function setVars(array $A) : self
    {
        if (is_array($A)) {
            $this->id = (int)$A['id'];
            $this->name = $A['name'];
            $this->enabled = isset($A['enabled']) && $A['enabled'] == 1 ? 1 : 0;
        }
    }


    /**
     * Get all categories into an array of objects.
     *
     * @return  array       Array of Category objects
     */
    public static function getAll() : array
    {
        global $_TABLES;
        static $Cats = NULL;

        if ($Cats === NULL) {
            $key = self::$TABLE . '_all';
            $Cats = Cache::get($key);
            if (!$Cats) {
                $Cats = array();
                $sql = "SELECT * FROM {$_TABLES[self::$TABLE]}";
                $res = DB_query($sql);
                while ($A = DB_fetchArray($res, false)) {
                    $Cats[$A['id']] = new self($A);
                }
                Cache::set($key, $Cats, array('categories'));
            }
        }
        return $Cats;
    }


    /**
     * Get the record ID for the category.
     *
     * @return  integer     DB record ID
     */
    public function getID() : int
    {
        return (int)$this->id;
    }


    /**
     * Get the category name.
     *
     * @return  string      Category name
     */
    public function getName() : string
    {
        return $this->name;
    }


    /**
     * Check if the category is enabled for use.
     *
     * @return  boolean     1 if enabled, 0 if disabled
     */
    public function isEnabled() : bool
    {
        return $this->enabled ? 1 : 0;
    }


    /**
     * Update the 'enabled' value for a category.
     *
     * @param   integer $newval     New value to set (1 or 0)
     * @param   string  $id         Category ID.
     */
    public static function toggleEnabled($newval, $id) : void
    {
        global $_TABLES;

        $newval = $newval == 0 ? 0 : 1;
        DB_change(
            $_TABLES[self::$TABLE],
            'enabled', $newval,
            'id', (int)$id
        );
    }


    /**
     * Delete a category.
     *
     * @param   string  $id     Category ID to delete
     */
    public static function Delete(int $id) : void
    {
        global $_TABLES;

        $id = (int)$id;

        // Can't delete category 1
        if ($id == 1) return;

        DB_delete($_TABLES[self::$TABLE], 'id', $id);

        // Also delete from lookup table
        DB_delete($_TABLES['dailyquote_quoteXcat'], 'cid', $id);
    }


    /**
     * Save the current category object using the supplied values.
     *
     * @param   array   $A  Array of values from $_POST or database
     * @return  string      Empty string on success, error message on failure
     */
    public function Save(?array $A = NULL) : string
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $_CONF_DQ;

        if (is_array($A) && !empty($A))
            $this->setVars($A);

        // Determine if this is an INSERT or UPDATE
        if ($this->id == 0) {
            $sql = "INSERT INTO {$_TABLES[self::$TABLE]}
                    (name, enabled)
                VALUES (
                    '" . DB_escapeString($this->name) . "',
                    1)";
        } else {
            $sql = "UPDATE {$_TABLES[self::$TABLE]} SET
                        name = '" . DB_escapeString($this->name). "',
                        enabled = {$this->enabled}
                    WHERE id = {$this->id}";
        }
        $res = DB_query($sql);
        if (DB_error()) {
            return MO::_('There was an error updating the category.');
        } else {
            return '';
        }
    }


    /**
     * Administrator menu for categories.
     *
     * @return  string      HTML for menu block
     */
    public function AdminMenu() : string
    {
        global $_CONF, $LANG_ADMIN;

        USES_lib_admin();

        $menu_arr = array (
            array(
                'text' => MO::_('Admin Home'),
                'url' => $_CONF['site_admin_url'],
            ),
            array(
                'text' => MO::_('New Category'),
                'url' => DQ_ADMIN_URL . '/index.php?editcat=x',
            ),
            array(
                'text' => MO::_('Manage Quotes'),
                'url' => DQ_ADMIN_URL,
            ),
        );
        return $menu_arr;
    }


    /**
     * Create an admin list of quotes.
     *
     * @return  string  HTML for list
     */
    public static function adminList() : string
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
        global $_CONF_DQ, $LANG_DQ;

        $header_arr = array(      # display 'text' and use table field 'field'
            array(
                'text' => MO::_('Edit'),
                'field' => 'edit',
                'sort' => false,
            ),
            array(
                'text' => MO::_('Enabled'),
                'field' => 'enabled',
                'sort' => false,
            ),
            array(
                'text' => 'Category ID',
                'field' => 'id',
                'sort' => true,
            ),
            array(
                'text' => 'Category Name',
                'field' => 'name',
                'sort' => true,
            ),
            array(
                'text' => $LANG_ADMIN['delete'], 'field' => 'delete',
                'sort' => false),
        );

        $defsort_arr = array('field' => 'name', 'direction' => 'desc');

        $text_arr = array(
            'has_extras' => true,
            'form_url' => DQ_ADMIN_URL . '/index.php?mode=categories'
        );

        $query_arr = array(
            'table' => self::$TABLE,
            'sql' => "SELECT * FROM {$_TABLES[self::$TABLE]} ",
            'query_fields' => array('name'),
            'default_filter' => 'WHERE 1=1'
            //'default_filter' => COM_getPermSql ()
        );
        $form_arr = array();
        $retval = COM_createLink(
            $LANG_DQ['newcat'],
            DQ_ADMIN_URL . '/index.php?editcat=x',
            array(
                'class' => 'uk-button uk-button-success',
            )
        );
        USES_lib_admin();
        $retval .= ADMIN_list(
            'dailyquote',
            array(__CLASS__, 'getListField'),
            $header_arr,
            $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr
        );
        return $retval;
    }


    /**
     * Creates a form for editing or creating new categories.
     *
     * @return  string      HTML for the form
     */
    public function EditForm() : string
    {
        global $_CONF, $LANG_DQ, $_CONF_DQ;

        $retval = '';

        $T = new \Template(DQ_PI_PATH . '/templates');
        $T->set_file('page', 'catform.thtml');
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


    /**
     * Display a single field in the category admin list.
     *
     * @param   string  $fieldname  Name of field
     * @param   mixed   $fieldvalue Value of field
     * @param   array   $A          Array of all fields and values
     * @param   array   $icon_arr   Array of standard icons
     * @return  string              HTML to properly display field value
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr) : string
    {
        global $_CONF, $LANG_ACCESS, $LANG_DQ, $_CONF_DQ, $LANG_ADMIN;

        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval .= COM_createLink(
                '',
                DQ_ADMIN_URL . "/index.php?editcat=x&amp;id={$A['id']}",
                array(
                    'class' => 'uk-icon uk-icon-edit',
                )
            );
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
                $retval = COM_createLink('',
                    DQ_ADMIN_URL . '/index.php?delcat=x&id=' . $A['id'],
                    array(
                        'class' => 'uk-icon uk-icon-trash dq-icon-danger',
                        'onclick' => 'return confirm(\'' . $LANG_DQ['confirm_delitem'] . '\');',
                        'title' => $LANG_ADMIN['delete'],
                    )
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

}

