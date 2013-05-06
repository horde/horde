<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */

/**
 * Implementation of HashTable for a Memcache server.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Memcache
 */
class Horde_HashTable_Memcache extends Horde_HashTable implements Horde_HashTable_Lock
{
    /**
     * Memcache object.
     *
     * @var Horde_Memcache
     */
    protected $_memcache;

    /**
     * @param array $params  Additional configuration parameters:
     * <pre>
     *   - memcache: (Horde_Memcache) [REQUIRED] Memcache object.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['memcache'])) {
            throw InvalidArgumentException('Missing memcache parameter.');
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _init()
    {
        $this->_memcache = $this->_params['memcache'];
    }

    /**
     */
    protected function _delete($keys)
    {
        $ret = true;

        foreach ($keys as $val) {
            if (!$this->_memcache->delete($val)) {
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     */
    protected function _get($keys)
    {
        return (($res = $this->_memcache->get($keys)) === false)
            ? array_fill_keys($keys, false)
            : $res;
    }

    /**
     */
    protected function _set($key, $val, $opts)
    {
        return empty($opts['replace'])
            ? $this->_memcache->set($key, $val, isset($opts['expire']) ? $opts['expire'] : 0)
            : $this->_memcache->replace($key, $val, isset($opts['expire']) ? $opts['expire'] : 0);
    }

    /**
     */
    public function lock($key)
    {
        $this->_memcache->lock($key);
    }

    /**
     */
    public function unlock($key)
    {
        $this->_memcache->unlock($key);
    }

    /**
     */
    public function clear($prefix = null)
    {
        $this->_memcache->flush();
    }

}
