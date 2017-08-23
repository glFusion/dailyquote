<?php
/**
*   Class to handle quotes
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/**
*   Define a class to deal with quotes
*   @package dailyquote
*/
class dqQuote
{
    /** Quote properties
    *   @var array */
    var $properties = array();

    /** Categories to which this quote belongs
    *   @var array */
    var $categories = array();

    /** Is this a new submission?
    *   @var boolean */
    var $isNew;

    /** Flag to indicate current user is an admin
    *   @var boolean */
    var $isAdmin;

    /** Table to use, submission or production
    *   @var string */
    var $table;

    /** Table ID indicator, used to check which table is in use
    *   @var string */
    var $table_id;


    /**
    *   Constructor.
    *
    *   @param  string  $id     Quote ID to retrieve, blank for empty object
    *   @param  string  $table  Table ID, used for reading an existing quote
    */
    public function __construct($id='', $table='quotes')
    {
        global $_USER;

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
        $this->isAdmin = SEC_hasRights('dailyquote.admin');
    }


    /**
    *   Set a value into the properties array
    *
    *   @param  string  $key    Key to set
    *   @param  mixed   $value  Value to set for Key
    */
    public function __set($key, $value)
    {
        switch ($key) {
        case 'id':
            $this->properties[$key] = COM_sanitizeID($value, false);
            break;

        case 'quote':
        case 'quoted':
        case 'source':
        case 'sourcedate':
        case 'title':
        case 'dt':
            $this->properties[$key] = trim($value);
            break;

        case 'uid':
            $this->properties[$key] = (int)$value;
            break;

        case 'enabled':
            $this->properties[$key] = $value == 1 ? 1 : 0;
        }
    }


