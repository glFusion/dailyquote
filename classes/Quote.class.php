<?php
/**
 * Class to handle quotes.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2020 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote;
use DailyQuote\MO;


/**
 * Define a class to deal with quotes.
 * @package dailyquote
 */
class Quote
{
    /** Quote ID.
     * @var string */
    private $id = '';

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

    /** Categories to which this quote belongs.
     * @var array */
    private $categories = array();

    /** Is this a new submission?
     * @var boolean */
    private $isNew = 1;

    /** Flag to indicate current user is an admin.
     *  @var boolean */
    private $isAdmin = 0;

    /** Table to use, submission or production.
     * @var string */
    private $table = '';

    /** Table ID indicator, used to check which table is in use.
     * @var string */
    private $table_id = 'quotes';

    /** Array of Category objects.
     * @var object */
    private $Cats = array();


    /**
     * Constructor.
     *
     * @param   string  $id     Quote ID to retrieve, blank for empty object
     * @param   string  $table  Table ID, used for reading an existing quote
     */
    public function __construct($id='', $table='quotes')
    {
        global $_USER;

        if (is_array($id)) {
            // Already have the record.
            $this->setVars($id);
        } else {
            $this->id = $id;
            $this->isNew = true;
            $this->uid = $_USER['uid'];
            $this->enabled = 1;

            // Set the table name here in case a quote is being read
            $this->setTable($table);

            if ($this->id != '') {
                // Read() returns true if found
                $this->isNew = $this->Read() ? false : true;
            }
        }
        $this->isAdmin = SEC_hasRights('dailyquote.admin');
    }


