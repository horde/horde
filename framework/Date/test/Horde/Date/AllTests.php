<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 * @copyright  2008-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Date_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_AllTests extends Horde_Test_AllTests
{
}

Horde_Date_AllTests::init('Horde_Date', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Date_AllTests::main') {
    Horde_Date_AllTests::main();
}
