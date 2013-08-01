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
    /**
     * The autoloader that is being cached by this decorator.
     *
     * @var Horde_Autoloader
     */
    protected $_autoloader;

    /**
     * Cache key name.
     *
     * @var string
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

        if ($data) {
            if (extension_loaded('horde_lz4')) {
                $data = @horde_lz4_uncompress($data);
            } elseif (extension_loaded('lzf')) {
                $data = @lzf_decompress($data);
            }

            if ($data !== false) {
                $data = @json_decode($data, true);
                if (is_array($data)) {
                    $this->_cache = $data;
                } else {
                    $this->_cache = array();
                    $this->_changed = true;
                }
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
     * @param boolean $prepend If true, the autoloader will be prepended on the
     *                         autoload stack instead of appending it.
     *
     * @return NULL
     */
    public function registerAutoloader($prepend = false)
    {
        // Register the autoloader in a way to play well with as many
        // configurations as possible.
        spl_autoload_register(array($this, 'loadClass'), true, false);
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
        if (!array_key_exists($className, $this->_cache)) {
            $this->_cache[$className] = $this->_autoloader->mapToPath($className);
            $this->_changed = true;
        }

        return $this->_cache[$className];
    }

    /**
     * Call a method of the decorated autoloader.
     *
     * @param string $name The method name.
     * @param array  $arguments The method arguments.
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(
            array($this->_autoloader, $name), $arguments
        );
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
