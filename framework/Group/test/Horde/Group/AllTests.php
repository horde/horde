<?php
/**
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2011 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Group_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Group
 * @subpackage UnitTests
 */
class Horde_Group_AllTests extends Horde_Test_AllTests
{
}

Horde_Group_AllTests::init('Horde_Group', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Group_AllTests::main') {
    Horde_Group_AllTests::main();
}