<?php
/**
 * Tests for the horde/Serialize package.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Serialize
 * @package    Serialize
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Serialize_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @category   Horde
 * @package    Serialize
 * @subpackage UnitTests
 */
class Horde_Serialize_AllTests extends Horde_Test_AllTests
{
}

Horde_Serialize_AllTests::init('Horde_Serialize', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Serialize_AllTests::main') {
    Horde_Serialize_AllTests::main();
}
