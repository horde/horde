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
    public function testFolderTitleIsEmptyForPrivateNamespace()
    {
        $this->markTestIncomplete('This expectation currently does not hold as we are not using the namespace handler yet.');
        $folder = new Horde_Kolab_Storage_Folder(
            'INBOX',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('', $folder->getTitle());
    }

    public function testFolderTitleDoesNotContainPrivateNamespace()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'INBOX/test',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('test', $folder->getTitle());
    }

    public function testFolderTitleOfOtherUserDoesNotContainUserPrefixAndOtherUserName()
    {
        $this->markTestIncomplete('This expectation currently does not hold as we are not using the namespace handler yet.');
        $folder = new Horde_Kolab_Storage_Folder(
            'user/test/his_folder',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('his_folder', $folder->getTitle());
    }

    public function testFolderTitleReplacesSeparatorWithDoubleColon()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'INBOX/test/sub',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('test:sub', $folder->getTitle());
    }

    public function testFolderTitleConvertsUtf7()
    {
        Horde_String::setDefaultCharset('UTF8');
        $name = Horde_String::convertCharset('äöü', 'UTF8', 'UTF7-IMAP');
        $folder = new Horde_Kolab_Storage_Folder(
            'INBOX/' . $name,
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('äöü', $folder->getTitle());
    }

    public function testFolderTitleIsAccessibleForNewFolders()
    {
        $_SESSION['horde_auth']['userId'] = 'test';
        $folder = new Horde_Kolab_Storage_Folder(
            null,
            new Horde_Kolab_Storage_Namespace()
        );
        $folder->setName('test');
        $this->assertEquals('test', $folder->getTitle());
    }

    public function testFolderOwnerIsCurrentUserIfPrefixMatchesPrivateNamespace()
    {
        $this->markTestIncomplete('This expectation currently does not hold as we are not using the namespace handler yet.');
        $_SESSION['horde_auth']['userId'] = 'test';
        $folder = new Horde_Kolab_Storage_Folder(
            'INBOX',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('test', $folder->getOwner());
    }

    public function testFolderOwnerIsCurrentUserIfPrefixContainsPrivateNamespace()
    {
        $_SESSION['horde_auth']['userId'] = 'test';
        $folder = new Horde_Kolab_Storage_Folder(
            'INBOX/mine',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('test', $folder->getOwner());
    }

    public function testFolderOwnerIsOtherUserIfPrefixMatchesOtherNamespace()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'user/test',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('test', $folder->getOwner());
    }

    public function testFolderOwnerIsOtherUserIfPrefixContainsOtherNamespace()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'user/test/mine',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('test', $folder->getOwner());
    }

    public function testFolderOwnerIsAnonymousIfPrefixContainsSharedNamespace()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'shared.test',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('anonymous', $folder->getOwner());
    }

    public function testFolderOwnerIsAccessibleForNewFolders()
    {
        $_SESSION['horde_auth']['userId'] = 'test';
        $folder = new Horde_Kolab_Storage_Folder(
            null,
            new Horde_Kolab_Storage_Namespace()
        );
        $folder->setName('test');
        $this->assertEquals('test', $folder->getOwner());
    }

    public function testSetfolderDoesAddDefaultPersonalNamespace()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            null,
            new Horde_Kolab_Storage_Namespace()
        );
        $folder->setName('test:this');
        $this->assertEquals('INBOX/test/this', $folder->getName());
    }

    public function testSetfolderReplacesDoubleColonWithSeparator()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            null,
            new Horde_Kolab_Storage_Namespace()
        );
        $folder->setName('a:b:c');
        $this->assertEquals('INBOX/a/b/c', $folder->getName());
    }

    public function testSetfolderConvertsToUtf7()
    {
        Horde_Nls::setCharset('UTF8');
        $folder = new Horde_Kolab_Storage_Folder(
            null,
            new Horde_Kolab_Storage_Namespace()
        );
        $folder->setName('äöü');
        $this->assertEquals(
            'INBOX/äöü',
            Horde_String::convertCharset($folder->getName(), 'UTF7-IMAP')
        );
    }

    public function testFolderSubpathIsAccessibleForNewFolders()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            null,
            new Horde_Kolab_Storage_Namespace()
        );
        $folder->setName('test');
        $this->assertEquals('test', $folder->getSubpath());
    }

    public function testFolderSubpathDoesNotContainUsernameIfPrefixContainsOtherNamespace()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'user/test/mine',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('mine', $folder->getSubpath());
    }

    public function testFolderSubpathReturnsSubpathWithoutNamespacePrefix()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'INBOX/a/b',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('a/b', $folder->getSubpath());
    }

    public function testFolderSubpathReturnsSubpathWithoutSharedPrefix()
    {
        $folder = new Horde_Kolab_Storage_Folder(
            'shared.a/b',
            new Horde_Kolab_Storage_Namespace()
        );
        $this->assertEquals('a/b', $folder->getSubpath());
    }
}