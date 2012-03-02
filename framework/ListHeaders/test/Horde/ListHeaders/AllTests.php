<?php
/**
 * Horde_ListHeaders test suite
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    ListHeaders
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_ListHeaders_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    ListHeaders
 * @subpackage UnitTests
 */
class Horde_ListHeaders_AllTests extends Horde_Test_AllTests
{
}

Horde_ListHeaders_AllTests::init('Horde_ListHeaders', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_ListHeaders_AllTests::main') {
    Horde_ListHeaders_AllTests::main();
}
