<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */

/**
 * Cache storage using the Horde_HashTable interface.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
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
     * NOTE: This driver ignores the lifetime argument.
     */
    public function get($key, $lifetime = 0)
    {
        return $this->_hash->get($this->_params['prefix'] . $key);
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $this->_hash->set($this->_params['prefix'] . $key, $data, array_filter(array(
            'timeout' => $lifetime
        )));
    }

    /**
     * NOTE: This driver ignores the lifetime argument.
     */
    public function exists($key, $lifetime = 0)
    {
        return $this->_hash->exists($this->_params['prefix'] . $key);
    }

    /**
     */
    public function expire($key)
    {
        $this->_hash->delete($this->_params['prefix'] . $key);
    }

    /**
     */
    public function clear()
    {
        $this->_hash->clear();
    }

}
