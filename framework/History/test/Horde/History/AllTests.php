<?php
/**
 * All tests for the Horde_History:: package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  History
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_History_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_History
 * @subpackage UnitTests
 */
class Horde_History_AllTests extends Horde_Test_AllTests
{
}

Horde_History_AllTests::init('Horde_History', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_History_AllTests::main') {
    Horde_History_AllTests::main();
}
