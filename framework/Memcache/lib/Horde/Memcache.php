<?php
/**
 * This class provides an API or Horde code to interact with a centrally
 * configured memcache installation.
 *
 * memcached website: http://www.danga.com/memcached/
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Didi Rieder <adrieder@sbox.tugraz.at>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Memcache
 */
class Horde_Memcache implements Serializable
{
    /**
     * The number of bits reserved by PHP's memcache layer for internal flag
     * use.
     */
    const FLAGS_RESERVED = 16;

    /**
     * Locking timeout.
     *
     * @since 1.1.1
     */
    const LOCK_TIMEOUT = 30;

    /**
     * Suffix added to key to create the lock entry.
     *
     * @since 1.1.0
     */
    const LOCK_SUFFIX = '_l';

    /**
     * The max storage size of the memcache server.  This should be slightly
     * smaller than the actual value due to overhead.  By default, the max
     * slab size of memcached (as of 1.1.2) is 1 MB.
     */
    const MAX_SIZE = 1000000;

    /**
     * Serializable version.
     */
    const VERSION = 1;

    /**
     * Locked keys.
     *
     * @var array
     */
    protected $_locks = array();

    /**
     * Memcache object.
     *
     * @var Memcache
     */
    protected $_memcache;

    /**
     * Memcache defaults.
     *
     * @var array
     */
    protected $_params = array(
        'compression' => false,
        'hostspec' => array('localhost'),
        'large_items' => true,
        'persistent' => false,
        'port' => array(11211),
        'prefix' => 'horde'
    );

    /**
     * A list of items known not to exist.
     *
     * @var array
     */
    protected $_noexist = array();

    /**
     * Logger instance.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - compression: (boolean) Compress data inside memcache?
     *                  DEFAULT: false
     *   - c_threshold: (integer) The minimum value length before attempting
     *                  to compress.
     *                  DEFAULT: none
     *   - hostspec: (array) The memcached host(s) to connect to.
     *                  DEFAULT: 'localhost'
     *   - large_items: (boolean) Allow storing large data items (larger than
     *                  Horde_Memcache::MAX_SIZE)?
     *                  DEFAULT: true
     *   - persistent: (boolean) Use persistent DB connections?
     *                 DEFAULT: false
     *   - prefix: (string) The prefix to use for the memcache keys.
     *             DEFAULT: 'horde'
     *   - port: (array) The port(s) memcache is listening on. Leave empty
     *           if using UNIX sockets.
     *           DEFAULT: 11211
     *   - weight: (array) The weight(s) to use for each memcached host.
     *             DEFAULT: none (equal weight to all servers)
     *
     * @throws Horde_Memcache_Exception
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
        }

        $this->_init();

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Do initialization.
     *
     * @throws Horde_Memcache_Exception
     */
    public function _init()
    {
        $this->_memcache = new Memcache();

        $servers = array();
        for ($i = 0, $n = count($this->_params['hostspec']); $i < $n; ++$i) {
            if ($this->_memcache->addServer($this->_params['hostspec'][$i], empty($this->_params['port'][$i]) ? 0 : $this->_params['port'][$i], !empty($this->_params['persistent']), !empty($this->_params['weight'][$i]) ? $this->_params['weight'][$i] : 1)) {
                $servers[] = $this->_params['hostspec'][$i] . (!empty($this->_params['port'][$i]) ? ':' . $this->_params['port'][$i] : '');
            }
        }

        /* Check if any of the connections worked. */
        if (empty($servers)) {
            throw new Horde_Memcache_Exception('Could not connect to any defined memcache servers.');
        }

        if (!empty($this->_params['c_threshold'])) {
            $this->_memcache->setCompressThreshold($this->_params['c_threshold']);
        }

        // Force consistent hashing
        ini_set('memcache.hash_strategy', 'consistent');

        if ($this->_logger) {
            $this->_logger->log('Connected to the following memcache servers:' . implode($servers, ', '), 'DEBUG');
        }
    }

