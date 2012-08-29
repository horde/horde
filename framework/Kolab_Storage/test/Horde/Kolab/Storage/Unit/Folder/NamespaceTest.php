<?php
/**
 * Test the handling of namespaces.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @subpackage UnitTests
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the handling of namespaces.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Folder_NamespaceTest
extends Horde_Kolab_Storage_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_storage = $this->getMock('Horde_Kolab_Storage', array(), array(), '', false, false);
        $this->_connection = $this->getMock('Horde_Kolab_Storage_Driver');
    }

    public function testTitleForPersonalNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX', $namespace);
            $this->assertEquals('', $folder->getTitle());
        }
    }

    public function testTitleWithoutPersonalPrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/test', $namespace);
            $this->assertEquals('test', $folder->getTitle());
        }
    }

    public function testTitleWithoutOtherUserPrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/his_folder', $namespace);
            $this->assertEquals('his_folder', $folder->getTitle());
        }
    }

    public function testTitleSeparator()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/test/sub', $namespace);
            $this->assertEquals('sub', $folder->getTitle());
        }
    }

    public function testOwnerForPersonalNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testOwnerInPersonalNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/mine', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testOwnerForOtherUserNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testOwnerInOtherUserNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testAnonymousForSharedNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('shared.test', $namespace);
            $this->assertFalse($folder->getOwner());
        }
    }

    public function testOwnerDomain()
    {
        foreach ($this->_getNamespaces('test@example.com') as $namespace) {
            $folder = $this->_getFolder('user/test/mine', $namespace);
            $this->assertEquals('test@example.com', $folder->getOwner());
        }
    }

    public function testOwnerCurrentDomain()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine@example.com', $namespace);
            $this->assertEquals('test@example.com', $folder->getOwner());
        }
    }

    public function testSubpathWithoutUsername()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine', $namespace);
            $this->assertEquals('mine', $folder->getSubpath());
        }
    }

    public function testSubpathWithoutPrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/a/b', $namespace);
            $this->assertEquals('a/b', $folder->getSubpath());
        }
    }

    public function testParent()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/a/b', $namespace);
            $this->assertEquals('INBOX/a', $folder->getParent());
        }
    }

    public function testRoot()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/a', $namespace);
            $this->assertEquals('INBOX', $folder->getParent());
        }
    }

    public function testParentInOther()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine/a', $namespace);
            $this->assertEquals('user/test/mine', $folder->getParent());
        }
    }

    public function testRootInOther()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine', $namespace);
            $this->assertEquals('user/test', $folder->getParent());
        }
    }

    public function testParentInShared()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('shared.a/b', $namespace);
            $this->assertEquals('shared.a', $folder->getParent());
        }
    }

    public function testRootInShared()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('shared.a', $namespace);
            $this->assertEquals('', $folder->getParent());
        }
    }

    public function testSubpathWithoutSharedPrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('shared.a/b', $namespace);
            $this->assertEquals('a/b', $folder->getSubpath());
        }
    }

    public function testConstructFolderNamePersonal()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->assertEquals('INBOX/b', $namespace->constructFolderName('test', 'b'));
        }
    }

    public function testConstructFolderNameOther()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->assertEquals('user/other/c', $namespace->constructFolderName('other', 'c'));
        }
    }

    public function testConstructFolderNameShared()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->assertEquals('shared.c', $namespace->constructFolderName(null, 'c'));
        }
    }

    public function testConstructFolderPathPersonal()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->assertEquals('INBOX/b', $namespace->constructFolderName('test', 'b', 'INBOX'));
        }
    }

    public function testConstructFolderPathOther()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->assertEquals('user/other/c', $namespace->constructFolderName('other', 'c', 'user'));
        }
    }

    public function testConstructFolderPathOtherWithDomain()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->assertEquals('user/other/c@domain.de', $namespace->constructFolderName('other@domain.de', 'c', 'user'));
        }
    }

    public function testConstructFolderPathOtherWithoutDomain()
    {
        foreach ($this->_getNamespaces('test@domain.de') as $namespace) {
            $this->assertEquals('user/other/c', $namespace->constructFolderName('other@domain.de', 'c', 'user'));
        }
    }

    public function testConstructFolderPathShared()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->assertEquals('shared.c', $namespace->constructFolderName(false, 'c', ''));
        }
    }

    public function testToString()
    {
        $namespace = new Horde_Kolab_Storage_Folder_Namespace_Fixed('test@example.com');
        $this->assertEquals(
            'Horde_Kolab_Storage_Folder_Namespace_Fixed: "INBOX" (personal, "/"), "user" (other, "/"), "" (shared, "/")',
            (string) $namespace
        );
    }

    public function testSerializable()
    {
        $data = array();
        foreach ($this->_getNamespaces() as $namespace) {
            $data[] = (string) unserialize(serialize($namespace));
        }
        $this->assertEquals(
            array(
                'Horde_Kolab_Storage_Folder_Namespace_Fixed: "INBOX" (personal, "/"), "user" (other, "/"), "" (shared, "/")',
                'Horde_Kolab_Storage_Folder_Namespace_Config: "INBOX" (personal, "/"), "user" (other, "/"), "" (shared, "/")',
                'Horde_Kolab_Storage_Folder_Namespace_Imap: "INBOX" (personal, "/"), "user" (other, "/"), "" (shared, "/")'
            ),
            $data
        );
    }

    private function _getFolder($name, $namespace)
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->_connection->expects($this->any())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $this->_connection->expects($this->any())
            ->method('listFolders')
            ->will($this->returnValue(array($name)));
        $this->_connection->expects($this->any())
            ->method('listAnnotation')
            ->will($this->returnValue(array($name => 'mail')));
        $list = new Horde_Kolab_Storage_List_Query_List_Base(
            $this->_connection,
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
        return new Horde_Kolab_Storage_Folder_Base($list, $name);
    }

    private function _getNamespaces($user = 'test')
    {
        return array(
            new Horde_Kolab_Storage_Folder_Namespace_Fixed($user),
            new Horde_Kolab_Storage_Folder_Namespace_Config(
                $user,
                array(
                    array(
                        'type' => Horde_Kolab_Storage_Folder_Namespace::PERSONAL,
                        'name' => 'INBOX/',
                        'delimiter' => '/',
                        'add' => true,
                    ),
                    array(
                        'type' => Horde_Kolab_Storage_Folder_Namespace::OTHER,
                        'name' => 'user/',
                        'delimiter' => '/',
                    ),
                    array(
                        'type' => Horde_Kolab_Storage_Folder_Namespace::SHARED,
                        'name' => '',
                        'delimiter' => '/',
                        'prefix' => 'shared.'
                    ),
                )
            ),
            new Horde_Kolab_Storage_Folder_Namespace_Imap(
                $user,
                array(
                    array(
                        'name'      => 'INBOX/',
                        'type'      =>  Horde_Kolab_Storage_Folder_Namespace::PERSONAL,
                        'delimiter' => '/',
                    ),
                    array(
                        'name'      => 'user/',
                        'type'      =>  Horde_Kolab_Storage_Folder_Namespace::OTHER,
                        'delimiter' => '/',
                    ),
                    array(
                        'name'      => '',
                        'type'      =>  Horde_Kolab_Storage_Folder_Namespace::SHARED,
                        'delimiter' => '/',
                        'prefix' => 'shared.'
                    ),
                )
            )
        );
    }
}