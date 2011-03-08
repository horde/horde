<?php
/**
 * @package    Injector
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Injector_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Injector
 * @subpackage UnitTests
 */
class Horde_Injector_AllTests extends Horde_Test_AllTests
{
}

Horde_Injector_AllTests::init('Horde_Injector', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Injector_AllTests::main') {
    Horde_Injector_AllTests::main();
}
