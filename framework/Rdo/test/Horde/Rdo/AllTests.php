<?php
/**
 * @package    Horde_Rdo
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Rdo_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Rdo
 * @subpackage UnitTests
 */
class Horde_Rdo_AllTests extends Horde_Test_AllTests
{
}

if (PHPUnit_MAIN_METHOD == 'Horde_Rdo_AllTests::main') {
    Horde_Rdo_AllTests::main('Horde_Rdo', __FILE__);
}
