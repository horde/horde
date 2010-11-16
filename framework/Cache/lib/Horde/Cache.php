<?php
/**
 * This class provides the API interface to the cache storage drivers.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
class Horde_Cache
{
    /**
     * Cache parameters.
     *
     * @var array
     */
    protected $_params = array(
        'compress' => false,
        'lifetime' => 86400
    );

    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Storage object.
     *
     * @var Horde_Cache_Storage
     */
    protected $_storage;

    /**
     * Constructor.
     *
     * @param Horde_Cache_Storage $storage  The storage object.
     * @param array $params                 Parameter array:
     * <pre>
     * 'compress' - (boolean) Compress data? Requires the 'lzf' PECL
     *              extension.
     *              DEFAULT: false
     * 'lifetime' - (integer) Lifetime of data, in seconds.
     *              DEFAULT: 86400 seconds
     * 'logger' - (Horde_Log_Logger) Log object to use for log/debug messages.
     * </pre>
     */
    public function __construct(Horde_Cache_Storage $storage,
                                array $params = array())
    {
        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);

            $storage->setLogger($this->_logger);
        }

        if (!empty($params['compress']) && !extension_loaded('lzf')) {
            unset($params['compress']);
        }

        $this->_params = array_merge($this->_params, $params);
        $this->_storage = $storage;
    }

    /**
     * Attempts to directly output a cached object.
     *
     * @param string $key        Object ID to query.
     * @param integer $lifetime  Lifetime of the object in seconds.
     *
     * @return boolean  True if output or false if no object was found.
     */
    public function output($key, $lifetime = 1)
    {
        $data = $this->get($key, $lifetime);
        if ($data === false) {
            return false;
        }

        echo $data;
        return true;
    }

    /**
     * Retrieve cached data.
     *
     * @param string $key        Object ID to query.
     * @param integer $lifetime  Lifetime of the object in seconds.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    public function get($key, $lifetime = 1)
    {
        $res = $this->_storage->get($key, $lifetime);

        return ($this->_params['compress'] && ($res !== false))
            // lzf_decompress() returns false on error
            ? lzf_decompress($res)
            : $res;
    }

    /**
     * Store an object in the cache.
     *
     * @param string $key        Object ID used as the caching key.
     * @param string $data       Data to store in the cache.
     * @param integer $lifetime  Object lifetime - i.e. the time before the
     *                           data becomes available for garbage
     *                           collection.  If null use the default Horde GC
     *                           time.  If 0 will not be GC'd.
     */
    public function set($key, $data, $lifetime = null)
    {
        if ($this->_params['compress']) {
            $data = lzf_compress($data);
        }
        $lifetime = is_null($lifetime)
            ? $this->_params['lifetime']
            : $lifetime;

        $this->_storage->set($key, $data, $lifetime);
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
        return $this->_storage->exists($key, $lifetime);
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
        return $this->_storage->expire($key);
    }

}
