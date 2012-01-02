<?php
/**
 * Horde_Ldap test suite.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
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
 * @package    Ldap
 * @subpackage UnitTests
 */
class Horde_Ldap_AllTests extends Horde_Test_AllTests
{
}

Horde_Ldap_AllTests::init('Horde_Ldap', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Ldap_AllTests::main') {
    Horde_Ldap_AllTests::main();
}
