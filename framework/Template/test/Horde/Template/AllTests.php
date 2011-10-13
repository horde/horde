<?php
/**
 * Tests for the horde/Template package.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Template
 * @package    Template
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Template_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @category   Horde
 * @package    Template
 * @subpackage UnitTests
 */
class Horde_Template_AllTests extends Horde_Test_AllTests
{
}

Horde_Template_AllTests::init('Horde_Template', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Template_AllTests::main') {
    Horde_Template_AllTests::main();
}
