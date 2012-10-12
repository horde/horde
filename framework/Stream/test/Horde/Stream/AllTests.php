<?php
/**
 * Horde_Stream test suite
 *
 * @category   Horde
 * @package    Stream
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Stream_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Stream
 * @subpackage UnitTests
 */
class Horde_Stream_AllTests extends Horde_Test_AllTests
{
}

Horde_Stream_AllTests::init('Horde_Stream', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Stream_AllTests::main') {
    Horde_Stream_AllTests::main();
}
