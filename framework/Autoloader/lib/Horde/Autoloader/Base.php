<?php
/**
 * Base implementation for the Horde_Autoloader
 *
 * PHP 5
 *
 * @category Horde
 * @package  Autoloader
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */

/**
 * Horde_Autoloader manages an application's class name to file name
 * mapping conventions. One or more class-to-filename mappers are
 * defined, and are searched in LIFO order.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader
 */
class Horde_Autoloader_Base implements Horde_Autoloader
{
    /**
     * The class to file mappers registered to the autoloader.
     *
     * @var array
     */
    private $_mappers = array();

    /**
     * Potential callbacks to be called after loading a class. An association of
     * "class name" to "callback function".
     *
     * @var array
     */
    private $_callbacks = array();

    /**
     * Try to load a source file for the provided class name.
     *
     * @param string $className The class to load.
     *
     * @return boolean True if loading the class succeeded.
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
        if ($this->_include($path)) {
            $className = strtolower($className);
            if (isset($this->_callbacks[$className])) {
                call_user_func($this->_callbacks[$className]);
            }
            return true;
        }

        return false;
    }

    /**
     * Add a mapper that converts from a class name to paths.
     *
     * @param Horde_Autoloader_ClassPathMapper $mapper The mapper to be added.
     *
     * @return Horde_Autoloader_Base This instance.
     */
    public function addClassPathMapper(Horde_Autoloader_ClassPathMapper $mapper)
    {
        array_unshift($this->_mappers, $mapper);
        return $this;
    }

    /**
     * Add a callback to run when a class is loaded through loadClass().
     *
     * @param string $class    The classname.
     * @param mixed $callback  The callback to run when the class is loaded.
     */
    public function addCallback($class, $callback)
    {
        $this->_callbacks[strtolower($class)] = $callback;
    }

    /**
     * Register the autoloader in the PHP autoloading system via
     * spl_autoload_register().
     *
     * @param boolean $prepend If true, the autoloader will be prepended on the
     *                         autoload stack instead of appending it.
     */
    public function registerAutoloader($prepend = false)
    {
        // Register the autoloader in a way to play well with as many
        // configurations as possible.
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }
    }

    /**
     * Map a class name to a file path. The registered mappers will be searched
     * in LIFO order.
     *
     * @param string $className The class name that should be mapped to a path.
     *
     * @return string The path name to the source file.
     */
    public function mapToPath($className)
    {
        foreach ($this->_mappers as $mapper) {
            if ($path = $mapper->mapToPath($className)) {
                if ($this->_fileExists($path)) {
                    return $path;
                }
            }
        }
    }

    /**
     * Inlcude the specified source file.
     *
     * @param string $path The path to the source file.
     *
     * @return boolean True if the file was successfully included.
     */
    protected function _include($path)
    {
        return (bool)include $path;
    }

    /**
     * Check if the specified file exists.
     *
     * @param string $path The path to check.
     *
     * @return boolean True if the file exists.
     */
    protected function _fileExists($path)
    {
        return file_exists($path);
    }
}
