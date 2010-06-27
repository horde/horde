<?php
/**
 * All tests for the Horde_Kolab_Config:: package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_Config_AllTests::main');
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
 * @category   Kolab
 * @package    Kolab_Config
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Config
 */
class Horde_Kolab_Config_AllTests extends Horde_Test_AllTests
{
}

Horde_Kolab_Config_AllTests::init('Horde_Kolab_Config', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_Config_AllTests::main') {
    Horde_Kolab_Config_AllTests::main();
}
