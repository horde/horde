<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Icalendar
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Icalendar_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Icalendar
 * @subpackage UnitTests
 */
class Horde_Icalendar_AllTests extends Horde_Test_AllTests
{
}

Horde_Icalendar_AllTests::init('Horde_Icalendar', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Icalendar_AllTests::main') {
    Horde_Icalendar_AllTests::main();
}
