<?php
/**
 * Test the Horde_Auth_Passwd:: class.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Horde_Auth_Passwd:: class.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */
class Horde_Auth_Unit_PasswdTest extends Horde_Auth_TestCase
{
    public function setUp()
    {
        $this->driver = new Horde_Auth_Passwd(
            array('filename' => dirname(__FILE__) . '/../fixtures/test.passwd')
        );
    }

    public function testAuthenticate()
    {
        $this->assertTrue($this->driver->authenticate('user', array('password' => 'password')));
    }

    public function testListUsers()
    {
        $this->assertEquals(array('user'), $this->driver->listUsers());
    }
}