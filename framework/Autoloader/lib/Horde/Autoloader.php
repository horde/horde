<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Autoloader
 */

/**
 * Horde autoloader implementation.
 *
 * Manages an application's class name to file name mapping conventions. One or
 * more class-to-filename mappers are defined, and are searched in LIFO order.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */
class Horde_Autoloader
{
    /**
     * List of callback methods.
     *
     * @var array
     */
    private $_callbacks = array();

    /**
     * List of classpath mappers.
     *
     * @var array
     */
    private $_mappers = array();

    /**
     * Register the autoloader with PHP (in a way to play well with as many
     * configurations as possible).
     */
    public function registerAutoloader()
    {
        spl_autoload_register(array($this, 'loadClass'));
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }
    }

    /**
     * Loads a class into the current environment by classname.
     *
     * @param string $className  Classname to load.
     *
     * @return boolean  True if the class was successfully loaded.
     */
    public function loadClass($className)
    {
        if (($path = $this->mapToPath($className)) &&
            $this->_include($path)) {
            $className = $this->_lower($className);
            if (isset($this->_callbacks[$className])) {
                call_user_func($this->_callbacks[$className]);
            }
            return true;
        }

        return false;
    }

    /**
     * Adds a class path mapper to the beginning of the queue.
     *
     * @param Horde_Autoloader_ClassPathMapper $mapper  A mapper object.
     *
     * @return Horde_Autoloader  This instance.
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
        $this->_callbacks[$this->_lower($class)] = $callback;
    }

    /**
     * Search registered mappers in LIFO order.
     *
     * @param string $className  Classname to load.
     *
     * @return mixed  Pathname to class, or null if not found.
     */
    public function mapToPath($className)
    {
        foreach ($this->_mappers as $mapper) {
            if (($path = $mapper->mapToPath($className)) &&
                $this->_fileExists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Include a file.
     *
     * @param string $path  Pathname of file to include.
     *
     * @return boolean  Success.
     */
    protected function _include($path)
    {
        return (bool)include $path;
    }

    /**
     * Does a file exist?
     *
     * @param string $path  Pathname of file to check.
     *
     * @return boolean  Does file exist?
     */
    protected function _fileExists($path)
    {
        return file_exists($path);
    }

    /**
     * Locale independant strtolower() implementation.
     *
     * @param string $string The string to convert to lowercase.
     *
     * @return string  The lowercased string, based on ASCII encoding.
     */
    protected function _lower($string)
    {
        $language = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'C');
        $string = strtolower($string);
        setlocale(LC_CTYPE, $language);
        return $string;
    }

}
