<?php
/**
 * All tests for the Horde_Share:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Share
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Share_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Feed
 * @subpackage UnitTests
 */
class Horde_Share_AllTests extends Horde_Test_AllTests
{
}

Horde_Share_AllTests::init('Horde_Share', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Share_AllTests::main') {
    Horde_Share_AllTests::main();
}