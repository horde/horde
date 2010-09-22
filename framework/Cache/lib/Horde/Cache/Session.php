<?php
/**
 * This class provides a session storage implementation of the Horde caching
 * system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
class Horde_Cache_Session extends Horde_Cache_Base
{
    /**
     * Pointer to the session entry.
     *
     * @var array
     */
    protected $_sess;

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'session' - (string) Store session data in this entry.
     *             DEFAULT: 'horde_cache_session'
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'sess_name' => 'horde_cache_session'
        ), $params);

        parent::__construct($params);

        if (!isset($_SESSION[$this->_params['sess_name']])) {
            $_SESSION[$this->_params['sess_name']] = array();
        }
        $this->_sess = &$_SESSION[$this->_params['sess_name']];
    }

    /**
     * Attempts to retrieve a piece of cached data and return it to
     * the caller.
     *
     * @param string $key        Cache key to fetch.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    public function get($key, $lifetime = 1)
    {
        if ($this->exists($key, $lifetime)) {
            return $this->_sess[$key]['d'];
        }

        return false;
    }

    /**
     * Attempts to store an object to the cache.
     *
     * @param string $key        Cache key (identifier).
     * @param string $data       Data to store in the cache.
     * @param integer $lifetime  Data lifetime.
     *
     * @throws Horde_Cache_Exception
     */
    public function set($key, $data, $lifetime = null)
    {
        if (!is_string($data)) {
            throw new Horde_Cache_Exception('Data must be a string.');
        }
        $this->_sess[$key] = array(
            'd' => $data,
            'l' => $this->_getLifetime($lifetime)
        );
    }

    /**
     * Checks if a given key exists in the cache, valid for the given
     * lifetime.
     *
     * @param string $key        Cache key to check.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return boolean  Existence.
     */
    public function exists($key, $lifetime = 1)
    {
        if (isset($this->_sess[$key])) {
            /* 0 means no expire. */
            if (($lifetime == 0) ||
                ((time() - $lifetime) <= $this->_sess[$key]['l'])) {
                return true;
            }

            unset($this->_sess[$key]);
        }

        return false;
    }

    /**
     * Expire any existing data for the given key.
     *
     * @param string $key  Cache key to expire.
     *
     * @return boolean  Success or failure.
     */
    public function expire($key)
    {
        if (isset($this->_sess[$key])) {
            unset($this->_sess[$key]);
            return true;
        }

        return false;
    }

}
