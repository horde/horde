<?php
/**
 * Horde_Argv test suite
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

if (!function_exists('_')) {
    function _($t) {
        return $t;
    }
}

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Argv_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Argv
 * @subpackage UnitTests
 */
class Horde_Argv_AllTests extends Horde_Test_AllTests
{
}

Horde_Argv_AllTests::init('Horde_Argv', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Argv_AllTests::main') {
    Horde_Argv_AllTests::main('Horde_Argv', __FILE__);
}
