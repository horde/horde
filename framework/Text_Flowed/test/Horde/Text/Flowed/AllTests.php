<?php
/**
 * All tests for the horde/Text_Flowed package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Text_Flowed
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Text_Flowed
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Text_Flowed_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Text_Flowed
 * @subpackage UnitTests
 */
class Horde_Text_Flowed_AllTests extends Horde_Test_AllTests
{
}

Horde_Text_Flowed_AllTests::init('Horde_Text_Flowed', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Text_Flowed_AllTests::main') {
    Horde_Text_Flowed_AllTests::main();
}
