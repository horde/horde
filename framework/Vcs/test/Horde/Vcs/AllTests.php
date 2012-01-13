<?php
/**
 * Horde_Vcs test suite.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Vcs
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Vcs_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Vcs
 * @subpackage UnitTests
 */
class Horde_Vcs_AllTests extends Horde_Test_AllTests
{
}

Horde_Vcs_AllTests::init('Horde_Vcs', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Vcs_AllTests::main') {
    Horde_Vcs_AllTests::main();
}
