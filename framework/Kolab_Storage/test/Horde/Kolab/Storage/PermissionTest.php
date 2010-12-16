<?php
/**
 * Test the Kolab permission handler.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once 'Autoload.php';

/**
 * Test the Kolab permission handler.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_PermissionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->folder = $this->getMock('Horde_Kolab_Storage_Folder_Base', array(), array(), '', false, false);
        $this->groups = $this->getMock('Horde_Group', array(), array(), '', false, false);
        $this->perms = new Horde_Perms();
    }

    public function testConstruct()
    {
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'l')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertEquals('matrix', $permission->get('type'));
    }

    public function testImapListAclResultsInShowPermission()
    {
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'l')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::SHOW));
    }

    public function testImapReadAclResultsInReadPermission()
    {
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'r')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::READ));
    }

    public function testImapEditAclResultsInEditPermission()
    {
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'i')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::EDIT));
    }

    public function testImapDeleteAclResultsInDeletePermission() 
    {
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'd')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertTrue((bool) $this->perms->hasPermission($permission, 'test', Horde_Perms::DELETE));
    }

    public function testImapAnonymousUserMapsToGuestUsers()
    {
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('anonymous' => 'lrid')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertEquals(Horde_Perms::ALL, $permission->getGuestPermissions());
    }

    public function testImapAnyoneUserMapsToDefaultUsers()
    {
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('anyone' => 'lrid')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertEquals(Horde_Perms::ALL, $permission->getDefaultPermissions());
    }

    public function testImapOwnerUserMapsToCreator()
    {
        $storage = $this->getMock('Horde_Kolab_Storage', array(), array(), '', false, false);
        $connection = $this->getMock('Horde_Kolab_Storage_Driver');
        $connection->expects($this->any())
            ->method('getNamespace')
            ->will(
                $this->returnValue(
                    new Horde_Kolab_Storage_Folder_Namespace_Imap(
                        array(
                            array(
                                'type' => Horde_Kolab_Storage_Folder_Namespace::PERSONAL,
                                'name' => 'INBOX/',
                                'delimiter' => '/',
                                'add' => true,
                            )
                        )
                    )
                )
            );
        $connection->expects($this->any())
            ->method('getAuth')
            ->will($this->returnValue('test'));
        $connection->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('test' => 'lrid')));
        $folder = new Horde_Kolab_Storage_Folder_Base($storage, $connection, 'INBOX/test');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $folder, $this->groups
        );
        $this->assertEquals(Horde_Perms::ALL, $permission->getCreatorPermissions());
    }

    public function testImapGroupMapsToHordeGroup()
    {
        $this->groups->expects($this->once())
            ->method('getGroupId')
            ->with('test')
            ->will($this->returnValue('horde_test'));
        $this->folder->expects($this->once())
            ->method('getAcl')
            ->will($this->returnValue(array('group:test' => 'lrid')));
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $this->assertEquals(array('horde_test' => Horde_Perms::ALL), $permission->getGroupPermissions());
    }

    public function testShowPermissionResultsInImapListAcl()
    {
        $this->folder->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->folder->expects($this->once())
            ->method('setAcl')
            ->with('test', 'l');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::SHOW, true);
    }

    public function testReadPermissionResultsInImapReadAcl()
    {
        $this->folder->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->folder->expects($this->once())
            ->method('setAcl')
            ->with('test', 'r');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::READ, true);
    }

    public function testEditPermissionResultsInImapEditAcl()
    {
        $this->folder->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->folder->expects($this->once())
            ->method('setAcl')
            ->with('test', 'iswc');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::EDIT, true);
    }

    public function testDeletePermissionResultsInImapDeleteAcl() 
    {
        $this->folder->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->folder->expects($this->once())
            ->method('setAcl')
            ->with('test', 'd');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $permission->addUserPermission('test', Horde_Perms::DELETE, true);
    }

    public function testGuestUsersMapsToImapAnonymousUser()
    {
        $this->folder->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->folder->expects($this->once())
            ->method('setAcl')
            ->with('anonymous', 'lriswcd');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $permission->addGuestPermission(Horde_Perms::ALL, true);
    }

    public function testDefaultUsersMapsToImapAnyoneUser()
    {
        $this->folder->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->folder->expects($this->once())
            ->method('setAcl')
            ->with('anyone', 'lriswcd');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $permission->addDefaultPermission(Horde_Perms::ALL, true);
    }

    public function testCreatorMapsToImapOwnerUser()
    {
        $storage = $this->getMock('Horde_Kolab_Storage', array(), array(), '', false, false);
        $connection = $this->getMock('Horde_Kolab_Storage_Driver');
        $connection->expects($this->any())
            ->method('getNamespace')
            ->will(
                $this->returnValue(
                    new Horde_Kolab_Storage_Folder_Namespace_Imap(
                        array(
                            array(
                                'type' => Horde_Kolab_Storage_Folder_Namespace::PERSONAL,
                                'name' => 'INBOX/',
                                'delimiter' => '/',
                                'add' => true,
                            )
                        )
                    )
                )
            );
        $connection->expects($this->any())
            ->method('getAuth')
            ->will($this->returnValue('test'));
        $connection->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $connection->expects($this->once())
            ->method('setAcl')
            ->with('INBOX/test', 'test', 'alriswcd');
        $folder = new Horde_Kolab_Storage_Folder_Base($storage, $connection, 'INBOX/test');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $folder, $this->groups
        );
        $permission->addCreatorPermission(Horde_Perms::ALL, true);
    }

    public function testHordeGroupMapsToImapGroup()
    {
        $this->groups->expects($this->once())
            ->method('getGroupName')
            ->with('horde_test')
            ->will($this->returnValue('test'));
        $this->folder->expects($this->exactly(3))
            ->method('getAcl')
            ->will($this->returnValue(array()));
        $this->folder->expects($this->once())
            ->method('setAcl')
            ->with('group:test', 'lriswcd');
        $permission = new Horde_Kolab_Storage_Folder_Permission(
            'test', $this->folder, $this->groups
        );
        $permission->addGroupPermission('horde_test', Horde_Perms::ALL, true);
    }

    /**
     * Test saving permissions
     */
    public function testSave()
    {
        $this->markTestIncomplete('Currently broken');
        $GLOBALS['conf']['auth']['driver'] = 'auto';
        $GLOBALS['conf']['group']['driver'] = 'mock';

        $folder = new DummyFolder(
            array(
                'wrobel' => array('l', 'r', 'i', 'd'),
                'reader' => array('l', 'r'),
                'viewer' => array('l'),
                'editor' => array('l', 'r', 'e'),
                'anyone' => array('l'),
                'anonymous' => array(''),
                'group:editors' => array('l', 'r', 'e')
            ),
            'wrobel'
        );
        $perms = new Horde_Kolab_Storage_Folder_Permissions_Default($folder);
        $data = $perms->getData();
        unset($data['guest']);
        unset($data['default']);
        unset($data['users']['viewer']);
        $data['users']['editor'] = Horde_Perms::ALL;
        $data['users']['test'] = Horde_Perms::SHOW | Horde_Perms::READ;
        $data['groups']['group'] = Horde_Perms::SHOW | Horde_Perms::READ;
        $perms->setData($data);
        $perms->save();
        $this->assertNotContains('anyone', array_keys($folder->acl));
        $this->assertNotContains('anonymous', array_keys($folder->acl));
        $this->assertEquals('lr', join('', $folder->acl['test']));
        $this->assertEquals('lriswcd', join('', $folder->acl['editor']));
        $this->assertEquals('alriswcd', join('', $folder->acl['wrobel']));
    }

    /**
     * Test using Horde permissions.
     */
    public function testHordePermissions()
    {
        $this->markTestIncomplete('Currently broken');
        $GLOBALS['conf']['auth']['driver'] = 'auto';
        $GLOBALS['conf']['group']['driver'] = 'mock';

        $folder = new DummyFolder(array(), 'wrobel');
        $hperms = new Horde_Perms_Permission('test');
        $hperms->addUserPermission('wrobel', Horde_Perms::SHOW, false);
        $perms = new Horde_Kolab_Storage_Folder_Permissions_Default($folder, $hperms->data);
        $perms->save();
        $this->assertEquals('al', join('', $folder->acl['wrobel']));
    }
}
