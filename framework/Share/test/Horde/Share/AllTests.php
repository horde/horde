<?php
/**
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Share_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Share
 * @subpackage UnitTests
 */
class Horde_Share_AllTests extends Horde_Test_AllTests
{
}

Horde_Share_AllTests::init('Horde_Share', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Share_AllTests::main') {
    Horde_Share_AllTests::main();
}