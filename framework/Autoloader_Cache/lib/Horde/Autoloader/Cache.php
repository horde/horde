<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader_Cache
 */

require_once 'Horde/Autoloader/Default.php';

/**
 * Decorator for Horde_Autoloader that implements caching of class-file-maps.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader_Cache
 */
class Horde_Autoloader_Cache extends Horde_Autoloader_Default
{
    /* Cache types. */
    const APC = 1;
    const XCACHE = 2;
    const EACCELERATOR = 3;
    const TEMPFILE = 4;

    /* Cache key prefix. */
    const PREFIX = 'horde_autoloader_cache';

    /* Key that holds list of autoloader cache keys. */
    const KEYLIST = 'horde_autoloader_keys';

    /**
     * Map of all classes already looked up.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Cache key name.
     *
     * @var string
     */
    protected $_cachekey = self::PREFIX;

    /**
     * The cache type.
     *
     * @var array
     */
    protected $_cachetype;

    /**
     * Has the cache changed since the last save?
     *
     * @var boolean
     */
    protected $_changed = false;

    /**
     * Is this a new key?
     *
     * @var boolean
     */
    protected $_newkey = false;

    /**
     * Cached value of the temporary directory.
     *
     * @var string
     */
    protected $_tempdir;

    /**
     * Constructor.
     *
     * Tries all supported cache backends and tries to retrieve the cached
     * class map.
     */
    public function __construct()
    {
        parent::__construct();

        if (isset($_SERVER['SERVER_NAME'])) {
            $this->_cachekey .= '|' . $_SERVER['SERVER_NAME'];
        }
        $this->_cachekey .= '|' . __FILE__;

        $data = null;

        if (extension_loaded('apc')) {
            $data = apc_fetch($this->_cachekey);
            $this->_cachetype = self::APC;
        } elseif (extension_loaded('xcache')) {
            $data = xcache_get($this->_cachekey);
            $this->_cachetype = self::XCACHE;
        } elseif (extension_loaded('eaccelerator')) {
            $data = eaccelerator_get($this->_cachekey);
            $this->_cachetype = self::EACCELERATOR;
        } elseif (($tempdir = sys_get_temp_dir()) && is_readable($tempdir)) {
            $this->_tempdir = $tempdir;
            /* For files, add cachekey prefix for easy filesystem
             * identification. */
            $this->_cachekey = self::PREFIX . '_' . hash('sha1', $this->_cachekey);
            if (($data = @file_get_contents($tempdir . '/' . $this->_cachekey)) === false) {
                unlink($tempdir . '/' . $this->_cachekey);
            }
            $this->_cachetype = self::TEMPFILE;
        }

        if ($data) {
            if (extension_loaded('horde_lz4')) {
                $data = @horde_lz4_uncompress($data);
            } elseif (extension_loaded('lzf')) {
                $data = @lzf_decompress($data);
            }

            if ($data !== false) {
                $data = extension_loaded('msgpack')
                    ? msgpack_unpack($data)
                    : @json_decode($data, true);
                if (is_array($data)) {
                    $this->_cache = $data;
                }
            }
        } else {
            $this->_newkey = true;
        }
    }

    /**
     * Destructor.
     *
     * Attempts to save the class map to the cache.
     */
    public function __destruct()
    {
        if (!$this->_changed || !$this->_cachetype) {
            return;
        }

        $data = extension_loaded('msgpack')
            ? msgpack_pack($this->_cache)
            : json_encode($this->_cache);
        if (extension_loaded('horde_lz4')) {
            $data = horde_lz4_compress($data);
        } elseif (extension_loaded('lzf')) {
            $data = lzf_compress($data);
        }

        switch ($this->_cachetype) {
        case self::APC:
            apc_store($this->_cachekey, $data);
            break;

        case self::XCACHE:
            xcache_set($this->_cachekey, $data);
            break;

        case self::EACCELERATOR:
            eaccelerator_put($this->_cachekey, $data);
            break;

        case self::TEMPFILE:
            if (!file_put_contents($this->_tempdir . '/' . $this->_cachekey, $data)) {
                error_log('Cannot write Autoloader cache file to system temp directory: ' . $this->_tempdir, 4);
            }
            break;
        }

        if ($this->_newkey) {
            $keylist = $this->_getKeylist();
            $keylist[] = $this->_cachekey;
            $this->_saveKeylist($keylist);
        }
    }

    /**
     * Search registered mappers in LIFO order.
     *
     * @param string $className  Classname.
     *
     * @return string  Path.
     */
    public function mapToPath($className)
    {
        if (isset($this->_cache[$className])) {
            return $this->_cache[$className];
        }

        if ($res = parent::mapToPath($className)) {
            $this->_cache[$className] = $res;
            $this->_changed = true;
        }

        return $res;
    }

    /**
     * Prunes the autoloader cache.
     *
     * @return boolean  True if pruning succeeded.
     */
    public function prune()
    {
        foreach (array_unique(array_merge($this->_getKeylist(), array($this->_cachekey))) as $val) {
            switch ($this->_cachetype) {
            case self::APC:
                apc_delete($val);
                break;

            case self::XCACHE:
                xcache_unset($val);
                break;

            case self::EACCELERATOR:
                /* Undocumented, unknown return value. */
                eaccelerator_rm($val);
                break;

            case self::TEMPFILE:
                @unlink($this->_tempdir . '/' . $val);
                break;
            }
        }

        $this->_saveKeylist(array());

        return true;
    }

    /**
     * Returns the keylist.
     *
     * @return array  Keylist.
     */
    protected function _getKeylist()
    {
        switch ($this->_cachetype) {
        case self::APC:
            $keylist = apc_fetch(self::KEYLIST);
            break;

        case self::XCACHE:
            $keylist = xcache_get(self::KEYLIST);
            break;

        case self::EACCELERATOR:
            $keylist = eaccelerator_get(self::KEYLIST);
            break;

        case self::TEMPFILE:
            $tmp = @file_get_contents($this->_tempdir . '/' . self::KEYLIST);
            $keylist = extension_loaded('msgpack')
                ? msgpack_unpack($tmp)
                : @json_decode($tmp, true);
            break;
        }

        return empty($keylist)
            ? array()
            : $keylist;
    }

    /**
     * Saves the keylist.
     *
     * @param array $keylist  Keylist to save.
     */
    protected function _saveKeylist($keylist)
    {
        switch ($this->_cachetype) {
        case self::APC:
            apc_store(self::KEYLIST, $keylist);
            break;

        case self::XCACHE:
            xcache_set(self::KEYLIST, $keylist);
            break;

        case self::EACCELERATOR:
            eaccelerator_put(self::KEYLIST, $keylist);
            break;

        case self::TEMPFILE:
            file_put_contents($this->_tempdir . '/' . self::KEYLIST, extension_loaded('msgpack') ? msgpack_pack($keylist) : json_encode($keylist));
            break;
        }
    }

}

spl_autoload_unregister(array($__autoloader, 'loadClass'));
$__autoloader = new Horde_Autoloader_Cache();
$__autoloader->registerAutoloader();
