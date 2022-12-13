<?php
/**
 * Class to handle product collections.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner
 * @package     dailyquote
 * @version     v0.3.0
 * @since       v0.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote\Collections;
use glFusion\Database\Database;
use glFusion\Log\Log;
use DailyQuote\Quote;
use DailyQuote\Cache;


/**
 * Class to get collections of quotes.
 * @package dailyquote
 */
class QuoteCollection extends Collection
{
    private $page = 1;

    public function __construct()
    {
        global $_TABLES, $_CONF;

        parent::__construct();

        $this->_qb->select('q.*')
                  ->distinct()
                  ->from($_TABLES['dailyquote_quotes'], 'q')
                  ->leftJoin('q', $_TABLES['dailyquote_quoteXcat'], 'x', 'q.qid = x.qid')
                  ->leftJoin('x', $_TABLES['dailyquote_cat'], 'c', 'x.cid = c.id')
                  ->where('q.enabled = 1')
                  ->andWhere('c.enabled = 1 OR c.enabled IS NULL');
    }


    /**
     * Limit results to a single quote ID.
     * Really no different than calling Quote::getInstance($qid).
     *
     * @param   integer $qid    Quote ID
     * @return  object  $this
     */
    public function withQuoteId(int $qid) : self
    {
        $this->_qb->andWhere('qid = :qid')
                  ->setParameter('qid', $qid, Database::INTEGER);
        return $this;
    }


    /**
     * Get only approved or un-approved quotes.
     *
     * @param   boolean $flag   True for approved, False for unapproved
     * @return  object  $this
     */
    public function withApproved(bool $flag=true) : self
    {
        $this->_qb->andWhere('approved = :approved')
                  ->setParameter('approved', $flag ? 1 : 0, Database::INTEGER);
        return $this;
    }


    /**
     * Set the filter clause to limit by category ID.
     *
     * @param   integer $catid      Category ID
     * @return  object  $this
     */
    public function withCategoryId(int $catid) : self
    {
        if (!empty($catid)) {
            $this->_qb->andWhere('x.cid = :cat_id')
                      ->setParameter('cat_id', $catid, Database::INTEGER);
        }
        return $this;
    }


    /**
     * Set the filter clause to limit by author name.
     *
     * @param   string  $author     Author name
     * @return  object  $this
     */
    public function withAuthorName(string $author) : self
    {
        $this->_qb->andWhere('quoted = :author')
                  ->setParameter('author', $author, Database::STRING);
        return $this;
    }


    /**
     * Get the total number of quotes matching the criteria.
     * The collection is cloned so the select part query can be re-used.
     *
     * @return  integer     Total number of quotes
     */
    public function getCount() : int
    {
        $qb = clone $this->_qb;
        $qb->select('count(DISTINCT q.qid) AS cnt');
        $cache_key = md5($qb->getSQL() . json_encode($qb->getParameters()));
        $retval = Cache::get($cache_key);
        if ($retval === NULL) {
            $retval = 0;
            try {
                $row = $qb->execute()->fetchAssociative();
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $row = false;
            }
            if (is_array($row)) {
                $retval = (int)$row['cnt'];
            } else {
                $retval = 0;
            }
            Cache::set($cache_key, $retval, $this->cache_tags);
        }
        return $retval;
    }


    /**
     * Add a search string.
     *
     * @param   string  $str    Search string
     * @return  object  $this
     */
    public function withSearchString(string $str) : self
    {
        $sql = 'q.quote LIKE :str OR c.name LIKE :str OR
            q.title LIKE :str OR q.source LIKE :str OR
            q.author LIKE :str';
        $this->_qb->andWhere($sql)
           ->setParameter('str', '%' . $str . '%', Database::STRING);
        return $this;
    }


    /**
     * Add a "having" clause to the sql.
     *
     * @param   string  $str    Full clause
     * @return  object  $this
     */
    public function withHaving(string $str) : self
    {
        $this->_qb->having($str);
        return $this;
    }


    /**
     * Set the page number being displayed.
     *
     * @param   integer $page       Page number
     * @return  object  $this
     */
    public function setPage(int $page) : self
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
     * Create the limit clause based on the page number.
     *
     * @return  string      LIMIT clause
     */
    public function createPageLimit() : self
    {
        $displim = $this->getDisplayLimit();
        $startlimit = ($displim * $this->page) - $displim;
        $this->withLimit($startlimit, $displim);
        return $this;
    }


    /**
     * Get an array of product objects.
     *
     * @return  array   Array of Product objects
     */
    public function getObjects() : array
    {
        $Quotes = array();
        $rows = $this->getRows();
        foreach ($rows as $row) {
            $Quotes[$row['qid']] = Quote::fromArray($row);
        }
        return $Quotes;
    }


    /**
     * Get the page navigation based on the total number of quotes.
     *
     * @return  string      HTML for page navigation
     */
    public function getPageNavigation($sort, $dir, $total_quotes) : string
    {
        $prevpage = $this->page - 1;
        $nextpage = $this->page + 1;
        $displim = $this->getDisplayLimit();
        $pagestart = ($this->page - 1) * $displim;
        $baseurl = DQ_URL . '/index.php?sort=' . $sort. '&dir=' . $dir;
        $numpages = ceil($total_quotes / $displim);
        return COM_printPageNavigation($baseurl, $this->page, $numpages);
    }


    /**
     * Use this to order by RAND().
     *
     * @return  object  $this
     */
    public function withRandom() : self
    {
        $this->_useCache = false;
        $total = $this->getCount();
        $rand = mt_rand(0,$total - 1);
        $this->withLimit($rand, 1)->orderBy('qid', 'ASC');
        return $this;
    }

}
