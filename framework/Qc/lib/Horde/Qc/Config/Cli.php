<?php
/**
 * Horde_Qc_Config_Cli:: class provides the command line interface for the Horde
 * quality control tool.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */

/**
 * Horde_Qc_Config_Cli:: class provides the command line interface for the Horde
 * quality control tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */
class Horde_Qc_Config_Cli
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
    private $_opts;

    /**
     * Any additional arguments parsed from the command line.
     *
     * @var array
     */
    private $_args;

    /**
     * Constructor.
     *
     * @param array            $parameters A list of named configuration parameters.
     * <pre>
     * 'parser' - (array)  Parser configuration parameters.
     *   'class'  - (string) The class name of the parser to use.
     * </pre>
     */
    public function __construct(
        $parameters = array()
    ) {
        if (empty($parameters['parser']['class'])) {
            $parser_class = 'Horde_Argv_Parser';
        } else {
            $parser_class = $parameters['parser']['class'];
        }
        $this->_parser = new $parser_class(
            array(
                'usage' => '%prog ' . _("[options] PACKAGE_PATH")
            )
        );

    }

    /**
     * Load the options for the list of supported modules.
     *
     * @param Horde_Qc_Modules $modules A list of modules.
     * @return NULL
     */
    public function handleModules(Horde_Qc_Modules $modules)
    {
        foreach ($modules as $module) {
            $this->_addOptionsFromModule($this->_parser, $module);
        }

        list($this->_opts, $this->_args) = $this->_parser->parseArgs();
    }

    /**
     * Return the options parsed from the command line.
     *
     * @return array An array of options.
     */
    public function getOptions()
    {
        return $this->_opts;
    }

    /**
     * Add an option group from the provided module to the parser.
     *
     * @param Horde_Argv_Parser $parser The parser.
     * @param Horde_Qc_Module   $module The module providing the option group.
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
