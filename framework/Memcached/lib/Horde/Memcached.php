<?php
/**
 * This class provides an API or Horde code to interact with a centrally
 * configured memcached installation.
 *
 * memcached website: http://www.danga.com/memcached/
 *
 * Copyright 2007-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Carlos Pires <acmpires@sapo.pt>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Memcached
 */
class Horde_Memcached implements Serializable
{
    /**
     * Locking timeout.
     */
    const LOCK_TIMEOUT = 30;

    /**
     * Suffix added to key to create the lock entry.
     */
    const LOCK_SUFFIX = '_l';

    /**
     * The max storage size of the memcached server. This should be slightly
     * smaller than the actual value due to overhead. By default, the max
     * slab size of memcached (as of 1.1.2) is 1 MB.
     */
    const MAX_SIZE = 1000000;

    /**
     * Serializable version.
     */
    const VERSION = 2;

    /**
     * Locked keys.
     *
     * @var array
     */
    protected $_locks = array();

    /**
     * Logger instance.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Memcache object.
     *
     * @var Memcached
     */
    protected $_memcached;

    /**
     * A list of items known not to exist.
     *
     * @var array
     */
    protected $_noexist = array();

    /**
     * Memcached defaults.
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
     * The list of active servers.
     *
     * @var array
     */
    protected $_servers = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - compression: (boolean) Compress data inside memcached?
     *                  DEFAULT: false
     *   - c_threshold: (integer) The minimum value length before attempting
     *                  to compress.
     *                  DEFAULT: none
     *   - hostspec: (array) The memcached host(s) to connect to.
     *                  DEFAULT: 'localhost'
     *   - large_items: (boolean) Allow storing large data items (larger than
     *                  Horde_Memcache::MAX_SIZE)?
     *                  DEFAULT: true
     *   - persistent: (boolean) Use persistent Memcached connections?
     *                 DEFAULT: false
     *   - prefix: (string) The prefix to use for the memcached keys.
     *             DEFAULT: 'horde'
     *   - port: (array) The port(s) memcached is listening on. Leave empty
     *           if using UNIX sockets.
     *           DEFAULT: 11211
     *   - weight: (array) The weight(s) to use for each memcached host.
     *             DEFAULT: none (equal weight to all servers)
     *
     * @throws Horde_Memcached_Exception
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
        $this->_init();
    }

    /**
     * Do initialization.
     *
     * @throws Horde_Memcache_Exception
     */
    public function _init()
    {
        if (isset($this->_params['logger'])) {
            $this->_logger = $this->_params['logger'];
        }

        if (!empty($this->_params['compression'])) {
            ini_set('memcached.compression_type', 'zlib');
        }

        if (!empty($this->_params['c_threshold'])) {
            ini_set('memcached.compression_threshold', $this->_params['c_threshold']);
        }

        // Force consistent hashing
        ini_set('memcached.default_consistent_hash', 'On');

        $this->_memcached = new Memcached();

        $servers = array();
        foreach ($this->_params['hostspec'] as $k => $server) {
            $servers[] = array(
                $server,
                empty($this->_params['port'][$k])    ? 0 : $this->_params['port'][$k],
                !empty($this->_params['weight'][$k]) ? $this->_params['weight'][$k] : 1,
            );
        }

        if ($this->_memcached->isPristine()) {
            $this->_memcached->setOptions(array(
                Memcached::OPT_NO_BLOCK => true,
                Memcached::OPT_TCP_NODELAY => true,
                Memcached::OPT_BUFFER_WRITES => true,
                Memcached::OPT_BINARY_PROTOCOL => true,
                Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
                Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
            ));
            if (!empty($this->_params['compression'])) {
                $this->_memcached->setOption(Memcached::OPT_COMPRESSION, true);
            }
            $this->_memcached->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 15);
            $this->_memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500); // in milliseconds
            $this->_memcached->setOption(Memcached::OPT_RETRY_TIMEOUT, 1);     // in seconds
        }

        if (count($this->_memcached->getServerList()) == 0) {
            $res = $this->_memcached->addServers($servers);
            $this->_memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            $this->_memcached->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 15);
            $this->_memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500); // in milliseconds
            $this->_memcached->setOption(Memcached::OPT_RETRY_TIMEOUT, 1);     // in seconds

            if (!empty($this->_params['compression'])) {
                $this->_memcached->setOption(Memcached::OPT_COMPRESSION, true);
            }

            if ($res) {
                foreach ($servers as $k => $server) {
                    $this->_servers[] = $server[0] . ':' . $server[1];
                }
            }
        }
        $this->_logger->log('Connected to the following memcache servers:' . implode($this->_servers, ', '), 'DEBUG');

        /* Check if any of the connections worked. */
        if (empty($this->_servers)) {
            $this->_logger->log('Memcached servers connect error "Could not connect to any defined memcache servers"', 'DEBUG');
            throw new Horde_Memcached_Exception('Could not connect to any defined memcache servers.');
        }
    }

    /**
     * Shutdown function.
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
            : $this->_memcached->delete($this->_key($key), $timeout);
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
        $key_map = $out_array = array();
        $ret_array = true;

        if (!is_array($keys)) {
            $keys = array($keys);
            $ret_array = false;
        }
        $search_keys = $keys;

        foreach ($search_keys as $v) {
            $key_map[$v] = $this->_key($v);
        }

        $res = $this->_memcached->getMulti(array_values($key_map), null, Memcached::GET_PRESERVE_ORDER);
        if ($this->_memcached->getResultCode() !== Memcached::RES_SUCCESS || $res === false) {
            return false;
        }

        /* Check to see if we have any oversize items we need to get. */
        if (!empty($this->_params['large_items']) && $res !== false) {
            foreach ($res as $key => $val) {
                $missing = array();
                $delete = false;
                if (preg_match('/^total_large_items:(\d+)/', $val, $match)) {
                    $missing = $this->_getOSKeyArray($key, $match[1]);
                }

                if (!empty($missing)) {
                    $missing_map = array();
                    foreach ($missing as $v) {
                        $missing_map[$v] = $this->_key($v);
                    }

                    $res2 = $this->_memcached->getMulti(array_values($missing_map), null, Memcached::GET_PRESERVE_ORDER);
                    if ($this->_memcached->getResultCode() !== Memcached::RES_SUCCESS || $res2 === false) {
                        $delete = true;
                    }

                    $data = '';
                    if (count($missing_map) !== count($res2)) {
                        // Missing some parts, so mark item to be unset and remove all parts
                        $delete = true;
                    }

                    if (!$delete) {
                        foreach ($res2 as $k => $v) {
                            if (strlen($v) === 0) {
                                // Some multi-key part is empty and this makes data to be invalid,
                                //  so mark item to be unset and remove all parts
                                $delete = true;
                                continue;
                            }
                            $data .= $v;
                        }
                    }

                    if ($delete) {
                        $this->_noexist[$key] = true;
                        $this->delete($key);
                        unset($res[$key]);

                        if ($res2 !== false) {
                            foreach ($res2 as $k => $v) {
                                $this->_noexist[$k] = true;
                                $this->delete($k);
                            }
                        }
                        continue;
                    }

                    $res[$key] = $data;
                }
            }
        }

        foreach ($key_map as $k => $v) {
            if (!isset($res[$v])) {
                $this->_noexist[$k] = true;
            }
        }

        foreach ($keys as $key) {
            if (isset($res[$key_map[$key]])) {
                $out_array[$key] = @unserialize($res[$key_map[$key]]);
            }
        }

        return $ret_array
            ? $out_array
            : reset($out_array);
    }

    /**
     * Set the value of a key.
     *
     * @see Memcached::set()
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
        if ($len === null) {  // faster then is_null($len)
            $len = strlen($var);
        }

        if (empty($this->_params['large_items']) && ($len > self::MAX_SIZE)) {
            Horde::logMessage('Key discarted due big size', 'NOTICE');
            return false;
        }

        $items = array();
        if ($len > self::MAX_SIZE) {
            $items[$this->_key($key)] = 'total_large_items:' . ceil($len / self::MAX_SIZE);
            for ($i = 0; ($i * self::MAX_SIZE) < $len; ++$i) {
                 $curr_key = $this->_key($key) . '_s' . $i;
                 $items[$this->_key($curr_key)] = substr($var, $i * self::MAX_SIZE, self::MAX_SIZE);
            }
        } else {
            $items[$this->_key($key)] = $var;
        }

        $res = $this->_memcached->setMulti($items, $expire);
        if ($res === false) {
            $this->delete($key);
        }

        foreach ($items as $k => $v) {
            unset($this->_noexist[$k]);
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
                $this->_memcached->get($this->_key($key))) {
                return $this->_set($key, $var, $expire, $len);
            }
            return false;
        }

        return $this->_memcached->replace($this->_key($key), $var, $expire);
    }

    /**
     * Obtain lock on a key.
     *
     * @param string $key  The key to lock.
     */
    public function lock($key)
    {
        $i = 0;

        while ($this->_memcached->add($this->_key($key . self::LOCK_SUFFIX), 1, self::LOCK_TIMEOUT) === false) {
            usleep(min(pow(2, $i++) * 10000, 100000));
        }

        /* Register a shutdown handler function here to catch cases where PHP
         * suffers a fatal error. Must be done via shutdown function, since
         * a destructor will not be called in this case.
         * Only trigger on error, since we must assume that the code that
         * locked will also handle unlocks (which may occur in the destruct
         * phase, e.g. session handling).
         * @todo: $this is not usable in closures until PHP 5.4+ */
        if (empty($this->_locks)) {
            $self = $this;
            register_shutdown_function(function() use ($self) {
                $e = error_get_last();
                if ($e['type'] & E_ERROR) {
                    /* Try to do cleanup at very end of shutdown methods. */
                    register_shutdown_function(array($self, 'shutdown'));
                }
            });
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
        $this->_memcached->delete($this->_key($key . '_l'), 0);
        unset($this->_locks[$key]);
    }

    /**
     * Mark all entries on a memcached installation as expired.
     */
    public function flush()
    {
        //$this->_memcached->flush();
    }

    /**
     * Get the statistics output from the current memcached pool.
     *
     * @return array  The output from Memcached::getExtendedStats() using the
     *                current configuration values.
     */
    public function stats()
    {
        return $this->_memcached->getStats();
    }

    /**
     * Obtains the md5 sum for a key.
     *
     * @param string $key  The key.
     *
     * @return string  The corresponding memcached key.
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
            $ret[] = $key . '_s' . $i;
        }
        return $ret;
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
            $this->_params
        ));
    }

    /**
     * Unserialize.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     * @throws Horde_Memcached_Exception
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

        $this->_init();
    }

}
