<?php
/**
 * Basic Horde test case helper.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * Basic Horde test case helper.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Test
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Case extends PHPUnit_Framework_TestCase
{
    /**
     * Useful shorthand if you are mocking a class with a private constructor
     */
    public function getMockSkipConstructor($className, array $methods = array(), array $arguments = array(), $mockClassName = '')
    {
        return $this->getMock($className, $methods, $arguments, $mockClassName, /* $callOriginalConstructor */ false);
    }

    /**
     * Helper method for loading test configuration from a file.
     *
     * The configuration can be specified by an environment variable. If the
     * variable content is a file name, the configuration is loaded from the
     * file. Otherwise it's assumed to be a json encoded configuration hash. If
     * the environment variable is not set, the method tries to load a conf.php
     * file from the same directory as the test case.
     *
     * @param string $env     An environment variable name.
     * @param string $path    The path to use.
     * @param array $default  Some default values that are merged into the
     *                        configuration if specified as a json hash.
     *
     * @return mixed  The value of the configuration file's $conf variable, or
     *                null.
     */
    static public function getConfig($env, $path = null, $default = array())
    {
        $config = getenv($env);
        if ($config) {
            $json = json_decode($config, true);
            if ($json) {
                return array_replace_recursive($default, $json);
            }
        } else {
            if (!$path) {
                $backtrace = new Horde_Support_Backtrace();
                $caller = $backtrace->getCurrentContext();
                $path = dirname($caller['file']);
            }
            $config = $path . '/conf.php';
        }

        if (file_exists($config)) {
            require $config;
            return $conf;
        }

        return null;
    }
}
