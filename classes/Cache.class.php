<?php
/**
 * Class to cache DB and web lookup results.
 * Caching is supported in glFusion 1.8.0+ so this class abstracts the cache
 * functions, doing nothing if caching is not supported.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.3.0
 * @since       v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote;


/**
 * Class for cache.
 * @package dailyquote
 */
class Cache
{
    /** Tag applied to all saved items.
     * @var string */
    private static $tag = 'dailyquote';

    /**
     * Update the cache.
     *
     * @param  string  $key    Item key
     * @param  mixed   $data   Data, typically an array
     * @param  array|string $tag    One or more tags to apply
     */
    public static function set($key, $data, $tag='')
    {
        if (empty($tag)) {
            $tags = array(self::$tag);
        } elseif (is_array($tag)) {
            $tags = $tag;
            $tags[] = self::$tag;
        } else {
            $tags = array($tag, self::$tag);
        }
        $key = self::_makeKey($key);
        return \glFusion\Cache\Cache::getInstance()->set($key, $data, $tags);
    }


    /**
     * Clear the cache completely, or for specific tags.
     *
     * @param   string|array    $tag    Tag or tags to remove
     * @return  boolean     True on success, False on failure
     */
    public static function clear($tag = '')
    {
        $tags = array(self::$tag);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        }
        return \glFusion\Cache\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
     * Create a unique cache key.
     *
     * @param   string  $key    Base item key
     * @return  string          Encoded key string to use as a cache ID
     */
    private static function _makeKey($key)
    {
        return self::$tag . '_' . $key;
    }


    /**
     * Get a single item form cache.
     *
     * @param   string  $key    Item key
     * @return  mixed   Item, or NULL if not found
     */
    public static function get($key)
    {
        $key = self::_makeKey($key);
        if (\glFusion\Cache\Cache::getInstance()->has($key)) {
            return \glFusion\Cache\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }

}
