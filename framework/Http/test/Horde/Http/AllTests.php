<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Http_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Http
 * @subpackage UnitTests
 */
class Horde_Http_AllTests extends Horde_Test_AllTests
{
}

Horde_Http_AllTests::init('Horde_Http', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Http_AllTests::main') {
    Horde_Http_AllTests::main();
}
