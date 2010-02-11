<?php
/**
 * All tests for the Kolab_Format:: package.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_Format_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Kolab_Format
 * @subpackage UnitTests
 */
class Horde_Kolab_Format_AllTests extends Horde_Test_AllTests
{
}

Horde_Kolab_Format_AllTests::init('Horde_Kolab_Format', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_Format_AllTests::main') {
    Horde_Kolab_Format_AllTests::main();
}
