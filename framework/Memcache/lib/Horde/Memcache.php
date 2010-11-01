<?php
/**
 * The Horde_Memcache:: class provides an API or Horde code to interact with
 * a centrally configured memcache installation.
 *
 * memcached website: http://www.danga.com/memcached/
 *
 * Configuration parameters:
 * <pre>
 * 'compression' - Compress data inside memcache?
 *                 DEFAULT: false
 * 'c_threshold' - The minimum value length before attempting to compress.
 *                 DEFAULT: none
 * 'hostspec'    - The memcached host(s) to connect to.
 *                 DEFAULT: 'localhost'
 * 'large_items' - Allow storing large data items (larger than
 *                 Horde_Memcache::MAX_SIZE)?
 *                 DEFAULT: true
 * 'persistent'  - Use persistent DB connections?
 *                 DEFAULT: false
 * 'prefix'      - The prefix to use for the memcache keys.
 *                 DEFAULT: 'horde'
 * 'port'        - The port(s) memcache is listening on. Leave empty or set
 *                 to 0 if using UNIX sockets.
 *                 DEFAULT: 11211
 * 'weight'      - The weight to use for each memcached host.
 *                 DEFAULT: none (equal weight to all servers)
 * </pre>
 *
 * @TODO Support for new pecl memcached extension:
 * http://gravitonic.com/2009/01/new-memcached-extension,
 * http://pecl.php.net/package/memcached
 * http://www.php.net/manual/en/book.memcached.php
 * http://techportal.ibuildings.com/2009/06/22/new-memcached-extension-for-php/
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Didi Rieder <adrieder@sbox.tugraz.at>
 * @package  Memcache
 */
class Horde_Memcache implements Serializable
{
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
        'compression' => 0,
        'hostspec' => 'localhost',
        'large_items' => true,
        'persistent' => false,
        'port' => 11211,
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
     * @param array $params  TODO
     *
     * @throws Horde_Exception
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
        $this->_params['prefix'] = (empty($this->_params['prefix'])) ? 'horde' : $this->_params['prefix'];

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
        }

        $this->_init();
    }

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
     * @throws Horde_Exception
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

    /**
     * Do initialization.
     *
     * @throws Horde_Exception
     */
    public function _init()
    {
        $servers = array();
        $this->_memcache = new Memcache();
        for ($i = 0, $n = count($this->_params['hostspec']); $i < $n; ++$i) {
            if ($this->_memcache->addServer($this->_params['hostspec'][$i], empty($this->_params['port'][$i]) ? 0 : $this->_params['port'][$i], !empty($this->_params['persistent']), !empty($this->_params['weight'][$i]) ? $this->_params['weight'][$i] : 1)) {
                $servers[] = $this->_params['hostspec'][$i] . (!empty($this->_params['port'][$i]) ? ':' . $this->_params['port'][$i] : '');
            }
        }

        /* Check if any of the connections worked. */
        if (empty($servers)) {
            throw new Horde_Exception('Could not connect to any defined memcache servers.');
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
        if (!empty($this->_params['large_items'])) {
            /* No need to delete the oversized parts - memcache's LRU
             * algorithm will eventually cause these pieces to be recycled. */
            if (!isset($this->_noexist[$key . '_os'])) {
                $this->_memcache->delete($this->_key($key . '_os'), $timeout);
            }
        }
        if (isset($this->_noexist[$key])) {
            return false;
        }
        return $this->_memcache->delete($this->_key($key), $timeout);
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
        $key_map = $os = $os_keys = $out_array = array();
        $ret_array = true;

        if (!is_array($keys)) {
            $keys = array($keys);
            $ret_array = false;
        }
        $search_keys = $keys;

        if (!empty($this->_params['large_items'])) {
            foreach ($keys as $val) {
                $os_keys[$val] = $search_keys[] = $val . '_os';
            }
        }

        foreach ($search_keys as $v) {
            $key_map[$v] = $this->_key($v);
        }

        $res = $this->_memcache->get(array_values($key_map));
        if ($res === false) {
            return false;
        }

        /* Check to see if we have any oversize items we need to get. */
        if (!empty($os_keys)) {
            foreach ($os_keys as $key => $val) {
                if (!empty($res[$key_map[$val]])) {
                    /* This is an oversize key entry. */
                    $os[$key] = $this->_getOSKeyArray($key, $res[$key_map[$val]]);
                }
            }

            if (!empty($os)) {
                $search_keys = $search_keys2 = array();
                foreach ($os as $val) {
                    $search_keys = array_merge($search_keys, $val);
                }

                foreach ($search_keys as $v) {
                    $search_keys2[] = $key_map[$v] = $this->_key($v);
                }

                $res2 = $this->_memcache->get($search_keys2);
                if ($res2 === false) {
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

        $old_error = error_reporting(0);

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
                $out_array[$k] = unserialize($data);
            } elseif (isset($os[$k]) && !isset($res[$key_map[$k]])) {
                $this->delete($k);
            }
        }

        error_reporting($old_error);

        return ($ret_array) ? $out_array : reset($out_array);
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
        $old_error = error_reporting(0);
        $var = serialize($var);
        error_reporting($old_error);

        return $this->_set($key, $var, $expire);
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
            $res = $this->_memcache->set($this->_key($curr_key), substr($var, $i * self::MAX_SIZE, self::MAX_SIZE), empty($this->_params['compression']) ? 0 : MEMCACHE_COMPRESSED, $expire);
            if ($res === false) {
                $this->delete($key);
                $i = 1;
                break;
            }
            unset($this->_noexist[$curr_key]);
        }

        if (($res !== false) && !empty($this->_params['large_items'])) {
            $os_key = $this->_key($key . '_os');
            if (--$i) {
                $this->_memcache->set($os_key, $i, 0, $expire);
            } elseif (!isset($this->_noexist[$key . '_os'])) {
                $this->_memcache->delete($os_key);
            }
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
        $old_error = error_reporting(0);
        $var = serialize($var);
        error_reporting($old_error);
        $len = strlen($var);

        if ($len > self::MAX_SIZE) {
            if (!empty($this->_params['large_items'])) {
                $res = $this->_memcache->get(array($this->_key($key), $this->_key($key . '_os')));
                if (!empty($res)) {
                    return $this->_set($key, $var, $expire, $len);
                }
            }
            return false;
        }

        if ($this->_memcache->replace($this->_key($key), $var, empty($this->_params['compression']) ? 0 : MEMCACHE_COMPRESSED, $expire)) {
            if (!empty($this->_params['large_items']) &&
                !isset($this->_noexist[$key . '_os'])) {
                $this->_memcache->delete($this->_key($key . '_os'));
            }
            return true;
        }

        return false;
    }

    /**
     * Obtain lock on a key.
     *
     * @param string $key  The key to lock.
     */
    public function lock($key)
    {
        /* Lock will automatically expire after 10 seconds. */
        while ($this->_memcache->add($this->_key($key . '_l'), 1, 0, 10) === false) {
            /* Wait 0.005 secs before attempting again. */
            usleep(5000);
        }
    }

    /**
     * Release lock on a key.
     *
     * @param string $key  The key to lock.
     */
    public function unlock($key)
    {
        $this->_memcache->delete($this->_key($key . '_l'), 0);
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

}
