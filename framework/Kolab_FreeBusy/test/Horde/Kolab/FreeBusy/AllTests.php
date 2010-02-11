<?php
/**
 * All tests for the Kolab_FreeBusy:: package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_FreeBusy_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Kolab_FreeBusy
 * @subpackage UnitTests
 */
class Horde_Kolab_FreeBusy_AllTests extends Horde_Test_AllTests
{
    public static function suite()
    {
        // Return empty for now
        return new PHPUnit_Framework_TestSuite();
    }
}

Horde_Kolab_FreeBusy_AllTests::init('Horde_Kolab_FreeBusy', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_FreeBusy_AllTests::main') {
    Horde_Kolab_FreeBusy_AllTests::main();
}
