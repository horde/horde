<?php
/**
 * Decorator for Horde_Autoloader that implements caching of
 * class-file-maps.
 *
 * PHP 5
 *
 * @category Horde
 * @package  Autoloader_Cache
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader_Cache
 */

/**
 * Decorator for Horde_Autoloader that implements caching of
 * class-file-maps.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader_Cache
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader_Cache
 */
class Horde_Autoloader_Cache implements Horde_Autoloader
{
    /* Cache types. */
    const APC = 1;
    const XCACHE = 2;
    const EACCELERATOR = 3;
    const TEMPFILE = 4;

    /**
     * The autoloader that is being cached by this decorator.
     *
     * @var Horde_Autoloader
     */
    protected $_autoloader;

    /**
     * Map of all classes already looked up.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * The cache type.
     *
     * @var array
     */
    protected $_cachetype;

    /**
     * Cache key name.
     *
     * @var string
     */
    protected $_cachekey = 'horde_autoloader_cache';

    /**
     * Has the cache changed since the last save?
     *
     * @var boolean
     */
    protected $_changed = false;

    /**
     * Cached value of the temporary directory.
     *
     * @var string
     */
    protected $_tempdir;

    /**
     * Constructor.
     *
     * Tries all supported cache backends and tries to retrieved the cached
     * class map.
     *
     * @param Horde_Autoloader $autoloader The autoloader that is being decorated.
     */
    public function __construct($autoloader)
    {
        $this->_autoloader = $autoloader;

        if (isset($_SERVER['SERVER_NAME'])) {
            $this->_cachekey .= '|' . $_SERVER['SERVER_NAME'];
        }
        $this->_cachekey .= '|' . __FILE__;

        if (extension_loaded('apc')) {
            $this->_cache = apc_fetch($this->_cachekey);
            $this->_cachetype = self::APC;
        } elseif (extension_loaded('xcache')) {
            $this->_cache = xcache_get($this->_cachekey);
            $this->_cachetype = self::XCACHE;
        } elseif (extension_loaded('eaccelerator')) {
            $this->_cache = eaccelerator_get($this->_cachekey);
            $this->_cachetype = self::EACCELERATOR;
        } elseif (($this->_tempdir = sys_get_temp_dir()) &&
                  is_readable($this->_tempdir)) {
            $this->_cachekey = hash('md5', $this->_cachekey);
            if (($data = file_get_contents($this->_tempdir . '/' . $this->_cachekey)) !== false) {
                $this->_cache = @json_decode($data, true);
            }
            $this->_cachetype = self::TEMPFILE;
        }
    }

    /**
     * Destructor.
     *
     * Tries all supported cache backends and tries to save the class map to
     * the cache.
     */
    public function __destruct()
    {
        if (!$this->_changed) {
            return;
        }

        switch ($this->_cachetype) {
        case self::APC:
            apc_store($this->_cachekey, $this->_cache);
            break;

        case self::XCACHE:
            xcache_set($this->_cachekey, $this->_cache);
            break;

        case self::EACCELERATOR:
            eaccelerator_put($this->_cachekey, $this->_cache);
            break;

        case self::TEMPFILE:
            file_put_contents($this->_tempdir . '/' . $this->_cachekey, json_encode($this->_cache));
            break;
        }
    }

    /**
     * Search registered mappers in LIFO order.
     *
     * @param string $className  TODO.
     *
     * @return string  TODO
     */
    public function mapToPath($className)
    {
        if (!$this->_cache) {
            $this->_cache = array();
        }
        if (!array_key_exists($className, $this->_cache)) {
            $this->_cache[$className] = $this->_autoloader->mapToPath($className);
            $this->_changed = true;
        }

        return $this->_cache[$className];
    }

    /**
     * Prunes the autoloader cache.
     *
     * @return boolean  True if pruning succeeded.
     */
    public function prune()
    {
        if (extension_loaded('apc')) {
            return apc_delete($this->_cachekey);
        }
        if (extension_loaded('xcache')) {
            return xcache_unset($this->_cachekey);
        }
        if (extension_loaded('eaccelerator')) {
            /* Undocumented, unknown return value. */
            eaccelerator_rm($this->_cachekey);
            return true;
        }
        if ($this->_tempdir) {
            return unlink($this->_tempdir . '/' . $this->_cachekey);
        }
        return false;
    }
}
