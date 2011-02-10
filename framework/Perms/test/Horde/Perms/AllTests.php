<?php
/**
 * All tests for the Perms:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Perms
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Perms
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Perms_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @category   Horde
 * @package    Perms
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Perms
 */
class Horde_Perms_AllTests extends Horde_Test_AllTests
{
}

Horde_Perms_AllTests::init('Horde_Perms', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Perms_AllTests::main') {
    Horde_Perms_AllTests::main();
}
