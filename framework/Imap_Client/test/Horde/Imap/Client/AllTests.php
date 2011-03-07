<?php
/**
 * All tests for the horde/Imap_Client package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Imap_Client
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Imap_Client_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * Combine the tests for this package.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Imap_Client
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imap_Client
 */
class Horde_Imap_Client_AllTests extends Horde_Test_AllTests
{
}

Horde_Imap_Client_AllTests::init('Horde_Imap_Client', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Imap_Client_AllTests::main') {
    Horde_Imap_Client_AllTests::main();
}
