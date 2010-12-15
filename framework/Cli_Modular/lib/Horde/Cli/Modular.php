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
}