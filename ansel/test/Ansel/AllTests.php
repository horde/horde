<?php
/**
 * All tests for the Ansel:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Ansel
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL-2.0
 * @link       http://www.horde.org/apps/mnemo
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Ansel_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Ansel:: package.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @package    Ansel
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL-2.0
 * @link       http://www.horde.org/apps/mnemo
 */
class Ansel_AllTests extends Horde_Test_AllTests
{
}

Ansel_AllTests::init('Ansel', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Ansel_AllTests::main') {
    Ansel_AllTests::main();
}
