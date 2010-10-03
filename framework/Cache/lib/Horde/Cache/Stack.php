<?php
/**
 * Horde_Cache_Stack:: is a Cache implementation that will loop through a
 * given list of Cache drivers to search for a cached value.  This driver
 * allows for use of caching backends on top of persistent backends.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Cache
 */
class Horde_Cache_Stack extends Horde_Cache
{
    /**
     * Stack of cache drivers.
     *
     * @var string
     */
    protected $_stack = array();

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'stack' - (array) [REQUIRED] An array of Cache instances to loop
     *           through, in order of priority. The last entry is considered
     *           the 'master' driver, for purposes of writes.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['stack'])) {
            throw new InvalidArgumentException('Missing stack parameter.');
        }
        $this->_stack[] = $params['stack'];

        unset($params['stack']);
        parent::__construct($params);
    }

    /**
     * Attempts to retrieve a cached object and return it to the
     * caller.
     *
     * @param string $key        Object ID to query.
     * @param integer $lifetime  Lifetime of the object in seconds.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    public function get($key, $lifetime = 1)
    {
        foreach ($this->_stack as $val) {
            $result = $val->get($key, $lifetime);
            if ($result !== false) {
                break;
            }
        }

        return $result;
    }

    /**
     * Attempts to store an object in the cache.
     *
     * @param string $key        Object ID used as the caching key.
     * @param string $data       Data to store in the cache.
     * @param integer $lifetime  Object lifetime - i.e. the time before the
     *                           data becomes available for garbage
     *                           collection.  If null use the default Horde GC
     *                           time.  If 0 will not be GC'd.
     *
     * @throws Horde_Cache_Exception
     */
    public function set($key, $data, $lifetime = null)
    {
        if (!is_string($data)) {
            throw new Horde_Cache_Exception('Data must be a string.');
        }

        /* Do writes in *reverse* order - it is OK if a write to one of the
         * non-master backends fails. */
        $master = true;

        foreach (array_reverse($this->_stack) as $val) {
            $result = $val->set($key, $data, $lifetime);
            if ($result === false) {
                if ($master) {
                    return;
                }

                /* Attempt to invalidate cache if write failed. */
                $val->expire($id);
            }
            $master = false;
        }
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
        foreach ($this->_stack as $val) {
            $result = $val->exists($key, $lifetime);
            if ($result === true) {
                break;
            }
        }

        return $result;
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
        /* Only report success from master. */
        $master = $success = true;

        foreach (array_reverse($this->_stack) as $val) {
            $result = $val->expire($id);
            if ($master && ($result === false)) {
                $success = false;
            }
            $master = false;
        }

        return $success;
    }
}
