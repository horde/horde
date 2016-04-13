<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */

/**
 * Implementation of HashTable for a Memcached server.
 *
 * @author    Carlos Pires <acmpires@sapo.pt>
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */
class Horde_HashTable_Memcached
extends Horde_HashTable_Base
implements Horde_HashTable_Lock
{
    /**
     * Memcache object.
     *
     * @var Horde_Memcached
     */
    protected $_memcached;

    /**
     * @param array $params  Additional configuration parameters:
     * <pre>
     *   - memcached: (Horde_Memcached) [REQUIRED] Memcached object.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['memcached'])) {
            throw new InvalidArgumentException('Missing memcached parameter.');
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _init()
    {
        $this->_memcached = $this->_params['memcached'];
    }

    /**
     */
    protected function _delete($keys)
    {
        $ret = true;

        foreach ($keys as $val) {
            if (!$this->_memcached->delete($val)) {
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     */
    protected function _exists($keys)
    {
        $out = array();

        foreach ($this->_get($keys) as $key => $val) {
            $out[$key] = ($val !== false);
        }

        return $out;
    }

    /**
     */
    protected function _get($keys)
    {
        return (($res = $this->_memcached->get($keys)) === false)
            ? array_fill_keys($keys, false)
            : $res;
    }

    /**
     */
    protected function _set($key, $val, $opts)
    {
        return empty($opts['replace'])
            ? $this->_memcached->set($key, $val, isset($opts['expire']) ? $opts['expire'] : 0)
            : $this->_memcached->replace($key, $val, isset($opts['expire']) ? $opts['expire'] : 0);
    }

    /**
     */
    public function lock($key)
    {
        $this->_memcached->lock($key);
    }

    /**
     */
    public function unlock($key)
    {
        $this->_memcached->unlock($key);
    }

    /**
     */
    public function clear()
    {
        // No way to delete keys via memcache - have to drop entire DB.
        $this->_memcached->flush();
    }

    /* Unique driver methods. */

    /**
     * Get the statistics output from the current memcache pool.
     *
     * @see Horde_Memcached#stats()
     */
    public function stats()
    {
        return $this->_memcached->stats();
    }

}
