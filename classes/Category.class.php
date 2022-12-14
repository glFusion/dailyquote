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
use glFusion\Database\Database;
use glFusion\Log\Log;
use glFusion\FieldList;
use DailyQuote\Models\DataArray;


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
            $this->setVars(new DataArray($id));
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

        try {
            $row = Database::getInstance()->conn->executeQuery(
                "SELECT * FROM {$_TABLES[self::$TABLE]} WHERE id = ?",
                array($id),
                array(Database::INTEGER)
            )->fetchAssociative();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $row = false;
        }

        if (is_array($row)) {
            $this->setVars(new DataArray($row));
            return true;
        } else {
            return false;
        }
    }


    /**
     * Set the category variables from the supplied array.
     * The array may be from a form ($_POST) or database record
     *
     * @param   array   $A  Array of values
     */
    public function setVars(DataArray $A) : self
    {
        $this->id = $A->getInt('id');
        $this->name = $A->getString('name');
        $this->enabled = $A->getInt('enabled');
        return $this;
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
                try {
                    $stmt = Database::getInstance()->conn->executeQuery(
                        "SELECT * FROM {$_TABLES[self::$TABLE]}"
                    );
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                    $stmt = false;
                }
                if ($stmt) {
                    while ($A = $stmt->fetchAssociative()) {
                        $Cats[$A['id']] = new self($A);
                    }
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
    public static function toggleEnabled(int $newval, int $id) : void
    {
        global $_TABLES;

        $newval = $newval == 0 ? 0 : 1;
        try {
            Database::getInstance()->conn->update(
                $_TABLES[self::$TABLE],
                array('enabled' => $newval),
                array('id' => $id),
                array(Database::INTEGER, Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
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

        $db = Database::getInstance();
        try {
            $db->conn->delete(
                $_TABLES[self::$TABLE],
                array('id' => $id),
                array(Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }

        // Also delete from lookup table
        try {
            $db->conn->delete(
                $_TABLES['dailyquote_quoteXcat'],
                array('cid' => $id),
                array(Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
    }


    /**
     * Save the current category object using the supplied values.
     *
     * @param   array   $A  Array of values from $_POST or database
     * @return  string      Empty string on success, error message on failure
     */
    public function Save(?DataArray $A=NULL) : string
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $_CONF_DQ;

        if (!empty($A)) {
            $this->setVars($A);
        }
        $db = Database::getInstance();

        try {
            // Determine if this is an INSERT or UPDATE
            if ($this->id == 0) {
                $db->conn->insert(
                    $_TABLES[self::$TABLE],
                    array('name' => $this->name, 'enabled' => $this->enabled),
                    array(Database::STRING, Database::INTEGER)
                );
            } else {
                $db->conn->update(
                    $_TABLES[self::$TABLE],
                    array(
                        'name' => $this->name,
                        'enabled' => $this->enabled,
                    ),
                    array('id' => $this->id),
                    array(Database::STRING, Database::INTEGER, Database::INTEGER)
                );
            }
            return '';
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return MO::_('There was an error updating the category.');
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
                'align' => 'center',
            ),
            array(
                'text' => MO::_('Enabled'),
                'field' => 'enabled',
                'sort' => false,
                'align' => 'center',
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
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'align' => 'center',
                'sort' => false,
            ),
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
            DQ_ADMIN_URL . '/index.php?editcat=0',
            array(
                'class' => 'uk-button uk-button-success',
            )
        );
        USES_lib_admin();
        $retval .= ADMIN_list(
            'dailyquote_admincats',
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
                DQ_ADMIN_URL . "/index.php?editcat={$A['id']}",
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
                $retval = FieldList::delete(array(
                    'delete_url' => DQ_ADMIN_URL . '/index.php?delcat=' . $A['id'],
                    'attr' => array(
                        'onclick' => 'return confirm(\'' . $LANG_DQ['confirm_delitem'] . '\');',
                        'title' => $LANG_ADMIN['delete'],
                    ),
                ) );
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


    public static function userList() : string
    {
        global $_TABLES, $_CONF;

        $retval = '';

        $sql = "SELECT DISTINCT id, name
            FROM {$_TABLES['dailyquote_cat']} c
            WHERE c.enabled='1'
            ORDER BY name ASC";
        try {
            $stmt = Database::getInstance()->conn->executeQuery($sql);
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $retval = 'An error occurred while retrieving category list.';
            return $retval;
        }

        // Display cats if any to display
        $T = new \Template(DQ_PI_PATH . '/templates');
        $T->set_file('page', 'dispcats.thtml');

        // display horizontal rows -- 3 cats per row
        $i = 0;
        $col = 3;
        while ($row = $stmt->fetchAssociative()) {
            $T->set_block('page', 'CatRow', 'cRow');
            $T->set_var(array(
                'pi_url'    => DQ_URL . '/index.php',
                'cat_id'    => $row['id'],
                'dispcat'   => $row['name'],
                'cell_width' => (int)(100 / $col),
            ) );

            // Determine if it's time for a new row
            $i++;
            if ($i % $col === 0) {
                $T->set_var('newrow', 'true');
            }
            $T->parse('cRow', 'CatRow', true);
        }

        if ($i > 0) {
            $T->parse('output', 'page');
            $retval .= $T->finish($T->get_var('output'));
        }

        return $retval;
    }

}

