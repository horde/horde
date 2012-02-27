<?php
/**
 * All tests for the Horde_Text_Filter_Csstidy:: package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Text_Filter_Csstidy
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=Text_Filter_Csstidy
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Text_Filter_Csstidy_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Text_Filter_Csstidy
 * @subpackage UnitTests
 */
class Horde_Text_Filter_Csstidy_AllTests extends Horde_Test_AllTests
{
}

Horde_Text_Filter_Csstidy_AllTests::init('Horde_Text_Filter_Csstidy', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Text_Filter_Csstidy_AllTests::main') {
    Horde_Text_Filter_Csstidy_AllTests::main();
}
