<?php
/**
 * Test the handling of namespaces.
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
class Horde_Kolab_Storage_NamespaceTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_storage = $this->getMock('Horde_Kolab_Storage', array(), array(), '', false, false);
        $this->_connection = $this->getMock('Horde_Kolab_Storage_Driver');
    }

    public function testFolderTitleIsEmptyForPersonalNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX', $namespace);
            $this->assertEquals('', $folder->getTitle());
        }
    }

    public function testFolderTitleDoesNotContainPersonalNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/test', $namespace);
            $this->assertEquals('test', $folder->getTitle());
        }
    }

    public function testFolderTitleOfOtherUserDoesNotContainUserPrefixAndOtherUserName()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/his_folder', $namespace);
            $this->assertEquals('his_folder', $folder->getTitle());
        }
    }

    public function testFolderTitleReplacesSeparatorWithDoubleColon()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/test/sub', $namespace);
            $this->assertEquals('test:sub', $folder->getTitle());
        }
    }

    public function testFolderTitleConvertsUtf7()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $name = Horde_String::convertCharset('äöü', 'UTF8', 'UTF7-IMAP');
            $folder = $this->_getFolder('INBOX/' . $name, $namespace);
            $this->assertEquals('äöü', $folder->getTitle());
        }
    }

    public function testFolderTitleIsAccessibleForNewFolders()
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

    public function testFolderOwnerIsCurrentUserIfPrefixMatchesPersonalNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test'));
            $folder = $this->_getFolder('INBOX', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsCurrentUserIfPrefixContainsPersonalNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test'));
            $folder = $this->_getFolder('INBOX/mine', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsOtherUserIfPrefixMatchesOtherNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsOtherUserIfPrefixContainsOtherNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine', $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsAnonymousIfPrefixContainsSharedNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('shared.test', $namespace);
            $this->assertEquals('anonymous', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsAccessibleForNewFolders()
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

    public function testFolderOwnerHasDomainFromFolderDomain()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $this->_connection->expects($this->any())
                ->method('getAuth')
                ->will($this->returnValue('test@example.com'));
            $folder = $this->_getFolder('user/test/mine', $namespace);
            $this->assertEquals('test@example.com', $folder->getOwner());
        }
    }

    public function testFolderOwnerHasDomainFromCurrentUserIfNoFolderDomain()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine@example.com', $namespace);
            $this->assertEquals('test@example.com', $folder->getOwner());
        }
    }

    public function testSetnameDoesAddDefaultPersonalNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('test:this');
            $this->assertEquals('INBOX/test/this', $folder->getName());
        }
    }

    public function testSetnameReplacesDoubleColonWithSeparator()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->restore($this->_storage, $this->_connection);
            $folder->setName('a:b:c');
            $this->assertEquals('INBOX/a/b/c', $folder->getName());
        }
    }

    public function testSetnameConvertsToUtf7()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->restore($this->_storage, $this->_connection);
            $folder->setName('äöü');
            $this->assertEquals(
                'INBOX/äöü',
                Horde_String::convertCharset($folder->getName(), 'UTF7-IMAP', 'UTF8')
            );
        }
    }

    public function testSetnameAllowsCreatingFoldersInSharedNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('shared.test');
            $this->assertEquals('shared.test', $folder->getName());
        }
    }

    public function testSetnameAllowsCreatingFoldersInOthersNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('user:test:test');
            $this->assertEquals('user/test/test', $folder->getName());
        }
    }

    public function testFolderSubpathIsAccessibleForNewFolders()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder(null, $namespace);
            $folder->setName('test');
            $this->assertEquals('test', $folder->getSubpath());
        }
    }

    public function testFolderSubpathDoesNotContainUsernameIfPrefixContainsOtherNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('user/test/mine', $namespace);
            $this->assertEquals('mine', $folder->getSubpath());
        }
    }

    public function testFolderSubpathReturnsSubpathWithoutNamespacePrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = $this->_getFolder('INBOX/a/b', $namespace);
            $this->assertEquals('a/b', $folder->getSubpath());
        }
    }

    public function testFolderSubpathReturnsSubpathWithoutSharedPrefix()
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
        $folder = new Horde_Kolab_Storage_Folder_Base($name);
        $folder->restore($this->_storage, $this->_connection);
        return $folder;
    }

    private function _getNamespaces()
    {
        return array(
            new Horde_Kolab_Storage_Driver_Namespace_Fixed(),
            new Horde_Kolab_Storage_Driver_Namespace_Config(
                array(
                    array(
                        'type' => Horde_Kolab_Storage_Driver_Namespace::PERSONAL,
                        'name' => 'INBOX/',
                        'delimiter' => '/',
                        'add' => true,
                    ),
                    array(
                        'type' => Horde_Kolab_Storage_Driver_Namespace::OTHER,
                        'name' => 'user/',
                        'delimiter' => '/',
                    ),
                    array(
                        'type' => Horde_Kolab_Storage_Driver_Namespace::SHARED,
                        'name' => '',
                        'delimiter' => '/',
                        'prefix' => 'shared.'
                    ),
                )
            ),
            new Horde_Kolab_Storage_Driver_Namespace_Imap(
                array(
                    array(
                        'name'      => 'INBOX/',
                        'type'      =>  Horde_Kolab_Storage_Driver_Namespace::PERSONAL,
                        'delimiter' => '/',
                    ),
                    array(
                        'name'      => 'user/',
                        'type'      =>  Horde_Kolab_Storage_Driver_Namespace::OTHER,
                        'delimiter' => '/',
                    ),
                    array(
                        'name'      => '',
                        'type'      =>  Horde_Kolab_Storage_Driver_Namespace::SHARED,
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