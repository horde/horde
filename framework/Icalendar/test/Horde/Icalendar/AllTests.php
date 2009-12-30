<?php
/**
 * @category   Horde
 * @package    Horde_Icalendar
 * @subpackage UnitTests
 * @copyright  2009 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Icalendar_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_AllTests extends Horde_Test_AllTests
{
}

if (PHPUnit_MAIN_METHOD == 'Horde_Icalendar_AllTests::main') {
    Horde_Icalendar_AllTests::main('Horde_Icalendar', __FILE__);
}