    /**
     * Shutdown function.
     *
     * @since 1.1.0
     */
    public function shutdown()
    {
        foreach (array_keys($this->_locks) as $key) {
            $this->unlock($key);
        }
    }

    /**
     * Delete a key.
     *
     * @see Memcache::delete()
     *
     * @param string $key       The key.
     * @param integer $timeout  Expiration time in seconds.
     *
     * @return boolean  True on success.
     */
    public function delete($key, $timeout = 0)
    {
        return isset($this->_noexist[$key])
            ? false
            : $this->_memcache->delete($this->_key($key), $timeout);
    }

    /**
     * Get data associated with a key.
     *
     * @see Memcache::get()
     *
     * @param mixed $keys  The key or an array of keys.
     *
     * @return mixed  The string/array on success (return type is the type of
     *                $keys), false on failure.
     */
    public function get($keys)
    {
        $flags = null;
        $key_map = $missing_parts = $os = $out_array = array();
        $ret_array = true;

        if (!is_array($keys)) {
            $keys = array($keys);
            $ret_array = false;
        }
        $search_keys = $keys;

        foreach ($search_keys as $v) {
            $key_map[$v] = $this->_key($v);
        }

        if (($res = $this->_memcache->get(array_values($key_map), $flags)) === false) {
            return false;
        }

        /* Check to see if we have any oversize items we need to get. */
        if (!empty($this->_params['large_items'])) {
            foreach ($key_map as $key => $val) {
                $part_count = isset($flags[$val])
                    ? ($flags[$val] >> self::FLAGS_RESERVED) - 1
                    : -1;

                switch ($part_count) {
                case -1:
                    /* Ignore. */
                    unset($res[$val]);
                    break;

                case 0:
                    /* Not an oversize part. */
                    break;

                default:
                    $os[$key] = $this->_getOSKeyArray($key, $part_count);
                    foreach ($os[$key] as $val2) {
                        $missing_parts[] = $key_map[$val2] = $this->_key[$val2];
                    }
                    break;
                }
            }

            if (!empty($missing_parts)) {
                if (($res2 = $this->_memcache->get($missing_parts)) === false) {
                    return false;
                }

                /* $res should now contain the same results as if we had
                 * run a single get request with all keys above. */
                $res = array_merge($res, $res2);
            }
        }

        foreach ($key_map as $k => $v) {
            if (!isset($res[$v])) {
                $this->_noexist[$k] = true;
            }
        }

        foreach ($keys as $k) {
            $out_array[$k] = false;
            if (isset($res[$key_map[$k]])) {
                $data = $res[$key_map[$k]];
                if (isset($os[$k])) {
                    foreach ($os[$k] as $v) {
                        if (isset($res[$key_map[$v]])) {
                            $data .= $res[$key_map[$v]];
                        } else {
                            $this->delete($k);
                            continue 2;
                        }
                    }
                }
                $out_array[$k] = @unserialize($data);
            } elseif (isset($os[$k]) && !isset($res[$key_map[$k]])) {
                $this->delete($k);
            }
        }

        return $ret_array
            ? $out_array
            : reset($out_array);
    }

    /**
     * Set the value of a key.
     *
     * @see Memcache::set()
     *
     * @param string $key       The key.
     * @param string $var       The data to store.
     * @param integer $timeout  Expiration time in seconds.
     *
     * @return boolean  True on success.
     */
    public function set($key, $var, $expire = 0)
    {
        return $this->_set($key, @serialize($var), $expire);
    }

