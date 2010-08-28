<?php
/**
 * All tests for the Itip:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Itip
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Itip_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Itip:: package.
 *
 * @category   Horde
 * @package    Itip
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_AllTests extends Horde_Test_AllTests
{
}

Horde_Itip_AllTests::init('Horde_Itip', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Itip_AllTests::main') {
    Horde_Itip_AllTests::main();
}
