<?php
/**
 * All tests for the Horde_Auth package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @link       http://pear.horde.org/index.php?package=Auth
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Auth_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * Combine the tests for this package.
 */
class Horde_Auth_AllTests extends Horde_Test_AllTests
{
}

Horde_Auth_AllTests::init('Horde_Auth', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Auth_AllTests::main') {
    Horde_Auth_AllTests::main();
}
