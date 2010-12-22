<?php
/**
 * All tests for the Kolab_Cli:: package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_Cli_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Kolab_Cli
 * @subpackage UnitTests
 */
class Horde_Kolab_Cli_AllTests extends Horde_Test_AllTests
{
}

Horde_Kolab_Cli_AllTests::init('Horde_Kolab_Cli', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_Cli_AllTests::main') {
    Horde_Kolab_Cli_AllTests::main();
}
