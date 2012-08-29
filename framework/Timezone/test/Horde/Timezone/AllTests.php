<?php
/**
 * Horde_Timezone test suite.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Timezone
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Timezone_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Timezone
 * @subpackage UnitTests
 */
class Horde_Timezone_AllTests extends Horde_Test_AllTests
{
}

Horde_Timezone_AllTests::init('Horde_Timezone', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Timezone_AllTests::main') {
    Horde_Timezone_AllTests::main();
}
