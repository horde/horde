<?php
/**
 * @package    Horde_Constraint
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Horde_Constraint_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Horde_Constraint
 * @subpackage UnitTests
 */
class Horde_Horde_Constraint_AllTests extends Horde_Test_AllTests
{
}

if (PHPUnit_MAIN_METHOD == 'Horde_Horde_Constraint_AllTests::main') {
    Horde_Horde_Constraint_AllTests::main('Horde_Horde_Constraint', __FILE__);
}
