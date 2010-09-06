<?php
/**
 * Horde_Element_Config_Cli:: class provides the command line interface for the Horde
 * element tool.
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
 * Horde_Element_Config_Cli:: class provides the command line interface for the Horde
 * element tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Element_Config_Cli
implements Horde_Element_Config
{
    /**
     * The command line argument parser.
     *
     * @var Horde_Argv_Parser
     */
    private $_parser;

    /**
     * The options parsed from the command line.
     *
     * @var array
     */
    private $_options;

    /**
     * Any additional arguments parsed from the command line.
     *
     * @var array
     */
    private $_arguments;

    /**
     * Constructor.
     *
     */
    public function __construct(
        Horde_Argv_Parser $parser
    ) {
        $this->_parser = $parser;
    }

    /**
     * Load the options for the list of supported modules.
     *
     * @param Horde_Element_Modules $modules A list of modules.
     * @return NULL
     */
    public function handleModules(Horde_Element_Modules $modules)
    {
        foreach ($modules as $module) {
            $this->_addOptionsFromModule($this->_parser, $module);
        }

        list($this->_options, $this->_arguments) = $this->_parser->parseArgs();
    }

    /**
     * Return the options parsed from the command line.
     *
     * @return Horde_Argv_Values The option values.
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Return the arguments parsed from the command line.
     *
     * @return array An array of arguments.
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Add an option group from the provided module to the parser.
     *
     * @param Horde_Argv_Parser $parser The parser.
     * @param Horde_Element_Module   $module The module providing the option group.
     *
     * @return NULL
     */
    private function _addOptionsFromModule($parser, $module)
    {
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
