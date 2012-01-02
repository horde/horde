<?php
/**
 * All tests for the Horde_PubSub:: package.
 *
 * @category Horde
 * @package  PubSub
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=PubSub
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_PubSub_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    PubSub
 * @subpackage UnitTests
 */
class Horde_PubSub_AllTests extends Horde_Test_AllTests
{
}

Horde_PubSub_AllTests::init('Horde_PubSub', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_PubSub_AllTests::main') {
    Horde_PubSub_AllTests::main();
}
