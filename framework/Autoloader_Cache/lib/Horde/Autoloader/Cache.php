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
     * The caching backend.
     *
     * @var Horde_Autoloader_Cache_Backend
     */
    protected $_backend;

    /**
     * Has the cache changed since the last save?
     *
     * @var boolean
     */
    protected $_changed = false;

    /**
     * Constructor.
     *
     * Tries all supported cache backends and tries to retrieved the cached
     * class map.
     *
     * @param Horde_Autoloader $autoloader The autoloader that is being decorated.
     * @param array            $backends   Class names of backends that may be used.
     */
    public function __construct($autoloader, array $backends = null)
    {
        $this->_autoloader = $autoloader;

        $cachekey = 'horde_autoloader_cache';
        if (isset($_SERVER['SERVER_NAME'])) {
            $cachekey .= '|' . $_SERVER['SERVER_NAME'];
        }
        $cachekey .= '|' . __FILE__;

        if ($backends === null) {
            $backends = array(
                'Horde_Autoloader_Cache_Backend_Apc',
                'Horde_Autoloader_Cache_Backend_Xcache',
                'Horde_Autoloader_Cache_Backend_Eaccelerator',
                'Horde_Autoloader_Cache_Backend_Tempfile'
            );
        }

        foreach ($backends as $backend) {
            if (class_exists($backend)
                && method_exists($backend, 'isSupported')
                && call_user_func(array($backend, 'isSupported'))) {
                $this->_backend = new $backend($cachekey);
                $this->_cache = $this->_backend->fetch();
                break;
            }
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

        $this->store();
    }

    /**
     * Save the class map to the cache.
     */
    public function store()
    {
        if ($this->_backend !== null) {
            $this->_backend->store($this->_cache);
        }
    }

    /**
     * Register this instance as autoloader.
     *
     * @return NULL
     */
    public function registerAutoloader()
    {
        // Register the autoloader in a way to play well with as many
        // configurations as possible.
        spl_autoload_register(array($this, 'loadClass'));
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }
    }

    /**
     * Try to load the definition for the provided class name.
     *
     * @param string $className The name of the undefined class.
     *
     * @return NULL
     */
    public function loadClass($className)
    {
        if ($path = $this->mapToPath($className)) {
            return $this->loadPath($path, $className);
        }
        return false;
    }

    /**
     * Try to load a class from the provided path.
     *
     * @param string $path      The path to the source file.
     * @param string $className The class to load.
     *
     * @return boolean True if loading the class succeeded.
     */
    public function loadPath($path, $className)
    {
        return $this->_autoloader->loadPath($path, $className);
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
        $this->_cache = array();
        if ($this->_backend !== null) {
            return $this->_backend->prune();
        }
    }
}
