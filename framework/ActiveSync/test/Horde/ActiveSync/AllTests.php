<?php
/**
 * All tests for the Horde_ActiveSync:: package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  ActiveSync
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_ActiveSync_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_AllTests extends Horde_Test_AllTests
{
}

Horde_ActiveSync_AllTests::init('Horde_ActiveSync', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_ActiveSync_AllTests::main') {
    Horde_ActiveSync_AllTests::main('Horde_ActiveSync', __FILE__);
}
