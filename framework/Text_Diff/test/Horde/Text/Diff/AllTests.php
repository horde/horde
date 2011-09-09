<?php
/**
 * Horde_Text_Diff test suite.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Text_Diff
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Text_Diff_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Text_Diff
 * @subpackage UnitTests
 */
class Horde_Text_Diff_AllTests extends Horde_Test_AllTests
{
}

Horde_Text_Diff_AllTests::init('Horde_Text_Diff', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Text_Diff_AllTests::main') {
    Horde_Text_Diff_AllTests::main();
}
