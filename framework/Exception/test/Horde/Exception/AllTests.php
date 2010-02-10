<?php
/**
 * All tests for the Horde_Exception:: package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Exception
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Exception
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Exception_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Exception
 * @subpackage UnitTests
 */
class Horde_Exception_AllTests extends Horde_Test_AllTests
{
}

Horde_Exception_AllTests::init('Horde_Exception', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Exception_AllTests::main') {
    Horde_Exception_AllTests::main();
}
