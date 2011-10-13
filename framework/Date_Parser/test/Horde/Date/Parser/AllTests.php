<?php
/**
 * @category   Horde
 * @package    Date_Parser
 * @subpackage UnitTests
 * @copyright  2008-2009 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Date_Parser_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Date_Parser
 * @subpackage UnitTests
 */
class Horde_Date_Parser_AllTests extends Horde_Test_AllTests
{
}

Horde_Date_Parser_AllTests::init('Horde_Date_Parser', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Date_Parser_AllTests::main') {
    Horde_Date_Parser_AllTests::main();
}