    /**
     * Set the value of a key.
     *
     * @param string $key       The key.
     * @param string $var       The data to store (serialized).
     * @param integer $timeout  Expiration time in seconds.
     * @param integer $lent     String length of $len.
     *
     * @return boolean  True on success.
     */
    protected function _set($key, $var, $expire = 0, $len = null)
    {
        if (is_null($len)) {
            $len = strlen($var);
        }

        if (empty($this->_params['large_items']) && ($len > self::MAX_SIZE)) {
            return false;
        }

        for ($i = 0; ($i * self::MAX_SIZE) < $len; ++$i) {
            $curr_key = $i ? ($key . '_s' . $i) : $key;

            $flags = $this->_getFlags($i ? 0 : ceil($len / self::MAX_SIZE));
            $res = $this->_memcache->set($this->_key($curr_key), substr($var, $i * self::MAX_SIZE, self::MAX_SIZE), $flags, $expire);
            if ($res === false) {
                $this->delete($key);
                break;
            }
            unset($this->_noexist[$curr_key]);
        }

        return $res;
    }

    /**
     * Replace the value of a key.
     *
     * @see Memcache::replace()
     *
     * @param string $key       The key.
     * @param string $var       The data to store.
     * @param integer $timeout  Expiration time in seconds.
     *
     * @return boolean  True on success, false if key doesn't exist.
     */
    public function replace($key, $var, $expire = 0)
    {
        $var = @serialize($var);
        $len = strlen($var);

        if ($len > self::MAX_SIZE) {
            if (!empty($this->_params['large_items']) &&
                $this->_memcache->get($this->_key($key))) {
                return $this->_set($key, $var, $expire, $len);
            }
            return false;
        }

        return $this->_memcache->replace($this->_key($key), $var, $this->_getFlags(1), $expire);
    }

    /**
     * Obtain lock on a key.
     *
     * @param string $key  The key to lock.
     */
    public function lock($key)
    {
        while ($this->_memcache->add($this->_key($key . self::LOCK_SUFFIX), 1, 0, self::LOCK_TIMEOUT) === false) {
            /* Wait 0.1 secs before attempting again. */
            usleep(100000);
        }

        $this->_locks[$key] = true;
    }

    /**
     * Release lock on a key.
     *
     * @param string $key  The key to lock.
     */
    public function unlock($key)
    {
        $this->_memcache->delete($this->_key($key . self::LOCK_SUFFIX), 0);
        unset($this->_locks[$key]);
    }

    /**
     * Mark all entries on a memcache installation as expired.
     */
    public function flush()
    {
        $this->_memcache->flush();
    }

    /**
     * Get the statistics output from the current memcache pool.
     *
     * @return array  The output from Memcache::getExtendedStats() using the
     *                current configuration values.
     */
    public function stats()
    {
        return $this->_memcache->getExtendedStats();
    }

    /**
     * Obtains the md5 sum for a key.
     *
     * @param string $key  The key.
     *
     * @return string  The corresponding memcache key.
     */
    protected function _key($key)
    {
        return hash('md5', $this->_params['prefix'] . $key);
    }

    /**
     * Returns the key listing of all key IDs for an oversized item.
     *
     * @return array  The array of key IDs.
     */
    protected function _getOSKeyArray($key, $length)
    {
        $ret = array();
        for ($i = 0; $i < $length; ++$i) {
            $ret[] = $key . '_s' . ($i + 1);
        }
        return $ret;
    }

    /**
     * Get flags for memcache call.
     *
     * @param integer $count
     *
     * @return integer
     */
    protected function _getFlags($count)
    {
        $flags = empty($this->_params['compression'])
            ? 0
            : MEMCACHE_COMPRESSED;
        return ($flags | $count << self::FLAGS_RESERVED);
    }

    /* Serializable methods. */

    /**
     * Serialize.
     *
     * @return string  Serialized representation of this object.
     */
    public function serialize()
    {
        return serialize(array(
            self::VERSION,
            $this->_params,
            $this->_logger
        ));
    }

    /**
     * Unserialize.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     * @throws Horde_Memcache_Exception
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_params = $data[1];
        $this->_logger = $data[2];

        $this->_init();
    }

}
