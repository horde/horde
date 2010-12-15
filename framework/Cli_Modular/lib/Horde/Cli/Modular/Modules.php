<?php
/**
 * The Horde_Cli_Modular_Modules:: class handles a set of CLI modules.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */

/**
 * The Horde_Cli_Modular_Modules:: class handles a set of CLI modules.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */
class Horde_Cli_Modular_Modules
implements Iterator, Countable
{
    /**
     * Parameters.
     *
     * @var array
     */
    private $_parameters;

    /**
     * The available modules.
     *
     * @var array
     */
    private $_modules;

    /**
     * The dependency provider.
     *
     * @var Cli_Modular_Dependencies
     */
    private $_dependencies;

    /**
     * Constructor.
     *
     * @param array $parameters Options for this instance.
     * <pre>
     *  - directory: (string) The path to the directory that holds the modules.
     *  - exclude:   (array) Exclude these modules from the list.
     * </pre>
     */
    public function __construct(array $parameters = null)
    {
        $this->_parameters = $parameters;
        $this->_initModules();
    }

    /**
     * Initialize the list of module class names.
     *
     * @return NULL
     *
     * @throws Horde_Cli_Modular_Exception In case the list of modules could not
     *                                     be established.
     */
    private function _initModules()
    {
        if (empty($this->_parameters['directory'])) {
            throw new Horde_Cli_Modular_Exception(
                'The "directory" parameter is missing!'
            );
        }
        if (!file_exists($this->_parameters['directory'])) {
            throw new Horde_Cli_Modular_Exception(
                sprintf(
                    'The indicated directory %s does not exist!',
                    $this->_parameters['directory']
                )
            );
        }
        if (!isset($this->_parameters['exclude'])) {
            $this->_parameters['exclude'] = array();
        } else if (!is_array($this->_parameters['exclude'])) {
            $this->_parameters['exclude'] = array($this->_parameters['exclude']);
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_parameters['directory'])) as $file) {
            if ($file->isFile() && preg_match('/.php$/', $file->getFilename())) {
                $class = preg_replace("/^(.*)\.php/", '\\1', $file->getFilename());
                if (!in_array($class, $this->_parameters['exclude'])) {
                    $this->_modules[] = $class;
                }
            }
        }
    }

    /**
     * List the available modules.
     *
     * @return array The list of modules.
     */
    public function listModules()
    {
        return $this->_modules;
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
     * @return Cli_Modular_Module|null The next module or null if there are no more
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