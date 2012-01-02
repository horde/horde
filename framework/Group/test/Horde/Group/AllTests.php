<?php
/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Group_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Group
 * @subpackage UnitTests
 */
class Horde_Group_AllTests extends Horde_Test_AllTests
{
}

Horde_Group_AllTests::init('Horde_Group', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Group_AllTests::main') {
    Horde_Group_AllTests::main();
}