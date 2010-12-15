<?php
/**
 * All tests for the Cli_Modular:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Cli_Modular
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Cli_Modular_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * Combine the tests for this package.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Cli_Modular
 */
class Horde_Cli_Modular_AllTests extends Horde_Test_AllTests
{
}

Horde_Cli_Modular_AllTests::init('Horde_Cli_Modular', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Cli_Modular_AllTests::main') {
    Horde_Cli_Modular_AllTests::main();
}
