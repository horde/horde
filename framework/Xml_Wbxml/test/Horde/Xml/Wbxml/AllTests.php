<?php
/**
 * Horde_Xml_Wbxml test suite.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Xml_Wbxml
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Xml_Wbxml_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Xml_Wbxml
 * @subpackage UnitTests
 */
class Horde_Xml_Wbxml_AllTests extends Horde_Test_AllTests
{
}

Horde_Xml_Wbxml_AllTests::init('Horde_Xml_Wbxml', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Xml_Wbxml_AllTests::main') {
    Horde_Xml_Wbxml_AllTests::main();
}
