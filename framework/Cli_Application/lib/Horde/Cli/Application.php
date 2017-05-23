<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Cli_Application
 */

namespace Horde\Cli;

use Horde_Argv_Parser as Parser;
use Horde_Cli as Cli;
use Horde\Cli\Application\Exception;
use Horde\Cli\Application\Translation;

/**
 * This class implements a complete command line application.
 *
 * The embedded Horde_Cli and Horde_Argv_Parser objects are proxied, so you can
 * call any of their methods on the \Horde\Cli\Application object too.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Cli_Application
 *
 * @property-read array $arguments           Additional arguments.
 * @property-read Horde_Cli $cli             CLI helper.
 * @property Horde_Argv_Parser $parser       Argument parser.
 * @property-read Horde_Argv_Values $values  Argument option values.
 */
class Application
{
    /**
     * @var Horde_Cli
     */
    protected $_cli;

    /**
     * @var Horde_Argv_Parser
     */
    protected $_parser;

    /**
     * @var Horde_Argv_Values
     */
    protected $_values;

    /**
     * @var array
     */
    protected $_arguments;

    /**
     * Constructor.
     *
     * @param Horde_Cli $cli     A Horde_Cli instance.
     * @param array $parserArgs  Any additional arguments for
     *                           Horde_Argv_Parser, e.g. 'usage', 'version',
     *                           'description', 'epilog'.
     */
    public function __construct(Cli $cli = null, array $parserArgs = array())
    {
        if ($cli) {
            $this->_cli = $cli;
        } else {
            /* Make sure no one runs from the web. */
            if (!Cli::runningFromCLI()) {
                throw new Exception(
                    Translation::t("Script must be run from the command line")
                );
            }

            /* Load the CLI environment - make sure there's no time limit, init
             * some variables, etc. */
            $this->_cli = Cli::init();
        }

        $this->_parser = new Parser($parserArgs);
    }

    /**
     * Delegates calls to the embedded Horde_Cli and Horde_Argv_Parser objects.
     *
     * @param string $method  Method name.
     * @param array $args     Method arguments.
     *
     * @return mixed  Result of method call.
     */
    public function __call($method, $args)
    {
        if (method_exists($this->_cli, $method)) {
            return call_user_func_array(array($this->_cli, $method), $args);
        }
        if (method_exists($this->_parser, $method)) {
            return call_user_func_array(array($this->_parser, $method), $args);
        }
        throw new BadMethodCallException('Undefined method ' . $method);
    }

    /**
     * Getter.
     *
     * @param string $property  Property to return.
     */
    public function __get($property)
    {
        switch ($property) {
        case 'arguments':
        case 'cli':
        case 'parser':
        case 'values':
            return $this->{'_' . $property};
        }
    }

    /**
     * Setter.
     *
     * @param string $property  Property to set.
     * @param mixed $value      Value to set.
     */
    public function __set($property, $value)
    {
        switch ($property) {
        case 'parser':
            if (!($value instanceof Horde_Argv_Parser)) {
                throw new InvalidArgumentException();
            }
            $this->_parser = $value;
        }
    }

    /**
     * Runs the application.
     */
    public function run()
    {
        list($this->_values, $this->_arguments) = $this->_parser->parseArgs();
        $this->_doRun();
    }

    /**
     * Excecutes the actual application logic.
     */
    protected function _doRun()
    {
    }
}
