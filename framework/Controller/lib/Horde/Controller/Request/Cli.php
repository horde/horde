<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Request
 */

/**
 * Represents a command line request invocation.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Request
 */
class Horde_Controller_Request_Cli extends Horde_Controller_Request_Base
{
    /**
     * Command line arguments
     */
    protected $_argv;

    /**
     * Constructor
     *
     * In addition to the $options from Horde_Controller_Request_Base, you can
     * pass a 'path' argument as a string, or a Horde_Argv_Parser object as
     * 'argvParser' to override the path of the cli request.
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $pathArgs = null;
        if (isset($options['path'])) {
            $pathArgs = $options['path'];
        } elseif (isset($options['argvParser'])) {
            $pathArgs = $options['argvParser'];
        }

        $this->setPath($pathArgs);
    }

    public function getUri()
    {
        return $this->getPath();
    }

    public function getMethod()
    {
        return 'CLI';
    }

    public function setArgv($args)
    {
        $this->_argv = $args;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function setPath($args = null)
    {
        if (is_string($args)) {
            $this->_path = $args;
        } else {
            if ($args instanceof Horde_Argv_Parser) {
                $parser = $args;
            } else {
                $parser = new Horde_Argv_Parser(array(
                    'allowUnknownArgs' => true,
                    'addHelpOption' => false,
                ));
            }

            list($this->_argv, $args) = $parser->parseArgs();
            if (!count($args)) {
                throw new Horde_Controller_Exception('unknown command: ' . implode(' ', $args));
            }
            $this->_path = $args[0];
            $this->_path = str_replace('-', '_', $this->_path);
        }
    }

    /**
     * Get all command line parameters.
     * some wacky loops to make sure that nested values in one
     * param list don't overwrite other nested values
     *
     * @return  array
     */
    public function getParameters()
    {
        $allParams = array();
        $paramArrays = array($this->_pathParams, $this->_argv);

        foreach ($paramArrays as $params) {
            foreach ((array)$params as $key => $value) {
                if (!is_array($value) || !isset($allParams[$key])) {
                    $allParams[$key] = $value;
                } else {
                    $allParams[$key] = array_merge($allParams[$key], $value);
                }
            }
        }
        return $allParams;
    }

}
