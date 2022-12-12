<?php
/**
 * Class to handle quotes.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Define a class to deal with quotes.
 * @package dailyquote
 */
class Quote
{
    /** Quote ID.
     * @var string */
    private $quote_id = 0;

    /** Quote contents.
     * @var string */
    private $quote = '';

    /** Person being quoted.
     * @var string */
    private $quoted = '';

    /** Quote source (article, speech, etc.).
     * @var string */
    private $source = '';

    /** Quote date.
     * @var string */
    private $sourcedate = '';

    /** Quote title.
     * @var string */
    private $title = '';

    /** Quote date.
     * @var string */
    private $dt = '';

    /** ID of the submitting user.
     * @var integer */
    private $uid = 0;

    /** Enabled for display. Set after submission is approved.
     * @var boolean */
    private $enabled = 1;

    /** Flag indicating whether a submission has been approved.
     * @var boolean */
    private $approved = 0;

    /** Categories to which this quote belongs.
     * @var array */
    private $categories = array();

    /** Flag to indicate current user is an admin.
     *  @var boolean */
    private $isAdmin = 0;

    /** Array of Category objects.
     * @var object */
    private $Cats = array();


    /**
     * Constructor.
     *
     * @param   string  $id     Quote ID to retrieve, blank for empty object
     */
    public function __construct()
    {
        global $_USER;

        $this->uid = $_USER['uid'];
        $this->isAdmin = SEC_hasRights('dailyquote.admin');
    }


    public static function fromArray(array $A) : self
    {
        $Quote = new self;
        $Quote->setVars($A);
        return $Quote;
    }


    /**
     * Get the quote ID.
     *
     * @return  integer     Quote ID
     */
    public function getID() : int
    {
        return $this->quote_id;
    }


    /**
     * Get the quote title.
     *
     * @return  string      Quote title
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * Get the quote text.
     *
     * @return  string      Quote text
     */
    public function getQuote()
    {
        return $this->quote;
    }


    /**
     * Get the person quoted.
     *
     * @return  string      Person quoted.
     */
    public function getQuoted()
    {
        return $this->quoted;
    }


    /**
     * Get the quote source.
     *
     * @return  string      Quote source
     */
    public function getSource()
    {
        return $this->source;
    }


    /**
     * Get the source date.
     *
     * @return  string      Source date
     */
    public function getSourceDate()
    {
        return $this->sourcedate;
    }


    /**
     * Get the submitting user ID.
     *
     * @return  integer     User ID
     */
    public function getUid()
    {
        return (int)$this->uid;
    }


    /**
     * Check if this is a new or non-existant record.
     *
     * @return  boolean     True if not from the database
     */
    public function isNew()
    {
        return $this->quote_id == 0;
    }


    /**
     * Get the "enabled" status.
     *
     * @return  integer     1 if enabled, 0 if disabled
     */
    public function isEnabled()
    {
        return $this->enabled ? 1 : 0;
    }


    /**
     * Get the date of the quote
     *
     * @return  string      Quote date
     */
    public function getDate()
    {
        return $this->dt;
    }


    /**
     * Get the user display name for the quote.
     *
     * @return  string      User Name
     */
    public function getUsername()
    {
        global $_TABLES;
        static $users = array();

        $uid = $this->getUid();
        if ($uid < 1) $uid = 1;
        if (!isset($users[$uid])) {
            $users[$uid] = Database::getInstance()->getItem(
                $_TABLES['users'],
                'username',
                array('uid' => $uid)
            );
        }
        return $users[$uid];
    }


    /**
     * Set the table for later use.
     *
     * @param   string  $table  Table ID, e.g. 'quotes' or 'submission'
     * @return  object  $this
     */
    public function setTable($table = 'quotes')
    {
        global $_TABLES;

        switch ($table) {
        case 'quotes':
        case 'submission':
            $this->table_id = $table;
            $this->table = $_TABLES['dailyquote_' . $table];
            break;
        }
        return $this;
    }


