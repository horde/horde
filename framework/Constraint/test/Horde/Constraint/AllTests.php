<?php
/**
 * @package    Constraint
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Constraint_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';
set_include_path(__DIR__ . '/../../' . PATH_SEPARATOR . get_include_path());

/**
 * @package    Constraint
 * @subpackage UnitTests
 */
class Horde_Constraint_AllTests extends Horde_Test_AllTests
{
}

Horde_Constraint_AllTests::init('Horde_Constraint', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Constraint_AllTests::main') {
    Horde_Constraint_AllTests::main();
}
