<?php
/**
 * Horde_Pdf test suite
 *
 * @license    http://opensource.org/licenses/lgpl-license.php
 * @category   Horde
 * @package    Horde_Pdf
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Pdf_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Pdf
 * @subpackage UnitTests
 */
class Horde_Pdf_AllTests extends Horde_Test_AllTests
{
}

Horde_Pdf_AllTests::init('Horde_Pdf', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Pdf_AllTests::main') {
    Horde_Pdf_AllTests::main();
}
