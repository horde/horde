<?php
/**
 * @package    Horde_Autoloader
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Autoloader_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Autoloader
 * @subpackage UnitTests
 */
class Horde_Autoloader_AllTests extends Horde_Test_AllTests
{
}

Horde_Autoloader_AllTests::init('Horde_Autoloader', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Autoloader_AllTests::main') {
    Horde_Autoloader_AllTests::main();
}
