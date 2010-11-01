<?php
/**
 * Horde_Url test suite
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Url
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
 * @package    Url
 * @subpackage UnitTests
 */
class Horde_Url_AllTests extends Horde_Test_AllTests
{
}

Horde_Url_AllTests::init('Horde_Url', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Url_AllTests::main') {
    Horde_Url_AllTests::main();
}
