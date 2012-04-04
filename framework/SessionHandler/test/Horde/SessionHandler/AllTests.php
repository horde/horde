<?php
/**
 * Horde_SessionHandler test suite.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Horde_SessionHandler
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_SessionHandler_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_SessionHandler
 * @subpackage UnitTests
 */
class Horde_SessionHandler_AllTests extends Horde_Test_AllTests
{
}

Horde_SessionHandler_AllTests::init('Horde_SessionHandler', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_SessionHandler_AllTests::main') {
    Horde_SessionHandler_AllTests::main();
}
