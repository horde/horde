<?php
/**
 * @category   Horde
 * @package    Horde_Text_Textile
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Text_Textile_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_AllTests extends Horde_Test_AllTests
{
}

if (PHPUnit_MAIN_METHOD == 'Horde_Text_Textile_AllTests::main') {
    Horde_Text_Textile_AllTests::main('Horde_Text_Textile', __FILE__);
}
