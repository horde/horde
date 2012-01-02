<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Controller
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Controller_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Controller
 * @subpackage UnitTests
 */
class Horde_Controller_AllTests extends Horde_Test_AllTests
{
}

Horde_Controller_AllTests::init('Horde_Controller', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Controller_AllTests::main') {
    Horde_Controller_AllTests::main();
}
