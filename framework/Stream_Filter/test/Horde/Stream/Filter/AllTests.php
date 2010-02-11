<?php
/**
 * Horde_Stream_Filter test suite
 *
 * @category   Horde
 * @package    Horde_Stream_Filter
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Stream_Filter_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Stream_Filter
 * @subpackage UnitTests
 */
class Horde_Stream_Filter_AllTests extends Horde_Test_AllTests
{
}

Horde_Stream_Filter_AllTests::init('Horde_Stream_Filter', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Stream_Filter_AllTests::main') {
    Horde_Stream_Filter_AllTests::main();
}
