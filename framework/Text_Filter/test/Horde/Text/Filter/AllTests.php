<?php
/**
 * All tests for the Horde_Text_Filter:: package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Text_Filter
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Text_Filter
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Text_Filter_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Text_Filter
 * @subpackage UnitTests
 */
class Horde_Text_Filter_AllTests extends Horde_Test_AllTests
{
}

Horde_Text_Filter_AllTests::init('Horde_Text_Filter', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Text_Filter_AllTests::main') {
    Horde_Text_Filter_AllTests::main();
}
