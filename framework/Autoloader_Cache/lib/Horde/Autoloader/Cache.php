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
class Horde_Autoloader_Cache implements Horde_Autoloader
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
     * @param Horde_Autoloader $autoloader The autoloader that is being
     *                                     decorated.
     * @param array            $backends   Class names of backends that may be
     *                                     used.
     */
    public function __construct($autoloader, array $backends = null)
    {
        $this->_autoloader = $autoloader;

        $cachekey = 'horde_autoloader_cache';
        if (isset($_SERVER['SERVER_NAME'])) {
            $cachekey .= '|' . $_SERVER['SERVER_NAME'];
        }
        $cachekey .= '|' . __FILE__;

        if (is_null($backends)) {
            $backends = array(
                'Horde_Autoloader_Cache_Backend_Apc',
                'Horde_Autoloader_Cache_Backend_Xcache',
                'Horde_Autoloader_Cache_Backend_Eaccelerator',
                'Horde_Autoloader_Cache_Backend_Tempfile'
            );
        }

        foreach ($backends as $backend) {
            if (class_exists($backend) &&
                call_user_func(array($backend, 'isSupported'))) {
                $this->_backend = new $backend($cachekey);
                $this->_cache = $this->_backend->fetch();
                break;
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
        if ($this->_changed) {
	        $this->store();
        }
    }

    /**
     * Call a method of the decorated autoloader.
     *
     * @param string $name       The method name.
     * @param array  $arguments  The method arguments.
     *
     * @return mixed  Method results.
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(
            array($this->_autoloader, $name),
            $arguments
        );
    }

    /**
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
     */
    public function loadClass($className)
    {
        return ($path = $this->mapToPath($className))
            ? $this->loadPath($path, $className)
            : false;
    }

    /**
     */
    public function loadPath($path, $className)
    {
        return $this->_autoloader->loadPath($path, $className);
    }

    /**
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
     * Save the class map to the cache.
     */
    public function store()
    {
        if (!is_null($this->_backend)) {
            $this->_backend->store($this->_cache);
        }
    }

    /**
     * Prunes the autoloader cache.
     *
     * @return boolean  True if pruning succeeded.
     */
    public function prune()
    {
        $this->_cache = array();
        return is_null($this->_backend)
            ? true
            : $this->_backend->prune();
    }

}
