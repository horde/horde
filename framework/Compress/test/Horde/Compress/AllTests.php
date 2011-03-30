<?php
/**
 * Horde_Compress test suite
 *
 * @category   Horde
 * @package    Compress
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Compress_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Compress
 * @subpackage UnitTests
 */
class Horde_Compress_AllTests extends Horde_Test_AllTests
{
}

Horde_Compress_AllTests::init('Horde_Compress', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Compress_AllTests::main') {
    Horde_Compress_AllTests::main();
}
