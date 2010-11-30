<?php
/**
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
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
 * @package    Share
 * @subpackage UnitTests
 */
class Horde_Share_AllTests extends Horde_Test_AllTests
{
}

Horde_Share_AllTests::init('Horde_Share', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Share_AllTests::main') {
    Horde_Share_AllTests::main();
}