<?php
/**
 * All tests for the Horde_Core:: package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Core_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Core
 * @subpackage UnitTests
 */
class Horde_Core_AllTests extends Horde_Test_AllTests
{
}

Horde_Core_AllTests::init('Horde_Core', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Core_AllTests::main') {
    Horde_Core_AllTests::main();
}
