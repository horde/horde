<?php
/**
 * Test the anonymous user.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the anonymous user.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_User_AnonymousTest
extends PHPUnit_Framework_TestCase
{
    public function testGetPrimaryId()
    {
        $this->assertEquals('', $this->_getUser()->getPrimaryId());
    }

    public function testGetDomain()
    {
        $this->assertEquals('', $this->_getUser()->getDomain());
    }

    public function testGetGroups()
    {
        $this->assertEquals(array(), $this->_getUser()->getGroups());
    }

    public function testIsAuthenticated()
    {
        $this->assertFalse($this->_getUser()->isAuthenticated());
    }

    private function _getUser()
    {
        return new Horde_Kolab_FreeBusy_User_Anonymous();
    }
}