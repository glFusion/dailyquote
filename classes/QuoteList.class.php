<?php
/**
 * Class to handle displaying quote lists.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote;

/**
 * Define a class to deal with quotes.
 * @package dailyquote
 */
class QuoteList
{
    /** Filter clause to limit by quote ID.
     * @var string */
    private $filter_id = '';

    /** Filter clause to limit by category ID.
     * @var string */
    private $filter_cat = '';

    /** Filter clause to limit by author name.
     * @var string */
    private $filter_author = '';

    private $sortby = '';
    private $sortdir = '';

    /** Record sorting clause.
     * @var string */
    private $sort_clause = '';

    /** Limit clause.
     * @var string */
    private $sql_limit = '';

    /** Page number being displayed.
     * @var integer */
    private $page = 1;


    /**
     * Set the filter clause to limit by quote ID.
     *
     * @param   string  $q_id       Quote ID
     * @return  object  $this
     */
    public function setFilterID($q_id)
    {
        if (!empty($q_id)) {
            $this->filter_id = " AND q.id = '" . DB_escapeString($q_id) . "'";
        } else {
            $this->filter_id = '';
        }
        return $this;
    }


    /**
     * Set the filter clause to limit by category ID.
     *
     * @param   integer $catid      Category ID
     * @return  object  $this
     */
    public function setFilterCat($catid)
    {
        if (!empty($catid)) {
            $this->filter_cat = " AND x.cid = " . (int)$catid;
        } else {
            $this->filter_cat = '';
        }
        return $this;
    }

    
    /**
     * Set the filter clause to limit by author name.
     *
     * @param   string  $author     Author name
     * @return  object  $this
     */
    public function setFilterAuthor($author)
    {
        if (!empty($author)) {
            $this->filter_author = " AND quoted = '" . DB_escapeString($author) . "'";
        } else {
            $this->filter_author = '';
        }
        return $this;
    }


    /**
     * Create the SQL sort clause and set internal sortby and sortdir props.
     *
     * @param   string  $fld    Sort field name
     * @param   string  $dir    Sort direction
     * @return  object  $this
     */
    public function setSort($fld, $dir='DESC')
    {
        if ($dir != 'ASC') $dir = 'DESC';

        switch ($fld) {
        case 'quote':
        case 'quoted':
        case 'dt':
            break;      // requested field is OK
        default:
            $fld = 'dt';    // default
            break;
        }
        $this->sortby = $fld;
        $this->sortdir = $dir;
        //$this->sort_clause = " GROUP BY q.id ORDER BY $fld $dir";
        $this->sort_clause = " ORDER BY $fld $dir";
        return $this;
    }


    /**
     * Set the page number being displayed.
     *
     * @param   integer $page       Page number
     * @return  object  $this
     */
    public function setPage($page)
    {
        if ($page < 1) $page = 1;
        $this->page = (int)$page;
        return $this;
    }


    /**
     * Get the configured per-page display limit.
     *
     * @return  integer     Per-page limit
     */
    public function getDisplayLimit()
    {
        global $_CONF_DQ;

        $displim = (int)$_CONF_DQ['indexdisplim'];
        if ($displim <= 0) {
            $displim = 15;
        }
        return $displim;
    }


    /**
     * Get the limit SQL clause.
     *
     * @return  string      LIMIT clause
     */
    public function getLimit()
    {
        $displim = $this->getDisplayLimit();
        $startlimit = ($displim * $this->page) - $displim;
        return " LIMIT $startlimit, $displim";
    }


    /**
     * Get the SQL predicate portion, everything after the "SELECT".
     *
     * @return  string      SQL query following "SELECT"
     */
    private function _sqlPredicate()
    {
        global $_TABLES;

        return " FROM {$_TABLES['dailyquote_quotes']} q
            LEFT JOIN {$_TABLES['dailyquote_quoteXcat']} x
                ON q.id = x.qid
            LEFT JOIN {$_TABLES['dailyquote_cat']} c
                ON x.cid = c.id
            WHERE q.enabled = '1'
            AND (c.enabled = '1' OR c.enabled IS NULL) 
            {$this->filter_id}
            {$this->filter_cat}
            {$this->filter_author}
            {$this->sort_clause}
            {$this->getLimit()}";
    }


    /**
     * Get the total number of quotes to be shown on a page.
     *
     * @return  integer     Quotes to show on a page
     */
    public function getPageCount()
    {
        $retval = 0;
        $sql = "SELECT count(DISTINCT q.id) AS cnt " . $this->_sqlPredicate();
        $res = DB_query($sql);
        if ($res) {
            $A = DB_fetchArray($res, false);
            $retval = (int)$A['cnt'];
        }
        return $retval;
    }


    /**
     * Get the quote objects to be shown on a page.
     *
     * @return  array       Array of Quote objects
     */
    public function getPageQuotes()
    {
        $retval = array();
        $sql = "SELECT DISTINCT(q.id), q.quote, q.uid,
            q.quoted, q.title, q.source, q.sourcedate,
            q.dt, q.enabled " . $this->_sqlPredicate();
        $res = DB_query($sql);
        if ($res) {
            while ($A = DB_fetchArray($res, false)) {
                $retval[] = new Quote($A);
            }
        }
        return $retval;
    }


    /**
     * Get the page navigation based on the total number of quotes.
     *
     * @return  string      HTML for page navigation
     */
    public function getPageNavigation($total_quotes)
    {
        $prevpage = $this->page - 1;
        $nextpage = $this->page + 1;
        $displim = $this->getDisplayLimit();
        $pagestart = ($this->page - 1) * $displim;
        $baseurl = DQ_URL . '/index.php?sort=' . $this->sortby . '&dir=' . $this->sortdir;
        $numpages = ceil($total_quotes / $displim);
        return COM_printPageNavigation($baseurl, $this->page, $numpages);
    }

}

?>
