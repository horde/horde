<?php
/**
 * The Horde_Cli_Modular_ModuleProvider:: class provides access to a single
 * module.
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
 * The Horde_Cli_Modular_ModuleProvider:: class provides access to a single
 * module.
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
class Horde_Cli_Modular_ModuleProvider
{
    /**
     * Class prefix.
     *
     * @var string
     */
    private $_prefix;

    /**
     * Constructor argument for CLI modules. Likely to be a Horde_Injector
     * instance.
     *
     * @var mixed
     */
    private $_dependencies;

    /**
     * A cache for initialized module instances.
     *
     * @var array
     */
    private $_instances;

    /**
     * Constructor.
     *
     * @param array $parameters Options for this instance.
     * <pre>
     *  - prefix: The module class name prefix.
     *  - dependencies: Constructor argument for CLI modules.
     * </pre>
     */
    public function __construct(array $parameters = null)
    {
        if (!isset($parameters['prefix'])) {
            throw new Horde_Cli_Modular_Exception(
                'Missing "prefix" parameter!'
            );
        }
        $this->_prefix = $parameters['prefix'];
        if (isset($parameters['dependencies'])) {
            $this->_dependencies = $parameters['dependencies'];
        }
    }

    /**
     * Return the specified module.
     *
     * @param string $module The desired module.
     *
     * @return Horde_Cli_Modular_Module The module instance.
     *
     * @throws Horde_Cli_Modular_Exception In case the specified module does not
     * exist.
     */
    public function getModule($module)
    {
        if (!isset($this->_instances[$module])) {
            $this->_instances[$module] = $this->createModule($module);
        }
        return $this->_instances[$module];
    }

    /**
     * Create the specified module.
     *
     * @param string $module The desired module.
     *
     * @return Horde_Cli_Modular_Module The module instance.
     *
     * @throws Horde_Cli_Modular_Exception In case the specified module does not
     * exist.
     */
    protected function createModule($module)
    {
        $class = $this->_prefix . $module;
        if (!class_exists($class)) {
            throw new Horde_Cli_Modular_Exception(
                sprintf('Invalid module %s!', $class)
            );
        }
        return new $class($this->_dependencies);
    }
}