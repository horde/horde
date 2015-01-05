<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader_Cache
 */

require_once 'Horde/Autoloader/Default.php';
require_once 'Horde/Autoloader/Cache/Bootstrap.php';

/**
 * Decorator for Horde_Autoloader that implements caching of class-file-maps.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader_Cache
 */
class Horde_Autoloader_Cache extends Horde_Autoloader_Default
{
    /* Cache types. @todo: Remove (not used) */
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
    protected $_cachekey;

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
     */
    protected $_storage;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $key = isset($_SERVER['SERVER_NAME'])
            ? $_SERVER['SERVER_NAME']
            : '';
        $key .= '|' . __FILE__;

        $this->_cachekey = self::PREFIX . '_' .
            hash(PHP_MINOR_VERSION >= 4 ? 'fnv132' : 'sha1', $key);
        $this->_storage = new Horde_Autoloader_Cache_Bootstrap();

        $data = $this->_storage->get($this->_cachekey);
        if ($data === false) {
            $this->_newkey = true;
        } else {
            $this->_cache = $data;
        }

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Shutdown method.
     *
     * Attempts to save the class map to the cache.
     */
    public function shutdown()
    {
        if (!$this->_changed) {
            return;
        }

        if (!$this->_storage->set($this->_cachekey, $this->_cache)) {
            error_log('Cannot write Autoloader cache to backend.', 4);
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
            $this->_storage->delete($val);
        }

        $this->_cache = array();

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
        $keylist = $this->_storage->get(self::KEYLIST);

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
        $keylist = $this->_storage->set(self::KEYLIST, $keylist);
    }

}

spl_autoload_unregister(array($__autoloader, 'loadClass'));
$__autoloader = new Horde_Autoloader_Cache();
$__autoloader->registerAutoloader();
