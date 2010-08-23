<?php
/**
 * The Horde_Element_Modules:: class handles a set of Element modules.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */

/**
 * The Horde_Element_Modules:: class handles a set of Element modules.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */
class Horde_Element_Modules
implements Iterator, Countable
{
    /**
     * The available modules.
     *
     * @var array
     */
    private $_modules;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_modules = array();
    }

    /**
     * Add all modules found in the specified directory.
     *
     * @param string $module_directory Load the modules from this dirrectory.
     *
     * @return NULL
     */
    public function addModulesFromDirectory(
        $module_directory,
        $base = 'Horde_Element_Module_'
    ) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module_directory)) as $file) {
            if ($file->isFile() && preg_match('/.php$/', $file->getFilename())) {
                $class = $base . preg_replace("/^(.*)\.php/", '\\1', $file->getFilename());
                $this->_modules[$class] = new $class();
            }
        }
    }

    /**
     * Implementation of the Iterator rewind() method. Rewinds the module list.
     *
     * return NULL
     */
    public function rewind()
    {
        reset($this->_modules);
    }

    /**
     * Implementation of the Iterator current(). Returns the current module.
     *
     * @return mixed The current module.
     */
    public function current()
    {
        return current($this->_modules);
    }

    /**
     * Implementation of the Iterator key() method. Returns the key of the current module.
     *
     * @return mixed The class name of the current module.
     */
    public function key()
    {
        return key($this->_modules);
    }

    /**
     * Implementation of the Iterator next() method. Returns the next module.
     *
     * @return Horde_Element_Module|null The next module or null if there are no more
     * modules.
     */
    public function next()
    {
        return next($this->_modules);
    }

    /**
     * Implementation of the Iterator valid() method. Indicates if the current element is a valid element.
     *
     * @return boolean Whether the current element is valid
     */
    public function valid()
    {
        return key($this->_modules) !== null;
    }

    /**
     * Implementation of Countable count() method. Returns the number of modules.
     *
     * @return integer Number of modules.
     */
    public function count()
    {
        return count($this->_modules);
    }
}