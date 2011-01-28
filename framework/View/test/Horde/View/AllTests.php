<?php
/**
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_View_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/* Ensure a default timezone is set. */
date_default_timezone_set('America/New_York');

/**
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_AllTests extends Horde_Test_AllTests
{
}

Horde_View_AllTests::init('Horde_View', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_View_AllTests::main') {
    Horde_View_AllTests::main();
}
