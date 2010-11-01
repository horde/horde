<?php
/**
 * @package    Horde_Feed
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Feed_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Feed
 * @subpackage UnitTests
 */
class Horde_Feed_AllTests extends Horde_Test_AllTests
{
}

Horde_Feed_AllTests::init('Horde_Feed', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Feed_AllTests::main') {
    Horde_Feed_AllTests::main();
}
