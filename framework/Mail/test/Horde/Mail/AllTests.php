<?php
/**
 * Horde_Mail test suite
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Mail
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Mail_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Mail
 * @subpackage UnitTests
 */
class Horde_Mail_AllTests extends Horde_Test_AllTests
{
}

Horde_Mail_AllTests::init('Horde_Mail', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Mail_AllTests::main') {
    Horde_Mail_AllTests::main();
}
