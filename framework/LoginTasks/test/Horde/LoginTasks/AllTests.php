<?php
/**
 * All tests for the Horde_LoginTasks package.
 *
 * @category Horde
 * @package  LoginTasks
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=LoginTasks
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_LoginTasks_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_LoginTasks
 * @subpackage UnitTests
 */
class Horde_LoginTasks_AllTests extends Horde_Test_AllTests
{
}

Horde_LoginTasks_AllTests::init('Horde_LoginTasks', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_LoginTasks_AllTests::main') {
    Horde_LoginTasks_AllTests::main();
}
