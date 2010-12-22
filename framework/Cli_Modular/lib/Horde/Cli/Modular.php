<?php
/**
 * Glue class for a modular CLI.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Glue class for a modular CLI.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Horde_Cli_Modular
{
    /**
     * Parameters.
     *
     * @var array
     */
    private $_parameters;

    /**
     * Handler for the list of modules.
     *
     * @var Horde_Cli_Modular_Modules
     */
    private $_modules;

    /**
     * Module provider.
     *
     * @var Horde_Cli_Modular_ModuleProvider
     */
    private $_provider;

    /**
     * Constructor.
     *
     * @param array $parameters Options for this instance.
     * <pre>
     *  - parser
     *    - class:   Class name of the parser that should be used to parse
     *               command line arguments.
     *    - usage:   The usage decription shown in the help output of the CLI
     *  - modules:   Determines the handler for modules. Can be one of:
     *               (array)  A parameter array.
     *                        See Horde_Cli_Modular_Modules::__construct()
     *               (string) A class name.
     *               (object) An instance of Horde_Cli_Modular_Modules
     *  - provider:  Determines the module provider. Can be one of:
     *               (array)  A parameter array.
     *                        See Horde_Cli_Modular_ModuleProvider::__construct()
     *               (string) A class name.
     *               (object) An instance of Horde_Cli_Modular_ModuleProvider
     * </pre>
     */
    public function __construct(array $parameters = null)
    {
        $this->_parameters = $parameters;
    }

    /**
     * Return the class name for the parser that should be used.
     *
     * @return string The class name.
     */
    public function getParserClass()
    {
        if (empty($this->_parameters['parser']['class'])) {
            return 'Horde_Argv_Parser';
        } else {
            return $this->_parameters['parser']['class'];
        }
    }

    /**
     * Return the usage description for the help output of the parser.
     *
     * @return string The usage description.
     */
    public function getUsage()
    {
        if (empty($this->_parameters['parser']['usage'])) {
            $usage = '[options]';
        } else {
            $usage = $this->_parameters['parser']['usage'];
        }
        foreach ($this->getModules() as $module) {
            $usage .= $this->getProvider()->getModule($module)->getUsage();
        }
        return $usage;
    }

    /**
     * Create the parser for command line arguments.
     *
     * @return Horde_Argv_Parser The parser.
     */
    public function createParser()
    {
        $parser_class = $this->getParserClass();
        $parser = new $parser_class(
            array(
                'usage' => '%prog ' . $this->getUsage()
            )
        );
        foreach ($this->getModules() as $module_name) {
            $module = $this->getProvider()->getModule($module_name);
            foreach ($module->getBaseOptions() as $option) {
                $parser->addOption($option);
            }
            if ($module->hasOptionGroup()) {
                $group = new Horde_Argv_OptionGroup(
                    $parser,
                    $module->getOptionGroupTitle(),
                    $module->getOptionGroupDescription()
                );
                foreach ($module->getOptionGroupOptions() as $option) {
                    $group->addOption($option);
                }
                $parser->addOptionGroup($group);
            }
        }
        return $parser;
    }

    /**
     * Return the module handler.
     *
     * @return Horde_Cli_Modular_Modules The module handler.
     */
    public function getModules()
    {
        if ($this->_modules === null) {
            $this->_modules = $this->_createModules();
        }
        return $this->_modules;
    }

    /**
     * Create the module handler.
     *
     * @return Horde_Cli_Modular_Modules The module handler.
     */
    private function _createModules()
    {
        if (is_array($this->_parameters['modules'])) {
            return new Horde_Cli_Modular_Modules(
                $this->_parameters['modules']
            );
        } else if ($this->_parameters['modules'] instanceOf Horde_Cli_Modular_Modules) {
            return $this->_parameters['modules'];
        } else if (is_string($this->_parameters['modules'])) {
            return new $this->_parameters['modules']();
        } else if (empty($this->_parameters['modules'])) {
            throw new Horde_Cli_Modular_Exception(
                'Missing "modules" parameter!'
            );
        } else {
            throw new Horde_Cli_Modular_Exception(
                'Invalid "modules" parameter!'
            );
        }
    }

    /**
     * Return the module provider.
     *
     * @return Horde_Cli_Modular_ModuleProvider The module provider.
     */
    public function getProvider()
    {
        if ($this->_provider === null) {
            $this->_provider = $this->_createProvider();
        }
        return $this->_provider;
    }

    /**
     * Create the module provider.
     *
     * @return Horde_Cli_Modular_ModuleProvider The module provider.
     */
    private function _createProvider()
    {
        if (is_array($this->_parameters['provider'])) {
            return new Horde_Cli_Modular_ModuleProvider(
                $this->_parameters['provider']
            );
        } else if ($this->_parameters['provider'] instanceOf Horde_Cli_Modular_ModuleProvider) {
            return $this->_parameters['provider'];
        } else if (is_string($this->_parameters['provider'])) {
            return new $this->_parameters['provider']();
        } else if (empty($this->_parameters['provider'])) {
            throw new Horde_Cli_Modular_Exception(
                'Missing "provider" parameter!'
            );
        } else {
            throw new Horde_Cli_Modular_Exception(
                'Invalid "provider" parameter!'
            );
        }
    }
}