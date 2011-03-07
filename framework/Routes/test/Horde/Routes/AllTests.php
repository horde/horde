<?php
/**
 * Horde Routes package
 *
 * This package is heavily inspired by the Python "Routes" library
 * by Ben Bangert (http://routes.groovie.org).  Routes is based
 * largely on ideas from Ruby on Rails (http://www.rubyonrails.org).
 *
 * @author  Maintainable Software, LLC. (http://www.maintainable.com)
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @package Routes
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Routes_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Routes
 * @subpackage UnitTests
 */
class Horde_Routes_AllTests extends Horde_Test_AllTests
{
}

Horde_Routes_AllTests::init('Horde_Routes', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Routes_AllTests::main') {
    Horde_Routes_AllTests::main();
}
