<?php
/**
 * Horde_Util test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Util_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Util
 * @subpackage UnitTests
 */
class Horde_Util_AllTests extends Horde_Test_AllTests
{
}

Horde_Util_AllTests::init('Horde_Util', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Util_AllTests::main') {
    Horde_Util_AllTests::main();
}
