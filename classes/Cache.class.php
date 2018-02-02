<?php
/**
*   Class to cache DB and web lookup results
*   Caching is supported in glFusion 1.8.0+ so this class abstracts the cache
*   functions, doing nothing if caching is not supported.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.2.1
*   @since      0.2.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace DailyQuote;

/**
*   Class for cache
*   @package dailyquote
*/
class Cache
{
    private static $tag = 'dailyquote';

    /**
    *   Update the cache
    *
    *   @param  string  $key    Item key
    *   @param  mixed   $data   Data, typically an array
    *   @param  
    */
    public static function setCache($key, $data, $tag='')
    {
        if (GVERSION < '1.8.0') return NULL;

        if ($tag == '') {
            $tags = array(self::$tag);
        } elseif (is_array($tag)) {
            $tags = $tag;
            $tags[] = self::$tag;
        } else {
            $tag = array($tag, self::$tag);
        }
        $key = self::_makeKey($key, $tag);
        \glFusion\Cache::getInstance()->set($key, $data, $tag);
    }


    /**
    *   Completely clear the cache.
    *   Called after upgrade.
    */
    public static function clearCache($tag = '')
    {
        if (GVERSION < '1.8.0') return NULL;
        $tags = array(self::$tag);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        }
        \glFusion\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
    *   Create a unique cache key.
    *
    *   @return string          Encoded key string to use as a cache ID
    */
    private static function _makeKey($key)
    {
        return self::$tag . '_' . $key;
    }

    
    public static function getCache($key, $tag='')
    {
        if (GVERSION < '1.8.0') return NULL;

        $key = self::_makeKey($key);
        if (\glFusion\Cache::getInstance()->has($key)) {
            return \glFusion\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }

}   // class Evlist\Cache

?>
