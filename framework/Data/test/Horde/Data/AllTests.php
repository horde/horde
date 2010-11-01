<?php
/**
 * Horde_Data test suite.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Data
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Data_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Data
 * @subpackage UnitTests
 */
class Horde_Data_AllTests extends Horde_Test_AllTests
{
}

Horde_Data_AllTests::init('Horde_Data', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Data_AllTests::main') {
    Horde_Data_AllTests::main();
}