    /**
    *   Get a property value
    *
    *   @param  string  $key    Key to retrieve
    *   @return mixed       Value for key or NULL if not found
    */
    public function __get($key)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        } else {
            return NULL;
        }
    }


    /**
    *   Set the table for later use
    *
    *   @param  string  $table  Table ID, e.g. 'quotes' or 'submission'
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
    }

   
    /**
    *   Read a quote record from the database.
    *   If no quote ID is specified, a random quote is read.
    *
    *   @param  string  $qid    Optional quote ID to read
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
                $this->categories[] = $A['cid'];
            }
        }
        return true;
    }


    /**
    *   Set the variables from the supplied array.
    *   The array may be from a form ($_POST) or database record.
    *
    *   @param  array   $A  Array of values
    */
    public function setVars($A)
    {
        if (!is_array($A))
            return;

        $this->id = COM_sanitizeID($A['id'], true);
        $this->quote = $A['quote'];
        $this->quoted = $A['quoted'];
        $this->source = $A['source'];
        $this->sourcedate = $A['sourcedate'];
        $this->title = $A['title'];
        $this->dt = $A['dt'];
        $this->enabled = (isset($A['enabled']) && $A['enabled'] == 1) ? 1 : 0;
        $this->uid = (int)$A['uid'];
    }


    /**
    *   Update the 'enabled' value for a quote.
    *   Only applies to the prod table
    *
    *   @param  integer $newval     New value to set (1 or 0)
    *   @param  string  $bid        Optional ad ID.  Current object if blank
    */
    public static function toggleEnabled($newval, $id='')
    {
        global $_TABLES;

        $newval = $newval == 0 ? 0 : 1;
        DB_change($_TABLES['dailyquote_quotes'],
                'enabled', $newval,
                'id', DB_escapeString(trim($id)));
    }


    /**
    *   Delete a quote.
    *   Deletes the supplied quote ID if not empty, otherwise
    *   deletes the current object.
    *
    *   @param  string  $bid    Optional quote ID to delete
    */
    public static function Delete($id='', $table='quotes')
    {
        global $_TABLES;

        if ($table != 'quotes')
            $table = 'submission';

        if (!self::hasAccess(3))
            return;

        $id = COM_sanitizeID($id, false);
        DB_delete($_TABLES['dailyquote_' . $table],
            'id', $id);

        DB_delete($_TABLES['dailyquote_quoteXcat'],
            'qid', $id);

        if ($table == 'quotes') {
            PLG_itemDeleted($id, 'dailyquote');
        }
    }


    /**
    *   Returns the current user's access level to this quote.
    *
    *   @param  boolean $isNew  True to check new item access, false for existing
    *   @return integer     User's access level (1 - 3)
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
    *   Determines whether the current user has a given level of access
    *   to this quote object.
    *
    *   @see    Access()
    *   @param  integer $level  Minimum access level required
    *   @return boolean     True if user has access >= level, false otherwise
    */
    public function hasAccess($level=3, $isNew=false)
    {
        if (self::Access($isNew) < $level) {
            return false;
        } else {
            return true;
        }
    }


    /**
    *   Displays the quote editing form
    *
    *   @param  string  $mode   Editing mode (edit, submission, etc)
    *   @param  array   $A      Provided form values, e.g. from previous $_POST
    *   @return string          HTML for the form.
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
        $saveaction = $this->table_id == 'quotes' ? 'savequote' : 'savesubmission';
        $hidden_vars = '';
        if ($this->isAdmin) {
            $action_url = DQ_ADMIN_URL . '/index.php';
        } else {
            $action_url = DQ_URL . '/index.php';
        }

        switch ($mode) {
        case 'edit':
            $saveoption = $LANG_ADMIN['save'];      // Save
            $cancel_url = $this->isAdmin ? DQ_ADMIN_URL . '/index.php' :
                $_CONF['site_url'];
            break;

        case 'submit':
        case $LANG12[8]:
            $saveoption = $LANG_ADMIN['save'];      // Save
            $hidden_vars= '<input type="hidden" name="type" value="dailyquote" />'
                .'<input type="hidden" name="mode" value="' . $LANG12[8].'" />';
            $cancel_url = $this->isAdmin ? DQ_ADMIN_URL . '/index.php' : $_CONF['site_url'];
            break;

        case 'moderate':
            $saveoption = $LANG_ADMIN['moderate'];  // Save & Approve
            $saveaction = 'savemoderation'; // override $saveaction
            $cancel_url = $_CONF['site_admin_url'] . '/moderation.php';
            break;
        }

        $T = new Template(DQ_PI_PATH . '/templates');
        if ($_SYSTEM['framework'] == 'uikit') {
            $T->set_file('page', 'editform.uikit.thtml');
        } else {
            $T->set_file('page', 'editform.thtml');
        }
        $T->set_var('gltoken_name', CSRF_TOKEN);
        $T->set_var('gltoken', SEC_createToken());
        $T->set_var(array(
            'pi_name'       => $_CONF_DQ['pi_name'],
            'action_url'    => $action_url,
            'saveaction'    => $saveaction,
            'saveoption'    => $saveoption,
            'id'            => $this->id,
            'uid'           => $this->uid,
            'quote'         => $this->quote,
            'quoted'        => $this->quoted,
            'title'         => $this->title,
            'quoteby'       => $this->quoteby,
            'source'        => $this->source,
            'sourcedate'    => $this->sourcedate,
            'ena_chk'       => $this->enabled ? 'checked="checked"' : '',
            'is_admin'      => $this->isAdmin,
            'hidden_vars'   => $hidden_vars,
        ) );

        //retrieve categories from db if any and display
        if (!$result = DB_query("SELECT id, name 
                            FROM {$_TABLES['dailyquote_cat']} 
                            WHERE enabled='1' 
                            ORDER BY name")) {
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
            while ($A = DB_fetchArray($result)) {
                $chk = in_array($A['id'], $this->categories) ? 'checked="checked"' : '';
                $catinput .= '<input type="checkbox" ' .
                    'name="categories[' . $A['id'] . ']" ' .
                    $chk . ' />' .
                    '&nbsp;' . $A['name'] . '&nbsp;&nbsp;';
            }
            $T->set_var('catinput', $catinput);
        }
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
    *   Save the current quote object using the supplied values.
    *
    *   @param  array   $A  Array of values from $_POST or database
    */
    public function Save($A)
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $LANG_DQ, $_CONF_DQ;

        if (is_array($A))
            $this->setVars($A);

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
                    dt = " . time() . ', ';
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
                enabled = {$this->enabled},
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
        if (!is_array($A['categories']) || empty($A['categories'])) {
            $A['categories'] = array(1 => 'Miscellaneous');
        }
        foreach($A['categories'] as $key => $dummy) {
            $key = (int)$key;
            $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_quoteXcat']}
                    (qid, cid)
                VALUES (
                    '{$this->id}', $key
                )";
            DB_query($sql);
        }
        if ($this->table_id == 'quotes') {
            PLG_itemSaved($this->id, 'dailyquote');
        }
        return '';
    }


    /**
    *   Save a user submission
    *   Verifies access and sets the correct table, then calls Save() to
    *   save the submission
    *
    *   @param  array   $A      $_POST array of data
    *   @return string      Error message or empty string on success
    */
    public function SaveSubmission($A)
    {
        global $_CONF_DQ, $LANG_DQ, $_USER, $_CONF;

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
            return $LANG_DQ['access_denied'];
        }
        $msg = $this->Save($A);

        if ($msg == '') {
            // Send notification, if configured
            if ($email_admin == 1) {
                $T = new Template(DQ_PI_PATH . '/templates');
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
    *   Retrieves a single quote.  If $id is empty, a random quote is selected.
    *
    *   @param  string  $qid    Optional quote specifier
    *   @param  string  $cid    Optional category specifier
    *   @return object          Quote object, NULL if error or not found
    */
    public static function getQuote($qid='', $cid=0)
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
                    ON x.cid = c.id ";
        }
        $sql .= " WHERE enabled=1 ";
        if ($qid == '') {
            if ($cid > 0) {
                $sql .= " AND c.id = '$cid' ";
            }
            $sql .= " ORDER BY rand() LIMIT 1";
        } else {
            $sql .= " AND q.id='" . DB_escapeString($qid). "'";
        }

        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("dqQuote::getQuote() error: $sql");
            return NULL;
        }
        if (!$result || DB_numRows($result) == 0) {
            return NULL; 
        }

        $row = DB_fetchArray($result, false);
        $Q = new dqQuote();
        $Q->setVars($row);
        return $Q;
    }


     /**
     *   Enclose the Quoted field in link tags for Google search.
     *
     *   @param  string  $Quoted     Person quoted
     *   @return string              URL for google search, or 'unknown'
     */
     public static function GoogleLink($Quoted)
     {
         global $_CONF, $LANG_DQ, $_CONF_DQ;
 
         //do if based on setting
         if ($_CONF_DQ['google_link'] == 0 || empty($_CONF_DQ['google_url'])) {
             return $Quoted;
         }
 
         if ($Quoted == '') {
             return $LANG_DQ['unknown'];
         }
 
         $gname = urlencode(trim($Quoted));
         $retval = '<a href="' .
             sprintf($_CONF_DQ['google_url'], $_CONF['iso_lang'], $gname) .
                     '">' . $Quoted . '</a>';
         return $retval;
    }
 
 
    /**
    *   Sanitize text inputs. This is to sanitize text before saving to the DB.
    *
    *   @param  string  $str    String to be sanitized
    *   @return string      Sanitized string
    */
    private static function _safeText($str)
    {
        //return htmlspecialchars(COM_checkWords($str),ENT_QUOTES,COM_getEncodingt());
        return COM_checkWords($str);
    }

}   // class dqQuote

?>
