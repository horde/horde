<?php
/**
 * Sesha test suite.
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Sesha
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Sesha_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Sesha
 * @subpackage UnitTests
 */
class Sesha_AllTests extends Horde_Test_AllTests
{
}

Sesha_AllTests::init('Sesha', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Sesha_AllTests::main') {
    Sesha_AllTests::main();
}
