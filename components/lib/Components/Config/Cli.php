<?php
/**
 * Components_Config_Cli:: class provides the command line interface for the Horde
 * element tool.
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
 * Components_Config_Cli:: class provides the command line interface for the Horde
 * element tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Components_Config_Cli
implements Components_Config
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

        $parser->addOption(
            new Horde_Argv_Option(
                '-q',
                '--quiet',
                array(
                    'action' => 'store_true',
                    'help'   => 'Reduce output to a minimum'
                )
            )
        );
        $parser->addOption(
            new Horde_Argv_Option(
                '-v',
                '--verbose',
                array(
                    'action' => 'store_true',
                    'help'   => 'Reduce output to a maximum'
                )
            )
        );
    }

    /**
     * Load the options for the list of supported modules.
     *
     * @param Components_Modules $modules A list of modules.
     * @return NULL
     */
    public function handleModules(Components_Modules $modules)
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
     * @param Components_Module   $module The module providing the option group.
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