    /**
     * Get the quote ID.
     *
     * @return  string      Quote ID
     */
    public function getID()
    {
        return $this->id;
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
     * Set the isNew flag manually.
     *
     * @param   boolean $flag   Nonzero for a new record, Zero if existing
     * @return  object  $this
     */
    private function setIsNew($flag=1)
    {
        $this->isNew = $flag ? 1 : 0;
        return $this;
    }

    /**
     * Get the isNew flag.
     *
     * @return  boolean     1 if this is a new record, 0 if existing
     */
    public function isNew()
    {
        return $this->isNew ? 1 : 0;
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
            $users[$uid] = DB_getItem(
                $_TABLES['users'],
                'username',
                "uid = $uid"
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

        $sql = "SELECT * FROM {$this->table}
                WHERE id = '{$this->id}'";
        $res = DB_query($sql);
        if (!$res|| DB_numRows($res) != 1) {
            return false;
        }
        $A = DB_fetchArray($res, false);
        $this->setVars($A);

        // Get the categories that this quote is in.
        $sql = "SELECT cid FROM {$_TABLES['dailyquote_quoteXcat']}
                WHERE qid = '{$this->id}'";
        $res = DB_query($sql);
        if ($res) {
            while ($A = DB_fetchArray($res, false)) {
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
        $sql = "SELECT cid FROM {$_TABLES['dailyquote_quoteXcat']}
            WHERE qid = '" . DB_escapeString($qid) . "'";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            if (array_key_exists($A['cid'], $Cats)) {
                $retval[$A['cid']] = $Cats[$A['cid']];
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

        $this->id = COM_sanitizeID($A['id'], true);
        $this->quote = $A['quote'];
        $this->quoted = $A['quoted'];
        $this->source = $A['source'];
        $this->sourcedate = $A['sourcedate'];
        $this->title = $A['title'];
        $this->enabled = (isset($A['enabled']) && $A['enabled'] == 1) ? 1 : 0;
        $this->uid = (int)$A['uid'];
        return $this;
    }


    /**
     * Update the 'enabled' value for a quote.
     * Only applies to the prod table
     *
     * @param   integer $newval     New value to set (1 or 0)
     * @param   string  $id         Quote ID
     */
    public static function toggleEnabled($newval, $id)
    {
        global $_TABLES;

        $newval = $newval == 0 ? 0 : 1;
        DB_change($_TABLES['dailyquote_quotes'],
                'enabled', $newval,
                'id', DB_escapeString(trim($id)));
    }


    /**
     * Delete a quote.
     * Deletes the supplied quote ID if not empty, otherwise
     * deletes the current object.
     *
     * @param   string  $id     Quote ID to delete
     * @param   string  $table  Table key from which to delete
     */
    public static function Delete($id, $table='quotes')
    {
        global $_TABLES;

        if ($table != 'quotes') {
            $table = 'submission';
        }

        if (!self::hasAccess(3)) {
            return;
        }

        $id = COM_sanitizeID($id, false);
        DB_delete(
            $_TABLES['dailyquote_' . $table],
            'id',
            $id
        );
        DB_delete(
            $_TABLES['dailyquote_quoteXcat'],
            'qid',
            $id
        );

        if ($table == 'quotes') {
            PLG_itemDeleted($id, 'dailyquote');
        }
    }


    /**
     * Returns the current user's access level to this quote.
     *
     * @param   boolean $isNew  True to check new item access, false for existing
     * @return  integer     User's access level (1 - 3)
     */
    public static function Access($isNew = false)
    {
        global $_USER, $_CONF_DQ;

        if (SEC_hasRights('dailyquote.edit'))
            return 3;

        if ($isNew) {
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
     * @param   boolean $isNew  True is this is a new submission
     * @return  boolean     True if user has access >= level, false otherwise
     */
    public static function hasAccess($level=3, $isNew=false)
    {
        if (self::Access($isNew) < $level) {
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
        global $_TABLES, $_CONF, $_USER, $_CONF_DQ,
            $LANG12, $_SYSTEM;

        $retval = '';
        if (!empty($A)) {
            // Form is being re-edited due to a previous error
            $this->setVars($A);
        }

        // Default save action based on table
        $saveaction = $this->table_id == 'quotes' ? 'savequote' : 'savesubmission';
        $hidden_vars = '';
        if ($this->isAdmin) {
            $action_url = DQ_ADMIN_URL . '/index.php';
        } else {
            $action_url = DQ_URL . '/index.php';
        }

        switch ($mode) {
        case 'edit':
            $saveoption = MO::_('Save');      // Save
            $cancel_url = $this->isAdmin ? DQ_ADMIN_URL . '/index.php' :
                $_CONF['site_url'];
            break;

        case 'submit':
        case $LANG12[8]:
            $saveoption = MO::_('Save');      // Save
            $hidden_vars= '<input type="hidden" name="type" value="dailyquote" />'
                .'<input type="hidden" name="mode" value="' . $LANG12[8].'" />';
            $cancel_url = $this->isAdmin ? DQ_ADMIN_URL . '/index.php' : $_CONF['site_url'];
            break;

        case 'moderate':
            $saveoption = MO::_('Moderate');  // Save & Approve
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
            'id'            => $this->id,
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
            'lang_addquote' => MO::_('Add Your Quotation Here'),
            'lang_nomarks'  => MO::_('Please do not enclose your quotation inside quotation marks.') .
                MO::_('Any quotations within your quotation should be contained within single quotation marks.') .
                MO::_('There should not be any double quotation marks anywhere in the text that you have typed or pasted into this space.') .
                MO::_('The only required field is the quotation field.'),
            'lang_addtitle' => MO::_('Title'),
            'lang_quotation' => MO::_('Quotation'),
            'lang_required' => MO::_('This item is required'),
            'lang_quoted' => MO::_('Person Quoted'),
            'lang_source' => MO::_('Source'),
            'lang_sourcedate' => MO::_('Source Date'),
            'lang_enabled' => MO::_('Enabled'),
            'lang_choosecat' => MO::_('Choose one or more categories'),
            'lang_reset' => MO::_('Reset'),
            'lang_delete'   => MO::_('Delete'),
            'lang_cancel'   => MO::_('Cancel'),
            'lang_confirm_delitem' => MO::_('Are you sure you want to delete this item?'),
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
        $catinput = '';
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
    public function Save($A)
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $_CONF_DQ;

        if (is_array($A)) {
            $this->setVars($A);
        }

        if ($this->uid == '') {
            // this is new quote from admin, set default values
            $uid = $_USER['uid'];
        }

        $access = $this->hasAccess(3, $this->isNew);
        if (!$access || $this->id == '') {
            COM_errorLog("User {$_USER['username']} tried to illegally submit or edit quote {$this->id}.");
            return $MESSAGE[31];
        }

        // Determine if this is an INSERT or UPDATE
        if ($this->isNew) {
            $sql1 = "INSERT INTO {$this->table} SET id = '" .
                    DB_escapeString($this->id) . "',
                    dt = UNIX_TIMESTAMP(), ";
            $sql3 = '';
        } else {
            $sql1 = "UPDATE {$this->table} SET ";
            $sql3 = " WHERE id = '{$this->id}'";
        }
        $sql2 = "quote = '" .  DB_escapeString(self::_safeText($this->quote)). "',
                quoted = '" . DB_escapeString(self::_safeText($this->quoted)). "',
                title = '" . DB_escapeString(self::_safeText($this->title)) . "',
                source = '" . DB_escapeString(self::_safeText($this->source)) . "',
                sourcedate = '" . DB_escapeString(self::_safeText($this->sourcedate)) . "',
                enabled = {$this->isEnabled()},
                uid = {$this->uid}";
        $sql = $sql1 . $sql2 . $sql3;
        DB_query($sql, 1);
        if (DB_error()) {
            return 'An error occurred inserting the quote';
        }

        // Delete all lookup records for this quote to make sure we
        // get rid of unused categories.
        DB_delete($_TABLES['dailyquote_quoteXcat'], 'qid', $this->id);

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
            $values[] = "('{$this->id}', $key)";
        }
        if (!empty($values)) {
            $value_str = implode(',', $values);
            $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_quoteXcat']}
                    (qid, cid)
                VALUES $value_str";
            DB_query($sql);
        }
        if ($this->table_id == 'quotes') {
            Cache::clear();
            PLG_itemSaved($this->id, 'dailyquote');
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
    public function SaveSubmission($A)
    {
        global $_CONF_DQ, $_USER, $_CONF;

        if (SEC_hasRights('dailyquote.submit')) {
            $this->setTable('quotes');
            $email_admin = 0;
        } elseif ($_CONF_DQ['queue'] == 0) {
            // user has submit right or submission queue is not being used
            $this->setTable('quotes');
            $email_admin = $_CONF_DQ['email_admin'] == 2 ? 1 : 0;
        } elseif ((int)$_USER['uid'] > 1 && $_CONF_DQ['loginadd'] == 1) {
            // user must go through the submission queue
            $this->setTable('submission');
            $email_admin = $_CONF_DQ['email_admin'] > 0 ? 1 : 0;
        }  else {
            return MO::_('Access Denied');
        }
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
                COM_mail(
                    $_CONF['site_mail'],
                    MO::_('New Daily Quote Notification'),
                    $T->finish($T->get_var('output'))
                );
            }
        }
        return $msg;
    }


    /**
     * Retrieves a single quote. If $id is empty, a random quote is selected.
     *
     * @param   string  $qid    Optional quote specifier
     * @param   string  $cid    Optional category specifier
     * @return  object          Quote object, NULL if error or not found
     */
    public static function getInstance($qid='', $cid=0)
    {
        global $_TABLES;

        // Sanitize category ID
        $cid = (int)$cid;

        //get random quote
        $sql = "SELECT q.* FROM {$_TABLES['dailyquote_quotes']} q";
        if ($cid > 0) {
            $sql .= " LEFT JOIN {$_TABLES['dailyquote_quoteXcat']} x
                    ON x.qid = q.id
                LEFT JOIN {$_TABLES['dailyquote_cat']} c
                    ON x.cid = c.id";
        }
        $sql .= " WHERE q.enabled=1";
        if ($qid == '') {
            if ($cid > 0) {
                $sql .= " AND c.id = '$cid' AND c.enabled = 1";
            }
            $sql .= " ORDER BY rand() LIMIT 1";
        } else {
            $sql .= " AND q.id='" . DB_escapeString($qid). "'";
        }
        //echo $sql;die;
        $Q = new self();
        $result = DB_query($sql, 1);
        if ($result && DB_numRows($result) > 0) {
            $row = DB_fetchArray($result, false);
            $Q->setVars($row);
            $Q->setIsNew(0);
        }
        return $Q;
    }


    /**
     * Enclose the Quoted field in link tags for Google search.
     *
     * @param   string  $Quoted     Person quoted
     * @return  string              URL for google search, or 'unknown'
     */
    public static function GoogleLink($Quoted)
    {
        global $_CONF, $_CONF_DQ;

        if ($Quoted == '') {
            $retval = MO::_('Unknown');
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
        global $_CONF, $_TABLES, $_CONF_DQ;

        $retval = '';

        $header_arr = array(      # display 'text' and use table field 'field'
            array(
                'field' => 'edit',
                'text' => MO::_('Edit'),
                'sort' => false,
            ),
            array(
                'field' => 'enabled',
                'text' => MO::_('Enabled'),
                'sort' => false,
            ),
            array(
                'field' => 'id',
                'text' => 'Quote ID',
                'sort' => true,
            ),
            array(
                'field' => 'dt',
                'text' => MO::_('Date'),
                'sort' => true,
            ),
            array(
                'field' => 'quoted',
                'text' => MO::_('Person Quoted'),
                'sort' => true,
            ),
            array(
                'field' => 'title',
                'text' => MO::_('Title'),
                'sort' => true,
            ),
            array(
                'field' => 'quote',
                'text' => MO::_('Quote'),
                'sort' => true,
            ),
            array(
                'field' => 'delete',
                'text' => MO::_('Delete'),
                'sort' => false,
            ),
        );

        $defsort_arr = array('field' => 'dt', 'direction' => 'desc');
        $text_arr = array(
            'has_extras' => true,
            'form_url' => DQ_ADMIN_URL . '/index.php?type=quote',
        );
        $options = array('chkdelete' => 'true', 'chkfield' => 'id');
        $query_arr = array(
            'table' => 'dailyquote',
            'sql' => "SELECT * FROM {$_TABLES['dailyquote_quotes']} ",
            'query_fields' => array('title', 'quotes', 'quoted'),
            'default_filter' => 'WHERE 1=1',
        );
        $form_arr = array();
        $retval = COM_createLink(
            MO::_('New Quote'),
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
        global $_CONF, $_CONF_DQ;

        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval .= COM_createLink('',
                DQ_ADMIN_URL . "/index.php?editquote=x&amp;id={$A['id']}",
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
                DQ_ADMIN_URL . '/index.php?delquote=x&id=' . $A['id'],
                array(
                    'class' => 'uk-icon uk-icon-trash dq-icon-danger',
                    'onclick' => 'return confirm(\'' . 
                        MO::_('Are you sure you want to delete this item?') .                       '\');',
                    'title' => MO::_('Delete'),
                )
            );
            break;

        default:
            $retval = strip_tags($fieldvalue);
            break;
        }
        return $retval;
    }

}   // class Quote

?>
