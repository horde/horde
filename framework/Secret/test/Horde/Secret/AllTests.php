<?php
/**
 * All tests for the Horde_Secret package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Secret
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Secret
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Secret_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Secret
 * @subpackage UnitTests
 */
class Horde_Secret_AllTests extends Horde_Test_AllTests
{
}

Horde_Secret_AllTests::init('Horde_Secret', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Secret_AllTests::main') {
    Horde_Secret_AllTests::main();
}
