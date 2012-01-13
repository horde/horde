<?php
/**
 * All tests for the Service_Weather:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Service_Weather
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @link       http://pear.horde.org/index.php?package=Service_Weather
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Service_Weather_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Service_Weather:: package.
 *
 * @category   Horde
 * @package    Service_Weather
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @link       http://pear.horde.org/index.php?package=Service_Weather
 */
class Horde_Service_Weather_AllTests extends Horde_Test_AllTests
{
}

Horde_Service_Weather_AllTests::init('Horde_Service_Weather', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Service_Weather_AllTests::main') {
    Horde_Service_Weather_AllTests::main();
}
