<?php
/**
 * Passwd test suite.
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Passwd
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Passwd_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Passwd
 * @subpackage UnitTests
 */
class Passwd_AllTests extends Horde_Test_AllTests
{
}

Passwd_AllTests::init('Passwd', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Passwd_AllTests::main') {
    Passwd_AllTests::main();
}
