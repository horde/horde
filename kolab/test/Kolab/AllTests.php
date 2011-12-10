<?php
/**
 * Kolab test suite.
 *
 * @author     Your Name <you@example.com>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Kolab
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Kolab_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Kolab
 * @subpackage UnitTests
 */
class Kolab_AllTests extends Horde_Test_AllTests
{
}

Kolab_AllTests::init('Kolab', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Kolab_AllTests::main') {
    Kolab_AllTests::main();
}
