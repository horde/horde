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
    protected $_cachekey = 'horde_autoloader_cache';

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
        } elseif (($this->_tempdir = sys_get_temp_dir()) &&
                  is_readable($this->_tempdir)) {
            $this->_cachekey = hash('md5', $this->_cachekey);
            $data = file_get_contents($this->_tempdir . '/' . $this->_cachekey);
            $this->_cachetype = self::TEMPFILE;
        }

        if ($data) {
            if (extension_loaded('horde_lz4')) {
                $data = @horde_lz4_uncompress($data);
            } elseif (extension_loaded('lzf')) {
                $data = @lzf_decompress($data);
            }

            if ($data !== false) {
                $this->_cache = @json_decode($data, true);
            }
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

        $data = json_encode($this->_cache);
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
            file_put_contents($this->_tempdir . '/' . $this->_cachekey, $data);
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
            $this->_cache[$className] = parent::mapToPath($className);
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

spl_autoload_unregister(array($__autoloader, 'loadClass'));
$__autoloader = new Horde_Autoloader_Cache();
$__autoloader->registerAutoloader();
