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

    public function testFolderTitleIsEmptyForPrivateNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('INBOX');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('', $folder->getTitle());
        }
    }

    public function testFolderTitleDoesNotContainPrivateNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('INBOX/test');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test', $folder->getTitle());
        }
    }

    public function testFolderTitleOfOtherUserDoesNotContainUserPrefixAndOtherUserName()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('user/test/his_folder');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('his_folder', $folder->getTitle());
        }
    }

    public function testFolderTitleReplacesSeparatorWithDoubleColon()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('INBOX/test/sub');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test:sub', $folder->getTitle());
        }
    }

    public function testFolderTitleConvertsUtf7()
    {
        Horde_Nls::setCharset('UTF8');
        foreach ($this->_getNamespaces() as $namespace) {
            $name = Horde_String::convertCharset('äöü', 'UTF8', 'UTF7-IMAP');
            $folder = new Horde_Kolab_Storage_Folder('INBOX/' . $name);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('äöü', $folder->getTitle());
        }
    }

    public function testFolderTitleIsAccessibleForNewFolders()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $_SESSION['horde_auth']['userId'] = 'test';
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $folder->setName('test');
            $this->assertEquals('test', $folder->getTitle());
        }
    }

    public function testFolderOwnerIsCurrentUserIfPrefixMatchesPrivateNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $_SESSION['horde_auth']['userId'] = 'test';
            $folder = new Horde_Kolab_Storage_Folder('INBOX');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsCurrentUserIfPrefixContainsPrivateNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $_SESSION['horde_auth']['userId'] = 'test';
            $folder = new Horde_Kolab_Storage_Folder('INBOX/mine');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsOtherUserIfPrefixMatchesOtherNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('user/test');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsOtherUserIfPrefixContainsOtherNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('user/test/mine');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsAnonymousIfPrefixContainsSharedNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('shared.test');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('anonymous', $folder->getOwner());
        }
    }

    public function testFolderOwnerIsAccessibleForNewFolders()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $_SESSION['horde_auth']['userId'] = 'test';
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $folder->setName('test');
            $this->assertEquals('test', $folder->getOwner());
        }
    }

    public function testFolderOwnerHasDomainFromFolderDomain()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $_SESSION['horde_auth']['userId'] = 'test@example.com';
            $folder = new Horde_Kolab_Storage_Folder('user/test/mine');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test@example.com', $folder->getOwner());
        }
    }

    public function testFolderOwnerHasDomainFromCurrentUserIfNoFolderDomain()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('user/test/mine@example.com');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('test@example.com', $folder->getOwner());
        }
    }

    public function testSetnameDoesAddDefaultPersonalNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $folder->setName('test:this');
            $this->assertEquals('INBOX/test/this', $folder->getName());
        }
    }

    public function testSetnameReplacesDoubleColonWithSeparator()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $folder->setName('a:b:c');
            $this->assertEquals('INBOX/a/b/c', $folder->getName());
        }
    }

    public function testSetnameConvertsToUtf7()
    {
        Horde_Nls::setCharset('UTF8');
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
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
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $folder->setName('shared.test');
            $this->assertEquals('shared.test', $folder->getName());
        }
    }

    public function testSetnameAllowsCreatingFoldersInOthersNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $folder->setName('user:test:test');
            $this->assertEquals('user/test/test', $folder->getName());
        }
    }

    public function testFolderSubpathIsAccessibleForNewFolders()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder(null);
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $folder->setName('test');
            $this->assertEquals('test', $folder->getSubpath());
        }
    }

    public function testFolderSubpathDoesNotContainUsernameIfPrefixContainsOtherNamespace()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('user/test/mine');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('mine', $folder->getSubpath());
        }
    }

    public function testFolderSubpathReturnsSubpathWithoutNamespacePrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('INBOX/a/b');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('a/b', $folder->getSubpath());
        }
    }

    public function testFolderSubpathReturnsSubpathWithoutSharedPrefix()
    {
        foreach ($this->_getNamespaces() as $namespace) {
            $folder = new Horde_Kolab_Storage_Folder('shared.a/b');
            $folder->restore($this->_storage, $this->_connection, $namespace);
            $this->assertEquals('a/b', $folder->getSubpath());
        }
    }

    private function _getNamespaces()
    {
        return array(
            new Horde_Kolab_Storage_Namespace_Fixed()
        );
    }
}