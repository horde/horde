<?php
/**
 * All tests for the Horde_Kolab_Config:: package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_Config_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * Combine the tests for this package.
 *
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */
class Horde_Kolab_Config_AllTests extends Horde_Test_AllTests
{
    /**
     * Main entry point for running the suite.
     */
    public static function main($package = null, $file = null)
    {
        if ($package) {
            self::$_package = $package;
        }
        if ($file) {
            self::$_file = $file;
        }

        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Collect the unit tests of this directory into a new suite.
     *
     * @return PHPUnit_Framework_TestSuite The test suite.
     */
    public static function suite()
    {
        return self::detectTestFixture(Horde_Test_AllTests::suite());
    }

    /**
     * Detect if test configuration is available for the server integration
     * tests.
     *
     * @param PHPUnit_Framework_TestSuite $suite The current test suite.
     */
    public static function detectTestFixture(PHPUnit_Framework_TestSuite $suite)
    {
        $config = getenv('KOLAB_CONFIG_TEST_CONFIG');
        if ($config === false) {
            $config = dirname(__FILE__) . '/conf.php';
        }
        if (file_exists($config)) {
            require $config;
            if (isset($conf['kolab']['config']['test'])) {
                $fixture = new stdClass;
                $fixture->conf = $conf['kolab']['config']['test'];
                $suite->setSharedFixture($fixture);
            }
        }
        return $suite;
    }
}

Horde_Kolab_Config_AllTests::init('Horde_Kolab_Config', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_Config_AllTests::main') {
    Horde_Kolab_Config_AllTests::main();
}
