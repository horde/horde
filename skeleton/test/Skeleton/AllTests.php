<?php
/**
 * Skeleton test suite.
 *
 * @author     Your Name <you@example.com>
 * @license    http://www.fsf.org/copyleft/gpl.html GPL
 * @category   Horde
 * @package    Skeleton
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Skeleton_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Skeleton
 * @subpackage UnitTests
 */
class Skeleton_AllTests extends Horde_Test_AllTests
{
}

Skeleton_AllTests::init('Skeleton', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Skeleton_AllTests::main') {
    Skeleton_AllTests::main();
}
