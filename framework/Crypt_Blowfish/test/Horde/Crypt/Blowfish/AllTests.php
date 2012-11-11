<?php
/**
 * Horde_Crypt_Blowfish test suite.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Crypt_Blowfish
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Crypt_Blowfish_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Crypt_Blowfish
 * @subpackage UnitTests
 */
class Horde_Crypt_Blowfish_AllTests extends Horde_Test_AllTests
{
}

Horde_Crypt_Blowfish_AllTests::init('Horde_Crypt_Blowfish', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Crypt_Blowfish_AllTests::main') {
    Horde_Crypt_Blowfish_AllTests::main();
}
