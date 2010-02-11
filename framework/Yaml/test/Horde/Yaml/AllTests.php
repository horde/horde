<?php
/**
 * Horde_Yaml test suite
 *
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Yaml
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Yaml_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';
require_once dirname(__FILE__) . '/Helpers.php';

/**
 * @package    Horde_Yaml
 * @subpackage UnitTests
 */
class Horde_Yaml_AllTests extends Horde_Test_AllTests
{
}

Horde_Yaml_AllTests::init('Horde_Yaml', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Yaml_AllTests::main') {
    Horde_Yaml_AllTests::main();
}