    /**
     * Read a quote record from the database.
     * If no quote ID is specified, a random quote is read.
     *
     * @param   string  $qid    Optional quote ID to read
     */
    public function Read()
    {
        global $_TABLES;

        // Reset the categories for this quote
        $this->categories = array();

        $db = Database::getInstance();
        try {
            $row = $db->conn->executeQuery(
                "SELECT * FROM {$this->table} WHERE id = ?",
                array($this->quote_id),
                array(Database::INTEGER)
            )->fetchAssociative();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $row = false;
        }
        if (!is_array($row)) {
            return false;
        }
        $this->setVars($row);

        // Get the categories that this quote is in.
        try {
            $stmt = $db->conn->executeQuery(
                "SELECT cid FROM {$_TABLES['dailyquote_quoteXcat']} WHERE qid = ?",
                array($this->quote_id),
                array(Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $this->categories[] = (int)$A['cid'];
            }
        }
        return true;
    }


    public static function getCats($qid)
    {
        global $_TABLES;

        static $Cats = NULL;
        if ($Cats === NULL) {
            $Cats = Category::getAll();
        }
        $retval = array();
        try {
            $rows = Database::getInstance()->conn->executeQuery(
                "SELECT cid FROM {$_TABLES['dailyquote_quoteXcat']}
                WHERE qid = ?",
                array($qid),
                array(Database::STRING)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $rows = false;
        }

        if (is_array($rows)) {
            foreach ($rows as $A) {
                if (array_key_exists($A['cid'], $Cats)) {
                    $retval[$A['cid']] = $Cats[$A['cid']];
                }
            }
        }
        return $retval;
     }


    /**
     * Set the variables from the supplied array.
     * The array may be from a form ($_POST) or database record.
     *
     * @param   array   $A  Array of values
     * @return  object  $this
     */
    public function setVars($A)
    {
        if (!is_array($A)) {
            return;
        }

        if (isset($A['quote_id'])) {
            $this->quote_id = (int)$A['quote_id'];
        }
        $this->quote = $A['quote'];
        $this->quoted = $A['quoted'];
        $this->source = $A['source'];
        $this->sourcedate = $A['sourcedate'];
        $this->dt = isset($A['dt']) ? $A['dt'] : time();
        $this->title = $A['title'];
        $this->enabled = (isset($A['enabled']) && $A['enabled'] == 1) ? 1 : 0;
        $this->approved = (isset($A['approved']) && $A['approved'] == 1) ? 1 : 0;
        $this->uid = (int)$A['uid'];
        return $this;
    }


    /**
     * Update the 'enabled' value for a quote.
     * Only applies to the prod table
     *
     * @param   integer $newval     New value to set (1 or 0)
     * @param   string  $id         Quote ID
     * @return  integer     New value, or old value on error
     */
    public static function toggleEnabled(int $newval, string $id) : int
    {
        global $_TABLES;

        $newval = $newval == 0 ? 0 : 1;
        $oldval = $newval == 0 ? 1 : 0;
        try {
            Database::getInstance()->conn->update(
                $_TABLES['dailyquote_quotes'],
                array('enabled' => $newval),
                array('quote_id' => $id),
                array(Database::INTEGER, Database::STRING)
            );
            return $newval;
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return $oldval;
        }
    }


    /**
     * Delete a quote.
     * Deletes the supplied quote ID if not empty, otherwise
     * deletes the current object.
     *
     * @param   string  $id     Quote ID to delete
     */
    public static function Delete(int $id) : void
    {
        global $_TABLES;

        if (!self::hasAccess(3)) {
            return;
        }

        $id = COM_sanitizeID($id, false);
        $db = Database::getInstance();
        try {
            $db->conn->delete(
                $_TABLES['dailyquote_quotes'],
                array('quote_id' => $id),
                array(Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return;     // Don't continue to delete category references
        }
        try {
            $db->conn->delete(
                $_TABLES['dailyquote_quoteXcat'],
                array('qid' => $id),
                array(Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }

        PLG_itemDeleted($id, 'dailyquote');
    }


    /**
     * Returns the current user's access level to this quote.
     *
     * @param   boolean $new_item   True to check new item access, false for existing
     * @return  integer     User's access level (1 - 3)
     */
    public static function Access(bool $new_item = false) : int
    {
        global $_USER, $_CONF_DQ;

        if (SEC_hasRights('dailyquote.edit'))
            return 3;

        if ($new_item) {
            if (SEC_hasRights('dailyquote.submit')) {
                $access = 3;
            } elseif (COM_isAnonUser()) {
                $access = $_CONF_DQ['anonadd'] == 1 ? 3 : 0;
            } else {
                $access = $_CONF_DQ['loginadd'] == 1 ? 3 : 0;
            }
        } else {
            $access = 2;
        }

        return $access;
    }


    /**
     * Determines whether the current user has a given level of access.
     *
     * @see     Access()
     * @param   integer $level  Minimum access level required
     * @param   boolean $new_item   True is this is a new submission
     * @return  boolean     True if user has access >= level, false otherwise
     */
    public static function hasAccess(int $level=3, bool $new_item=false) : bool
    {
        if (self::Access($new_item) < $level) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Displays the quote editing form.
     *
     * @param   string  $mode   Editing mode (edit, submission, etc)
     * @param   array   $A      Provided form values, e.g. from previous $_POST
     * @return  string          HTML for the form.
     */
    public function Edit($mode='edit', $A=array())
    {
        global $_TABLES, $_CONF, $_USER, $LANG_DQ, $LANG_ADMIN, $_CONF_DQ,
            $LANG12, $_SYSTEM;

        $retval = '';
        if (!empty($A)) {
            // Form is being re-edited due to a previous error
            $this->setVars($A);
        }

        // Default save action based on table
        $hidden_vars = '';
        if ($this->isAdmin) {
            $action_url = DQ_ADMIN_URL . '/index.php';
        } else {
            $action_url = DQ_URL . '/index.php';
        }

        $cancel_url = $this->isAdmin ? DQ_ADMIN_URL . '/index.php?quotes' : $_CONF['site_url'];
        switch ($mode) {
        case 'edit':
            $saveoption = $LANG_ADMIN['save'];      // Save
            $cancel_url = $this->isAdmin ? DQ_ADMIN_URL . '/index.php' :
                $_CONF['site_url'];
            $saveaction = 'savequote';
            break;

        case 'submit':
        case $LANG12[8]:
            $saveoption = $LANG_ADMIN['save'];      // Save
            $saveaction = 'savesubmission';
            $hidden_vars= '<input type="hidden" name="type" value="dailyquote" />'
                .'<input type="hidden" name="mode" value="' . $LANG12[8].'" />';
            break;

        case 'moderate':
            $saveoption = $LANG_ADMIN['moderate'];  // Save & Approve
            $saveaction = 'savemoderation'; // override $saveaction
            $cancel_url = $_CONF['site_admin_url'] . '/moderation.php';
            break;
        }

        $T = new \Template(DQ_PI_PATH . '/templates');
        $T->set_file('page', 'editform.thtml');
        $T->set_var(array(
            'gltoken_name'  => CSRF_TOKEN,
            'gltoken'       => SEC_createToken(),
            'pi_name'       => $_CONF_DQ['pi_name'],
            'action_url'    => $action_url,
            'saveaction'    => $saveaction,
            'saveoption'    => $saveoption,
            'id'            => $this->quote_id,
            'uid'           => $this->uid,
            'quote'         => $this->quote,
            'quoted'        => $this->quoted,
            'title'         => $this->title,
            'quoteby'       => $this->quoted,
            'source'        => $this->source,
            'sourcedate'    => $this->sourcedate,
            'ena_chk'       => $this->enabled ? 'checked="checked"' : '',
            'is_admin'      => $this->isAdmin,
            'hidden_vars'   => $hidden_vars,
        ) );
        //retrieve categories from db if any and display
        if (!$result = DB_query(
            "SELECT id, name
            FROM {$_TABLES['dailyquote_cat']}
            WHERE enabled='1'
            ORDER BY name"
        ) ) {
            $errstatus = 1;
        } else {
            $numrows = DB_numRows($result);
        }

        // Display $colnum vertical columns of categories to check.
        // if you increase or decrease this number,
        // then you'll need to adjust the cell width in the addcol and
        // addcatcol.thtml files
        if ($numrows > 0) {
            $T->set_block('page', 'CatSelect', 'CS');
            while ($A = DB_fetchArray($result, false)) {
                $chk = in_array($A['id'], $this->categories) ? 'checked="checked"' : '';
                $T->set_var(array(
                    'cat_id'    => $A['id'],
                    'cat_name'  => $A['name'],
                    'cat_sel'   => $chk,
                ) );
                $T->parse('CS', 'CatSelect', true);
            }
        }
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Save the current quote object using the supplied values.
     *
     * @param   array   $A  Array of values from $_POST or database
     */
    public function Save($A) : string
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $LANG_DQ, $_CONF_DQ;

        if (is_array($A)) {
            $this->setVars($A);
        }

        if ($this->uid == '') {
            // this is new quote from admin, set default values
            $uid = $_USER['uid'];
        }

        $access = $this->hasAccess(3, $this->isNew());
        if (!$access) {
            COM_errorLog("User {$_USER['username']} tried to illegally submit or edit quote {$this->quote_id}.");
            return $MESSAGE[31];
        }

        $values = array(
            'quote' => self::_safeText($this->quote),
            'quoted' => self::_safeText($this->quoted),
            'title' => self::_safeText($this->title),
            'source' => self::_safeText($this->source),
            'sourcedate' => self::_safeText($this->sourcedate),
            'dt' => $this->dt,
            'enabled' => $this->isEnabled(),
            'uid' => $this->uid,
            'approved' => $this->approved,
        );
        $types = array(
            Database::STRING,
            Database::STRING,
            Database::STRING,
            Database::STRING,
            Database::STRING,
            Database::INTEGER,
            Database::INTEGER,
            Database::INTEGER,
        );
        $db = Database::getInstance();
        try {
            if ($this->isNew()) {
                $db->conn->insert(
                    $_TABLES['dailyquote_quotes'],
                    $values,
                    $types
                );
                $this->quote_id = $db->conn->lastInsertId();
            } else {
                $types[] = Database::INTEGER;   // for quote_id
                $db->conn->update(
                    $_TABLES['dailyquote_quotes'],
                    $values,
                    array('quote_id' => $this->quote_id),
                    $types
                );
            }
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return 'An error occurred inserting the quote';
        }

        // Delete all lookup records for this quote to make sure we
        // get rid of unused categories.
        try {
            $db->conn->delete(
                $_TABLES['dailyquote_quoteXcat'],
                array('qid' => $this->quote_id),
                array(Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            // just keep going
        }

        // Now, add records to the lookup table to link the categories
        // to the quote.  Only if bypassing the submission queue; if
        // the queue is used $catlist will be empty.
        if (
            !isset($A['categories']) ||
            !is_array($A['categories']) ||
            empty($A['categories'])
        ) {
            // Set to static Miscellaneous category.
            $A['categories'] = array(1 => 1);
        }
        $values = array();
        foreach($A['categories'] as $key => $dummy) {
            $key = (int)$key;
            $values[] = "('{$this->quote_id}', $key)";
        }
        if (!empty($values)) {
            $value_str = implode(',', $values);
            $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_quoteXcat']}
                    (qid, cid)
                VALUES $value_str";
            DB_query($sql);
        }
        Cache::clear();
        if ($this->approved && $this->enabled) {
            PLG_itemSaved($this->quote_id, 'dailyquote');
        }
        return '';
    }


    /**
     * Save a user submission.
     * Verifies access and sets the correct table, then calls Save() to
     * save the submission.
     *
     * @param   array   $A      $_POST array of data
     * @return  string      Error message or empty string on success
     */
    public function saveSubmission($A)
    {
        global $_CONF_DQ, $LANG_DQ, $_USER, $_CONF;

        if (SEC_hasRights('dailyquote.submit')) {
            $A['approved'] = 1;
            $email_admin = 0;
        } elseif ($_CONF_DQ['queue'] == 0) {
            // user has submit right or submission queue is not being used
            $A['approved'] = 1;
            $email_admin = $_CONF_DQ['email_admin'] == 2 ? 1 : 0;
        } elseif ((int)$_USER['uid'] > 1 && $_CONF_DQ['loginadd'] == 1) {
            // user must go through the submission queue
            $A['approved'] = 0;
            $email_admin = $_CONF_DQ['email_admin'] > 0 ? 1 : 0;
        }  else {
            return $LANG_DQ['access_denied'];
        }
        $A['dt'] = time();
        $msg = $this->Save($A);

        if ($msg == '') {
            // Send notification, if configured
            if ($email_admin == 1) {
                $T = new \Template(DQ_PI_PATH . '/templates');
                $T->set_file('msg', 'email_admin.thtml');
                $T->set_var(array(
                    'title'     => $A['title'],
                    'quote'     => $A['quote'],
                    'quoted'    => $A['quoted'],
                    'subm_by'   => COM_getDisplayName($_USER['uid']),
                ) );
                $T->parse('output','msg');
                COM_mail($_CONF['site_mail'],
                    $LANG_DQ['email_subject'],
                    $T->finish($T->get_var('output'))
                );
            }
        }
        return $msg;
    }


    /**
     * Retrieves a single quote. If $id is empty, a random quote is selected.
     *
     * @param   integer $qid    Optional quote specifier
     * @return  object          Quote object, NULL if error or not found
     */
    public static function getInstance(?int $qid=NULL) : self
    {
        global $_TABLES;

        $row = false;
        if ($qid > 0) {
            try {
                $row = Database::getInstance()->conn->executeQuery(
                    "SELECT * FROM {$_TABLES['dailyquote_quotes']} WHERE quote_id = ?",
                    array($qid),
                    array(Database::INTEGER)
                )->fetchAssociative();
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $row = false;
            }
        }
        if (is_array($row)) {
            return self::fromArray($row);
        } else {
            return new self;
        }
    }


    /**
     * Enclose the Quoted field in link tags for Google search.
     *
     * @param   string  $Quoted     Person quoted
     * @return  string              URL for google search, or 'unknown'
     */
    public static function GoogleLink($Quoted)
    {
        global $_CONF, $LANG_DQ, $_CONF_DQ;

        if ($Quoted == '') {
            $retval = $LANG_DQ['unknown'];
        } elseif (
            $_CONF_DQ['google_link'] == 0 ||
            !isset($_CONF_DQ['google_url']) ||
            empty($_CONF_DQ['google_url'])
        ) {
            //do if based on setting
            $retval = $Quoted;
        } else {
            $gname = urlencode(trim($Quoted));
            $retval = COM_createLink(
                $Quoted,
                sprintf($_CONF_DQ['google_url'], $_CONF['iso_lang'], $gname),
                array(
                    'target' => '_blank',
                    'rel' => 'nofollow',
                )
            );
        }
        return $retval;
    }


    /**
     * Sanitize text inputs. This is to sanitize text before saving to the DB.
     *
     * @param   string  $str    String to be sanitized
     * @return  string      Sanitized string
     */
    private static function _safeText($str)
    {
        //return htmlspecialchars(COM_checkWords($str),ENT_QUOTES,COM_getEncodingt());
        return COM_checkWords($str);
    }
    
    
    /**
     * Create an admin list of quotes.
     *
     * @return  string  HTML for list
     */
    public static function adminList()
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS;
        global $_CONF_DQ, $LANG_DQ;

        $retval = '';

        $header_arr = array(      # display 'text' and use table field 'field'
            array(
                'field' => 'edit',
                'text' => $LANG_ADMIN['edit'],
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'field' => 'enabled',
                'text' => $LANG_DQ['enabled'],
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'field' => 'quote_id',
                'text' => 'Quote ID',
                'sort' => true,
            ),
            array(
                'field' => 'dt',
                'text' => $LANG_DQ['date'],
                'sort' => true,
            ),
            array(
                'field' => 'quoted',
                'text' => $LANG_DQ['quoted'],
                'sort' => true,
            ),
            array(
                'field' => 'title',
                'text' => $LANG_DQ['title'],
                'sort' => true,
            ),
            array(
                'field' => 'quote',
                'text' => $LANG_DQ['quote'],
                'sort' => true,
            ),
            array(
                'field' => 'delete',
                'text' => $LANG_ADMIN['delete'],
                'sort' => false,
                'align' => 'center',
            ),
        );

        $defsort_arr = array('field' => 'dt', 'direction' => 'desc');
        $text_arr = array(
            'has_extras' => true,
            'form_url' => DQ_ADMIN_URL . '/index.php?type=quote',
        );
        $options = array('chkdelete' => 'true', 'chkfield' => 'quote_id');
        $query_arr = array(
            'table' => 'dailyquote',
            'sql' => "SELECT * FROM {$_TABLES['dailyquote_quotes']} ",
            'query_fields' => array('title', 'quotes', 'quoted'),
            'default_filter' => 'WHERE 1=1',
        );
        $form_arr = array();
        $retval = COM_createLink(
            $LANG_DQ['newquote'],
            DQ_ADMIN_URL . '/index.php?editquote=x',
            array(
                'class' => 'uk-button uk-button-success',
            )
        );
        USES_lib_admin();
        $retval .= ADMIN_list(
            'adminlist_dailyquote_quotes',
            array(__CLASS__, 'getListField'),
            $header_arr,
            $text_arr, $query_arr, $defsort_arr, '', '',
            $options, $form_arr
        );
        return $retval;
    }

    
    /**
     * Display a single formatted field in the admin quote list.
     *
     * @param   string  $fieldname  Name of the field
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Name->Value array of all fields
     * @param   array   $icon_arr   System icon array
     * @return  string              HTML for the field display
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $LANG_ACCESS, $LANG_DQ, $_CONF_DQ, $LANG_ADMIN;

        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval .= COM_createLink('',
                DQ_ADMIN_URL . "/index.php?editquote=x&amp;id={$A['quote_id']}",
                array(
                    'class' => 'uk-icon uk-icon-edit',
                )
            );
            break;

        case 'enabled':
            $value = $fieldvalue == 1 ? 1 : 0;
            $chk = $fieldvalue == 1 ? ' checked="checked" ' : '';
            $retval .= '<input type="checkbox" id="togena' . $A['quote_id'] . '"' .
                $chk . 'onclick=\'DQ_toggleEnabled(this, "' . $A['quote_id'] .
                    '", "quote");\' />';
            break;

        case 'title':
        case 'quote':
            $max_len = 40;
            $ellipses = strlen($fieldvalue) > $max_len ? ' ...' : '';
            $retval = substr(stripslashes($fieldvalue), 0, $max_len) . $ellipses;
            break;

        case 'dt';
            $dt = new \Date($A['dt'], $_CONF['timezone']);
            $retval = $dt->format($_CONF['shortdate'], true);
            break;

        case 'delete':
            $retval = COM_createLink('',
                DQ_ADMIN_URL . '/index.php?delquote=x&id=' . $A['quote_id'],
                array(
                    'class' => 'uk-icon uk-icon-minus-square uk-text-danger',
                    'onclick' => 'return confirm(\'' . $LANG_DQ['confirm_delitem'] . '\');',
                    'title' => $LANG_ADMIN['delete'],
                )
            );
            break;

        default:
            $retval = strip_tags($fieldvalue);
            break;
        }
        return $retval;
    }

}
