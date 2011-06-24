<?php
/**
 * All tests for the Kronolith:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/apache Apache-like
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Kronolith_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Kronolith:: package.
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/apache Apache-like
 */
class Kronolith_AllTests extends Horde_Test_AllTests
{
}

Kronolith_AllTests::init('Kronolith', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Kronolith_AllTests::main') {
    Kronolith_AllTests::main();
}
