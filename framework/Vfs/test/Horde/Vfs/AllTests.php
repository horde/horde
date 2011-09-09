<?php
/**
 * All tests for the Horde_Vfs:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=VFS
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Vfs_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Feed
 * @subpackage UnitTests
 */
class Horde_Vfs_AllTests extends Horde_Test_AllTests
{
}

Horde_Vfs_AllTests::init('Horde_Vfs', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Vfs_AllTests::main') {
    Horde_Vfs_AllTests::main();
}
