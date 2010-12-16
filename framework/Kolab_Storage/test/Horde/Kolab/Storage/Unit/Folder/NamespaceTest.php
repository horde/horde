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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the handling of namespaces.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Folder_NamespaceTest
extends Horde_Kolab_Storage_TestCase
{
    public function setUp()
    {
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
            $this->assertEquals('test:sub', $folder->getTitle());
        }
    }

    public function testTitleUtf7()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $name = Horde_String::convertCharset('äöü', 'UTF8', 'UTF7-IMAP');
            $folder = $this->_getFolder('INBOX/' . $name, $namespace);
            $this->assertEquals('äöü', $folder->getTitle());
        }
    }

    public function testTitleForNewFolders()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test'));
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('test');
            $this->assertEquals('test', $folder->getTitle());
        }
    }

    public function testOwnerForPersonalNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test'));
            $folder = $this->_getFolder('INBOX', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testOwnerInPersonalNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test'));
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
            $this->assertEquals('anonymous', $folder->getOwner());
        }
    }

    public function testOwnerForNewFolders()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test'));
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('test');
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testOwnerDomain()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test@example.com'));
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

    public function testSetnameDefaultPersonalNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('test:this');
            $this->assertEquals('INBOX/test/this', $folder->getName());
        }
    }

    public function testSetnameSeparator()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('a:b:c');
            $this->assertEquals('INBOX/a/b/c', $folder->getName());
        }
    }

    public function testSetnameUtf7()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('äöü');
            $this->assertEquals(
                'INBOX/äöü',
                Horde_String::convertCharset($folder->getName(), 'UTF7-IMAP', 'UTF8')
            );
        }
    }

    public function testSetnameInSharedNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('shared.test');
            $this->assertEquals('shared.test', $folder->getName());
        }
    }

    public function testSetnameInOthersNS()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('user:test:test');
            $this->assertEquals('user/test/test', $folder->getName());
        }
    }

    public function testSubpathForNewFolders()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('test');
            $this->assertEquals('test', $folder->getSubpath());
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

    public function testSubpathWithoutSharedPrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('shared.a/b', $namespace);
            $this->assertEquals('a/b', $folder->getSubpath());
        }
    }

    private function _getFolder($name, $namespace)
    {
        $this->_connection->expects($this->any())
            ->method('getNamespace')
            ->will($this->returnValue($namespace));
        $folder = new Horde_Kolab_Storage_Folder_Base($this->_storage, $this->_connection, $name);
        return $folder;
    }

    private function _getNamespaces()
    {
        return array(
            new Horde_Kolab_Storage_Folder_Namespace_Fixed(),
            new Horde_Kolab_Storage_Folder_Namespace_Config(
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
                    ),
                ),
                array(
                    'INBOX/' => array(
                        'add' => true,
                    ),
                    '' => array(
                        'prefix' => 'shared.',
                    ),
                )
            ),
            
        );
    }
}