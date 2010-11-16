<?php
/**
 * This class provides cache storage in a memcache installation.
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
class Horde_Cache_Storage_Memcache extends Horde_Cache_Storage_Base implements Serializable
{
    /**
     * Cache results of expire() calls (since we will get the entire object
     * on an expire() call anyway).
     *
     * @var array
     */
    protected $_expirecache = array();

    /**
     * Memcache object.
     *
     * @var Horde_Memcache
     */
    protected $_memcache;

    /**
     * Construct a new Horde_Cache_Memcache object.
     *
     * @param array $params  Parameter array:
     * <pre>
     * 'memcache' - (Horde_Memcache) [REQUIRED] A Horde_Memcache object.
     * 'prefix' - (string) The prefix to use for the cache keys.
     *            DEFAULT: ''
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['memcache'])) {
            throw new InvalidArgumentException('Missing memcache object');
        }

        $this->_memcache = $params['memcache'];
        unset($params['memcache']);

        parent::__construct(array_merge(array(
            'prefix' => '',
        ), $params));
    }

    /**
     */
    public function get($key, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;
        if (isset($this->_expirecache[$key])) {
            return $this->_expirecache[$key];
        }

        $key_list = array($key);
        if (!empty($lifetime)) {
            $key_list[] = $key . '_e';
        }

        $res = $this->_memcache->get($key_list);

        if ($res === false) {
            unset($this->_expirecache[$key]);
        } else {
            // If we can't find the expire time, assume we have exceeded it.
            if (empty($lifetime) ||
                (($res[$key . '_e'] !== false) && ($res[$key . '_e'] + $lifetime > time()))) {
                $this->_expirecache[$key] = $res[$key];
            } else {
                $res[$key] = false;
                $this->expire($key);
            }
        }

        return $res[$key];
    }

    /**
     */
    public function set($key, $data, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;

        if ($this->_memcache->set($key . '_e', time(), $lifetime) !== false) {
            $this->_memcache->set($key, $data, $lifetime);
        }
    }

    /**
     */
    public function exists($key, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;

        return ($this->get($key, $lifetime) !== false);
    }

    /**
     */
    public function expire($key)
    {
        $key = $this->_params['prefix'] . $key;
        unset($this->_expirecache[$key]);
        $this->_memcache->delete($key . '_e');

        return $this->_memcache->delete($key);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize(array(
            $this->_memcache,
            $this->_params
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list($this->_memcache, $this->_params) = unserialize($data);
    }

}
