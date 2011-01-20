<?php
/**
 * Tests for the horde/Crypt package.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Crypt
 * @package    Crypt
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Crypt_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @category   Horde
 * @package    Crypt
 * @subpackage UnitTests
 */
class Horde_Crypt_AllTests extends Horde_Test_AllTests
{
}

Horde_Crypt_AllTests::init('Horde_Crypt', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Crypt_AllTests::main') {
    Horde_Crypt_AllTests::main();
}
