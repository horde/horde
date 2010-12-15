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
     * Constructor.
     *
     * @param array $parameters Options for this instance.
     * <pre>
     *  - cli
     *    - parser
     *      - class: Class name of the parser that should be used to parse
     *               command line arguments.
     *  - modules:   Determines the handler for modules. Can be one of:
     *               (array)  A parameter array.
     *                        See Horde_Cli_Modular_Modules::__construct()
     *               (string) A class name.
     *               (object) An instance of Horde_Cli_Modular_Modules
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
        if (empty($this->_parameters['cli']['parser']['class'])) {
            return 'Horde_Argv_Parser';
        } else {
            return $this->_parameters['cli']['parser']['class'];
        }
    }

    /**
     * Create the parser for command line arguments.
     *
     * @return Horde_Argv_Parser The parser.
     */
    public function createParser()
    {
        $parser_class = $this->getParserClass();
        return new $parser_class(
            array(
                'usage' => '%prog ' . _("[options] PACKAGE_PATH")
            )
        );
    }

    /**
     * Create the module handler.
     *
     * @return Horde_Cli_Modular_Modules The module handler.
     */
    public function createModules()
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
}