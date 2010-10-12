<?php
/**
 * Horde_Translation test suite.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Translation_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Translation
 * @subpackage UnitTests
 */
class Horde_Translation_AllTests extends Horde_Test_AllTests
{
}

Horde_Translation_AllTests::init('Horde_Translation', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Translation_AllTests::main') {
    Horde_Translation_AllTests::main();
}
