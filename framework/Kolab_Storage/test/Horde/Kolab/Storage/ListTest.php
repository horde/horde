<?php
/**
 * Test the Kolab list handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/ListTest.php,v 1.7 2009/01/06 17:49:28 jan Exp $
 *
 * @package Kolab_Storage
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the Kolab list handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/ListTest.php,v 1.7 2009/01/06 17:49:28 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_ListTest extends Horde_Kolab_Test_Storage
{

    /**
     * Test setup.
     */
    public function setUp()
    {
        $world = $this->prepareBasicSetup();

        /** Prepare a Kolab test storage */
        $params = array('driver'   => 'Mock',
                        'username' => 'wrobel@example.org',
                        'password' => 'none');
        $storage1 = Horde_Kolab_Storage::singleton('imap', $params);

        $folder = $this->prepareNewFolder($storage1, 'Contacts', 'contact', true);
	$perms = $folder->getPermission();
	$perms->addUserPermission('test@example.org', PERMS_SHOW);
	$perms->save();

        $folder = $this->prepareNewFolder($storage1, 'Calendar', 'event', true);
	$perms = $folder->getPermission();
	$perms->addUserPermission('test@example.org', PERMS_SHOW);
	$perms->save();

        /** Prepare a Kolab test storage */
        $storage2 = $this->authenticate($world['auth'],
                                        'test@example.org',
                                        'test');

        $this->prepareNewFolder($storage2, 'Contacts', 'contact', true);
        $this->prepareNewFolder($storage2, 'TestContacts', 'contact');
        $this->prepareNewFolder($storage2, 'Calendar', 'event', true);
        $this->prepareNewFolder($storage2, 'TestCalendar', 'event');

        $this->list = &$storage2;
    }

    /**
     * Test destruction.
     */
    public function tearDown()
    {
        Horde_Imap_Client_Mock::clean();
        $this->list->clean();
    }

    /**
     * Test class construction.
     */
    public function testConstruct()
    {
        $this->assertTrue($this->list instanceOf Horde_Kolab_Storage);
    }

    /**
     * Test listing folders.
     */
    public function testListFolders()
    {
        $folders = $this->list->listFolders();
        $this->assertContains('INBOX/Contacts', $folders);
    }

    /**
     * Test folder retrieval.
     */
    public function testGetFolders()
    {
        $folders = $this->list->getFolders();
        $this->assertEquals(6, count($folders));
        $folder_names = array();
        foreach ($folders as $folder) {
            $folder_names[] = $folder->name;
        }
        $this->assertContains('INBOX/Contacts', $folder_names);
    }

    /**
     * Test retrieving by share ID.
     */
    public function testGetByShare()
    {
        $folder = $this->list->getByShare('test@example.org', 'event');
        $this->assertEquals('INBOX/Calendar', $folder->name);
    }

    /**
     * Test fetching the folder type.
     */
    public function testGetByType()
    {
        $folders = $this->list->getByType('event');
        $this->assertEquals(3, count($folders));
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/Calendar', $names);
        $this->assertContains('INBOX/TestCalendar', $names);
    }

    /**
     * Test retrieving the default folder.
     */
    public function testGetDefault()
    {
        $folder = $this->list->getDefault('event');
        $this->assertEquals('INBOX/Calendar', $folder->name);
        $folder = $this->list->getDefault('contact');
        $this->assertEquals('INBOX/Contacts', $folder->name);
    }

    /**
     * Test foreign folder owner.
     */
    public function testGetForeignOwner()
    {
        $folder = $this->list->getFolder('user/wrobel');
        $this->assertEquals('wrobel@example.org', $folder->getOwner());
    }

    /**
     * Test retrieving a foreign default folder.
     */
    public function testGetForeignDefault()
    {
        $folder = $this->list->getForeignDefault('wrobel@example.org', 'event');
        $this->assertEquals('user/wrobel/Calendar', $folder->name);
        $this->assertEquals('user%2Fwrobel%2FCalendar', $folder->getShareId());
        $folder = $this->list->getForeignDefault('wrobel@example.org', 'contact');
        $this->assertEquals('user/wrobel/Contacts', $folder->name);
        $this->assertEquals('user%2Fwrobel%2FContacts', $folder->getShareId());
    }

    /**
     * Test folder creation.
     */
    public function testCreate()
    {
        $folder = $this->list->getNewFolder();
        $folder->setName('Notes');
        $folder->save(array());
        $result = $this->list->getFolder('INBOX/Notes');
        $this->assertTrue($result instanceOf Horde_Kolab_Storage_Folder);
    }

    /**
     * Test cache update.
     */
    public function testCacheAdd()
    {
        $params = array('driver'   => 'Mock',
                        'username' => 'cacheadd@example.org',
                        'password' => 'none');
        $storage4 = $this->getMock('Horde_Kolab_Storage',
                                   array('addToCache', 'removeFromCache'),
                                   array('imap', $params));
        $storage4->expects($this->once())
            ->method('addToCache');

        $folder = $storage4->getNewFolder();
        $folder->setName('Notes');
        $folder->save(array());
    }
        
    /**
     * Test cache update.
     */
    public function testCacheDelete()
    {
        $params = array('driver'   => 'Mock',
                        'username' => 'cachedel@example.org',
                        'password' => 'none');
        $storage4 = $this->getMock('Horde_Kolab_Storage',
                                   array('addToCache', 'removeFromCache'),
                                   array('imap', $params));
        $storage4->expects($this->once())
            ->method('removeFromCache');

        $folder = $storage4->getNewFolder();
        $folder->setName('Notes');
        $folder->save(array());
        $folder->delete();
    }
        

    /**
     * Test renaming folders.
     */
    public function testRename()
    {
        $folder = &$this->list->getFolder('INBOX/TestContacts');
        $folder->setName('TestNotes');
        $folder->save(array());
        $this->assertNotContains('INBOX/TestContacts', $this->list->listFolders());
        $this->assertContains('INBOX/TestNotes', $this->list->listFolders());
    }

    /**
     * Test folder removal.
     */
    public function testRemove()
    {
        $folder = &$this->list->getFolder('INBOX/Calendar');
        $this->assertTrue($folder->exists());
        $this->assertTrue($folder->isDefault());
        $folder->delete();
        $this->assertNotContains('INBOX/Calendar', $this->list->listFolders());
    }

    /**
     * Test the list cache.
     */
    public function testCaching()
    {
        $params = array('driver'   => 'Mock',
                        'username' => 'cache@example.org',
                        'password' => 'none');
        $storage3 = Horde_Kolab_Storage::singleton('imap', $params);
        $folders = $storage3->getFolders();
        $this->assertTrue(count($folders) == 1);
        $folders = $storage3->getByType('event');
        $this->assertTrue(empty($folders));
        $default = $storage3->getDefault('event');
        $this->assertTrue(empty($default));
        $connection = $storage3->getConnection();
        $addfolder = new Horde_Kolab_Storage_Folder(null);
        $addfolder->restore($storage3, $connection->connection);
        $addfolder->setName('TestFolder');
        $addfolder->save(array('type' => 'event', 'default' => true));
        $this->assertContains('INBOX/TestFolder', $storage3->listFolders());
        $this->assertEquals('test@example.org', $addfolder->getOwner());
        $folders = $storage3->getFolders();
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/TestFolder', $names);
        $folders = $storage3->getByType('event');
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/TestFolder', $names);
        $default = $storage3->getDefault('event');
        $this->assertTrue($default !== false);
        $this->assertEquals('INBOX/TestFolder', $default->name);
        $addfolder->setName('NewCal');
        $addfolder->save();
        $folders = $storage3->getFolders();
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/NewCal', $names);
        $folders = $storage3->getByType('event');
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/NewCal', $names);
        $default = $storage3->getDefault('event');
        $this->assertEquals('INBOX/NewCal', $default->name);
        $addfolder->delete();
        $folders = $storage3->getFolders();
        $this->assertTrue(count($folders) == 1);
        $folders = $storage3->getByType('event');
        $this->assertTrue(empty($folders));
        $default = $storage3->getDefault('event');
        $this->assertTrue(empty($default));
    }
}
