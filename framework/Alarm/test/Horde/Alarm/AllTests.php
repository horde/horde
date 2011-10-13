<?php
/**
 * Horde_Alarm test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Alarm_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Alarm
 * @subpackage UnitTests
 */
class Horde_Alarm_AllTests extends Horde_Test_AllTests
{
}

Horde_Alarm_AllTests::init('Horde_Alarm', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Alarm_AllTests::main') {
    Horde_Alarm_AllTests::main();
}
