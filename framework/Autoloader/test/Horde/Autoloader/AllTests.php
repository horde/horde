<?php
/**
 * All tests for the Autoloader:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Autoloader
 * @subpackage UnitTests
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Autoloader
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Autoloader_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Autoloader:: package.
 *
 * @category   Horde
 * @package    Autoloader
 * @subpackage UnitTests
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Autoloader
 */
class Horde_Autoloader_AllTests extends Horde_Test_AllTests
{
}

Horde_Autoloader_AllTests::init('Horde_Autoloader', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Autoloader_AllTests::main') {
    Horde_Autoloader_AllTests::main();
}
