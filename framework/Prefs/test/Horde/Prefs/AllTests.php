<?php
/**
 * All tests for the Horde_Prefs:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Prefs
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Prefs_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * Combine the tests for this package.
 *
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Prefs
 */
class Horde_Prefs_AllTests extends Horde_Test_AllTests
{
}

Horde_Prefs_AllTests::init('Horde_Prefs', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Prefs_AllTests::main') {
    Horde_Prefs_AllTests::main();
}
