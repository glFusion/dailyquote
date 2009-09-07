<?php
//  $Id$
/**
*   Class to handle banner ads
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
class DailyQuote
{
    /** Banner ID
     *  @var string */
    var $id;

    /** Quote Text
     *  @var string */
    var $quote;

    /** Who is quoted
     *  @var string */
    var $quoted;

    /** Title of quote
     *  @var string */
    var $title;

    /** Source (book, web site, etc)
     *  @var string */
    var $source;

    /** Date of the source
     *  @var string */
    var $sourcedate;

    /** Date of the quote entry
     *  @var integer */
    var $dt;

    /** User ID of submitter
     *  @var integer */
    var $uid;

    /** Status
     *  @var integer */
    var $status;
   
    /**
     *  Constructor
     *  @param string $bid Banner ID to retrieve, blank for empty class
     */
    function DailyQuote($id='')
    {
        $id = trim($id);
        if ($id != '') {
            $this->Read($id);
        } else {
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
            SELECT * FROM {$_TABLES['dailyquote_quotes']}
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

        $this->id = COM_sanitizeID($A['id'], false);
        $this->quote = $A['quote'];
        $this->quoted = $A['content'];
        $this->source = $A['source'];
        $this->sourcedate = $A['sourcedate'];
        $this->title = $A['title'];
        $this->dt = $A['dt'];
        $this->status = $A['status'] == 1 ? 1 : 0;
        $this->uid = (int)$A['uid'];

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
        DB_change($_TABLES['dailyquote_quote'],
                'status', $newval,
                'id', addslashes(trim($id)));
    }


    /**
     *  Delete a quote.
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
        DB_delete($_TABLES['dailyquote_quotes'],
            'id', $id);

        DB_delete($_TABLES['dailyquote_lookup'],
            'qid', $id);
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
    function Save($A, $table='dailyquote_quote')
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE, $LANG_DQ, $_CONF_DQ;

        if (is_array($A))
            $this->setVars($A);

        if (empty($this->uid)) {
            // this is new banner from admin, set default values
            $uid = $_USER['uid'];
        }

        $access = $this->hasAccess(3);
        if ($access < 3) {
            COM_errorLog("User {$_USER['username']} tried to illegally submit or edit quote {$this->id}.");
            return COM_showMessageText($MESSAGE[31], $MESSAGE[30]);
        }

        // Determine if this is an INSERT or UPDATE
        if (empty($this->id)) {
            $this->id = COM_makeSID();
            $sql = "INSERT INTO $table
                    (id, dt, quote, quoted, title, source, sourcedate)
                VALUES (
                    '{$A['id']}',
                    " . time() . ",
                    '" . addslashes(COM_checkwords($A['quote'])). "',
                    '" . addslashes(COM_checkwords($A['quoted'])). "',
                    '" . addslashes(COM_checkwords($A['title'])) . "',
                    '" . addslashes(COM_checkwords($A['source'])) . "',
                    '" . addslashes(COM_checkwords($A['sourcedate'])) . "'
            )";
        } else {
            $sql = "UPDATE $table
                SET
                    quote = '" . addslashes(COM_checkwords($A['quote'])). "',
                    quoted = '" . addslashes(COM_checkwords($A['quoted'])). "',
                    title = '" . addslashes(COM_checkwords($A['title'])) . "',
                    source = '" . addslashes(COM_checkwords($A['source'])) . "',
                    sourcedate = '" . addslashes(COM_checkwords($A['sourcedate'])) . "'
                WHERE
                    id = '" .addslashes($A['id']) . "'";
        }
        //echo $sql;die;
        DB_query($sql);

        // Add categories
        $catlist = array();     // used to populate lookup table
        if (empty($A['cat'][0]))
            $A['cat'][0] = 'Miscellaneous';
        foreach ($A['cat'] as $key=>$catname) {
            if ($catname == '') continue;
            if ($cat_table == 'dailyquote_cat_sub') {
                $sql = "
                    INSERT INTO
                        $cat_table
                        (qid, name)
                    VALUES (
                        '{$A['id']}',
                        '" . glfPrepareForDB(COM_checkwords($catname)) . "'
                    )";
                DB_query($sql);
            } else {
                $sql = "
                    INSERT IGNORE INTO
                        $cat_table
                        (name)
                    VALUES (
                        '" . glfPrepareForDB(COM_checkwords($catname)) . "'
                    )";
                DB_query($sql);
                $catid = DB_insertID();
                if ($catid == 0) {
                    $catid = DB_getItem(
                            $_TABLES['dailyquote_cat'],
                            'id', 
                            "name='$catname'");
                }
                $catlist[] = $catid;
            }

            if (DB_error())
                return DB_error();
        }

        // Now, add records to the lookup table to link the categories
        // to the quote.  Only if bypassing the submission queue; if
        // the queue is used $catlist will be empty.
        if (!empty($catlist)) {
            foreach($catlist as $catid) {
                $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_lookup']}
                        (qid, cid, uid, status)
                    VALUES (
                        '{$A['id']}', $catid, {$A['uid']}, 1
                    )";
                //echo $sql;
                @DB_query($sql);
            }
        }

        return '';

    }


    /**
     *  Retrieves a single quote.  If $id is empty, a randome quote is selected.
     *  If called within an instantiated object, then the object properties
     *  are populated.
     *  @param  string  $id     Optional quote specifier
     *  @return array   Values from quote table.
     */
    function getQuote($id='')
    {
        global $_TABLES;

        //get random quote
        $sql = "SELECT 
                    q.quote, q.source, q.quoted, q.title, 
                    q.source, q.sourcedate, 
                    q.dt
                FROM 
                    {$_TABLES['dailyquote_quotes']} q 
                WHERE 
                    q.status = '1'";
        if ($id == '') {
            $sql .= " ORDER BY rand() LIMIT 1";
        } else {
            $sql .= " AND q.id='" . addslashes($id). "'";
        }
        //echo $sql;

        if (!$result = DB_query($sql)) {
            COM_errorLog("An error occured while retrieving your quote",1);
            return array();
        }

        $row = DB_fetchArray($result, false);
        if (!empty($row))
            $row['quoted'] = DailyQuote::GoogleLink($row['quoted']);

        if (is_object($this))
            $this->SetVars($row);

        return $row;
    }


    function GoogleLink($Quoted)
    {
        global $_CONF, $_TABLES, $LANG_DQ, $_CONF_DQ;

        //do if based on setting
        if ($_CONF_DQ['google_link'] == 1 && !empty($_CONF_DQ['google_url'])) {
            if ($Quoted != '') {
                $gname = urlencode(trim($Quoted));
                $retval = '<a href="' . $_CONF_DQ['google_url'] . $gname . '">' . $Quoted . '</a>';
            } else {
                $retval = $LANG_DQ['unknown'];
            }
        } else {
            $retval = $Quoted;
        }

        return $retval;
    }


}   // class DailyQuote



?>
