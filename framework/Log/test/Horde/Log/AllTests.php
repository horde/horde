<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Log_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @category   Horde
 * @package    Log
 * @subpackage UnitTests
 */
class Horde_Log_AllTests extends Horde_Test_AllTests
{
}

Horde_Log_AllTests::init('Horde_Log', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Log_AllTests::main') {
    Horde_Log_AllTests::main();
}
