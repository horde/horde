<?php
/**
 * All tests for the Cli_Modular:: package.
 *
 * PHP version 5
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/components/Horde_Cli_Modular
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
 */
class Horde_Cli_Modular_AllTests extends Horde_Test_AllTests
{
}

Horde_Cli_Modular_AllTests::init('Horde_Cli_Modular', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Cli_Modular_AllTests::main') {
    Horde_Cli_Modular_AllTests::main();
}
