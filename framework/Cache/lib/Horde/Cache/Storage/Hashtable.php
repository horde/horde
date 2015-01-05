<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */

/**
 * Cache storage using the Horde_HashTable interface.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 * @since     2.2.0
 */
class Horde_Cache_Storage_Hashtable extends Horde_Cache_Storage_Base
{
    /**
     * HashTable object.
     *
     * @var Horde_HashTable
     */
    protected $_hash;

    /**
     * @param array $params  Additional parameters:
     * <pre>
     *   - hashtable: (Horde_HashTable) [REQUIRED] A Horde_HashTable object.
     *   - prefix: (string) The prefix to use for the cache keys.
     *             DEFAULT: ''
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['hashtable'])) {
            throw new InvalidArgumentException('Missing hashtable parameter.');
        }

        parent::__construct(array_merge(array(
            'prefix' => ''
        ), $params));
    }

    /**
     */
    protected function _initOb()
    {
        $this->_hash = $this->_params['hashtable'];
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        $dkey = $this->_getKey($key);
        $query = array($dkey);
        if ($lifetime) {
            $query[] = $lkey = $this->_getKey($key, true);
        }

        $res = $this->_hash->get($query);

        if ($lifetime &&
            (!$res[$lkey] || (($lifetime + $res[$lkey]) < time()))) {
            return false;
        }

        return $res[$dkey];
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $opts = array_filter(array(
            'expire' => $lifetime
        ));

        $this->_hash->set($this->_getKey($key), $data, $opts);
        $this->_hash->set($this->_getKey($key, true), time(), $opts);
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        return ($this->get($key, $lifetime) !== false);
    }

    /**
     */
    public function expire($key)
    {
        $this->_hash->delete(array(
            $this->_getKey($key),
            $this->_getKey($key, true)
        ));
    }

    /**
     */
    public function clear()
    {
        $this->_hash->clear();
    }

    /**
     * Return the hashtable key.
     *
     * @param string $key  Object ID.
     * @param boolean $ts  Return the timestamp key?
     *
     * @return string  Hashtable key ID.
     */
    protected function _getKey($key, $ts = false)
    {
        return $this->_params['prefix'] . $key . ($ts ? '_t' : '');
    }

}
