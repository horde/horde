<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Xml_Element
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Xml_Element_AllTests::main');
}

require_once 'Horde/Test/AllTests.php';

/**
 * @package    Url
 * @subpackage UnitTests
 */
class Horde_Xml_Element_AllTests extends Horde_Test_AllTests
{
}

Horde_Xml_Element_AllTests::init('Horde_Xml_Element', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Xml_Element_AllTests::main') {
    Horde_Xml_Element_AllTests::main();
}
