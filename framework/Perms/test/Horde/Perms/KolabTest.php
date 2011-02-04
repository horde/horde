<?php
/**
 * Test the Kolab permission handler.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Perms
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Perms
 */

/**
 * Prepare the test setup.
 */
require_once 'Autoload.php';

/**
 * Test the Kolab permission handler.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Perms
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Perms
 */
class Horde_Perms_KolabTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->storage = $this->getMock('Horde_Perms_Permission_Kolab_Storage');
        $this->storage->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('test'));
        $this->groups = $this->getMock('Horde_Group', array(), array(), '', false, false);
        $this->perms = new Horde_Perms();
    }

    public function testConstruct()
    {
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'l')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertEquals('matrix', $permission->get('type'));
    }

    public function testImapListAclResultsInShowPermission()
    {
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'l')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::SHOW));
    }

    public function testImapReadAclResultsInReadPermission()
    {
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'r')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::READ));
    }

    public function testImapEditAclResultsInEditPermission()
    {
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'i')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::EDIT));
    }

    public function testImapDeleteAclResultsInDeletePermission() 
    {
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'd')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::DELETE));
    }

    public function testImapAnonymousUserMapsToGuestUsers()
    {
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('anonymous' => 'lrid')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertEquals(Horde_Perms::ALL, $permission->getGuestPermissions());
    }

    public function testImapAnyoneUserMapsToDefaultUsers()
    {
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('anyone' => 'lrid')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertEquals(Horde_Perms::ALL, $permission->getDefaultPermissions());
    }

    public function testImapOwnerUserMapsToCreator()
    {
        $this->storage->expects($this->any())
            ->method('getOwner')
            ->will($this->returnValue('test'));
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'lrid')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertEquals(Horde_Perms::ALL, $permission->getCreatorPermissions());
    }

    public function testImapGroupMapsToHordeGroup()
    {
        $this->groups->expects($this->once())
            ->method('getGroupId')
            ->with('test')
            ->will($this->returnValue('horde_test'));
        $this->storage->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('group:test' => 'lrid')));
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $this->assertEquals(array('horde_test' => Horde_Perms::ALL), $permission->getGroupPermissions());
    }

    public function testShowPermissionResultsInImapListAcl()
    {
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('test', 'l');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::SHOW, true);
    }

    public function testReadPermissionResultsInImapReadAcl()
    {
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('test', 'r');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::READ, true);
    }

    public function testEditPermissionResultsInImapEditAcl()
    {
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('test', 'iswc');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::EDIT, true);
    }

    public function testDeletePermissionResultsInImapDeleteAcl() 
    {
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('test', 'd');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::DELETE, true);
    }

    public function testGuestUsersMapsToImapAnonymousUser()
    {
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('anonymous', 'lriswcd');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addGuestPermission(Horde_Perms::ALL, true);
    }

    public function testDefaultUsersMapsToImapAnyoneUser()
    {
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('anyone', 'lriswcd');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addDefaultPermission(Horde_Perms::ALL, true);
    }

    public function testCreatorMapsToImapOwnerUser()
    {
        $this->storage->expects($this->any())
            ->method('getOwner')
            ->will($this->returnValue('test'));
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('test', 'alriswcd');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addCreatorPermission(Horde_Perms::ALL, true);
    }

    public function testHordeGroupMapsToImapGroup()
    {
        $this->groups->expects($this->once())
            ->method('getGroupName')
            ->with('horde_test')
            ->will($this->returnValue('test'));
        $this->storage->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('setAcl')
            ->with('group:test', 'lriswcd');
        $permission = new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
        $permission->addGroupPermission('horde_test', Horde_Perms::ALL, true);
    }

    public function testGetType()
    {
        $this->assertEquals(
            'matrix', $this->_getComplexPermissions()->get('type')
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'Horde_Perms_Permission_Kolab::test',
            $this->_getComplexPermissions()->getName()
        );
    }

    public function testSetName()
    {
        $permission = $this->_getComplexPermissions();
        $permission->setName('DUMMY');
        $this->assertEquals('DUMMY', $permission->getName());
    }

    private function _getComplexPermissions()
    {
        $this->storage->expects($this->any())
            ->method('getAcl')
            ->will(
                $this->returnValue(
                    array(
                        'wrobel' => 'lrid',
                        'reader' => 'lr',
                        'viewer' => 'l',
                        'editor' => 'l', 'r', 'e',
                        'anyone' => 'l',
                        'anonymous' => '',
                        'group:editors' => 'l', 'r', 'e'
                    )
                )
            );
        return new Horde_Perms_Permission_Kolab(
            $this->storage, $this->groups
        );
    }
}
