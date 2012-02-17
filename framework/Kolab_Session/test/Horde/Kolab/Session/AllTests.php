<?php
/**
 * All tests for the Horde_Kolab_Session:: package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Session
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_Session_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * Combine the tests for this package.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Session
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_AllTests extends Horde_Test_AllTests
{
}

Horde_Kolab_Session_AllTests::init('Horde_Kolab_Session', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_Session_AllTests::main') {
    Horde_Kolab_Session_AllTests::main();
}
