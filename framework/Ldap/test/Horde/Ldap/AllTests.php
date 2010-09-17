<?php
/**
 * Horde_Ldap test suite.
 *
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Ldap_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Ldap
 * @subpackage UnitTests
 */
class Horde_Ldap_AllTests extends Horde_Test_AllTests
{
}

Horde_Ldap_AllTests::init('Horde_Ldap', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Ldap_AllTests::main') {
    Horde_Ldap_AllTests::main();
}
