<?php
/**
 * Horde_Url test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Horde_Url
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Url_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Url
 * @subpackage UnitTests
 */
class Horde_Url_AllTests extends Horde_Test_AllTests
{
}

if (PHPUnit_MAIN_METHOD == 'Horde_Url_AllTests::main') {
    Horde_Url_AllTests::main('Horde_Url', __FILE__);
}
