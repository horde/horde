<?php
/**
 * Horde_Support test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Support_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Support
 * @subpackage UnitTests
 */
class Horde_Support_AllTests extends Horde_Test_AllTests
{
}

Horde_Support_AllTests::init('Horde_Support', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Support_AllTests::main') {
    Horde_Support_AllTests::main();
}
