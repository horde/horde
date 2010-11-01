<?php
/**
 * Horde_Mime test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Horde_Mime
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Mime_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Mime
 * @subpackage UnitTests
 */
class Horde_Mime_AllTests extends Horde_Test_AllTests
{
}

Horde_Mime_AllTests::init('Horde_Mime', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Mime_AllTests::main') {
    Horde_Mime_AllTests::main();
}
