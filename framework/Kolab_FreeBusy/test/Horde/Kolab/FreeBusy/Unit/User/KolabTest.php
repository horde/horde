<?php
/**
 * Test the Kolab user.
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
 * Test the Kolab user.
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
class Horde_Kolab_FreeBusy_Unit_User_KolabTest
extends PHPUnit_Framework_TestCase
{
    public function testGetPrimaryId()
    {
        $this->assertEquals(
            'mail@example.org', $this->_getUser()->getPrimaryId()
        );
    }

    public function testGetDomain()
    {
        $this->assertEquals('example.org', $this->_getUser()->getDomain());
    }

    public function testGetGroups()
    {
        $this->assertEquals(
            array('group@example.org'), $this->_getKolabUser()->getGroups()
        );
    }

    public function testIsAuthenticated()
    {
        $this->assertTrue($this->_getAuthUser()->isAuthenticated());
    }

    private function _getUser()
    {
        $composite = $this->_getMockedComposite();
        $db = new Horde_Kolab_FreeBusy_UserDb_Kolab($composite);
        $user = $this->_getDbUser();
        $user->expects($this->any())
            ->method('getSingle')
            ->will($this->returnValue('mail@example.org'));
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        return $db->getUser('test', 'TEST');
    }

    private function _getKolabUser()
    {
        $composite = $this->_getMockedComposite();
        $db = new Horde_Kolab_FreeBusy_UserDb_Kolab($composite);
        $user = $this->_getKolabDbUser();
        $user->expects($this->any())
            ->method('getGroupAddresses')
            ->will($this->returnValue(array('group@example.org')));
        $composite->objects->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($user));
        return $db->getUser('test', 'TEST');
    }

    private function _getAuthUser()
    {
        $composite = $this->_getMockedComposite();
        $db = new Horde_Kolab_FreeBusy_UserDb_Kolab($composite);
        $user = $this->_getKolabDbUser();
        $composite->server->expects($this->once())
            ->method('connectGuid');
        return $db->getUser('test', 'TEST');
    }

    private function _getDbUser()
    {
        return $this->getMock(
            'Horde_Kolab_Server_Object_Hash', array(), array(), '', false, false
        );
    }

    private function _getKolabDbUser()
    {
        return $this->getMock(
            'Horde_Kolab_Server_Object_Kolab_User', array(), array(), '', false, false
        );
    }

    private function _getMockedComposite()
    {
        return new Horde_Kolab_Server_Composite(
            $this->getMock('Horde_Kolab_Server_Interface'),
            $this->getMock('Horde_Kolab_Server_Objects_Interface'),
            $this->getMock('Horde_Kolab_Server_Structure_Interface'),
            $this->getMock('Horde_Kolab_Server_Search_Interface'),
            $this->getMock('Horde_Kolab_Server_Schema_Interface')
        );
    }
}