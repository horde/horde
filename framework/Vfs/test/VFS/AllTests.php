<?php
/**
 * All tests for the VFS:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=VFS
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'VFS_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Feed
 * @subpackage UnitTests
 */
class VFS_AllTests extends Horde_Test_AllTests
{
}

VFS_AllTests::init('VFS', __FILE__);

if (PHPUnit_MAIN_METHOD == 'VFS_AllTests::main') {
    VFS_AllTests::main();
}
