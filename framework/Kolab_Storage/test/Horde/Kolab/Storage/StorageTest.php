<?php
/**
 * Test the Kolab storage handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once 'Autoload.php';

/**
 * Test the Kolab storage handler.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_StorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test setup.
     *
     * @return NULL
     */
    public function setUp()
    {
        /* $world = $this->prepareBasicSetup(); */

        /* /\** Prepare a Kolab test storage *\/ */
        /* $params   = array('driver'   => 'Mock', */
        /*                   'username' => 'wrobel@example.org', */
        /*                   'password' => 'none'); */
        /* $storage1 = Horde_Kolab_Storage::singleton('imap', $params); */

        /* $folder = $this->prepareNewFolder($storage1, 'Contacts', 'contact', true); */
        /* $perms  = $folder->getPermission(); */
        /* $perms->addUserPermission('test@example.org', Horde_Perms::SHOW); */
        /* $perms->save(); */

        /* $folder = $this->prepareNewFolder($storage1, 'Calendar', 'event', true); */
        /* $perms  = $folder->getPermission(); */
        /* $perms->addUserPermission('test@example.org', Horde_Perms::SHOW); */
        /* $perms->save(); */

        /* /\** Prepare a Kolab test storage *\/ */
        /* $storage2 = $this->authenticate($world['auth'], */
        /*                                 'test@example.org', */
        /*                                 'test'); */

        /* $this->prepareNewFolder($storage2, 'Contacts', 'contact', true); */
        /* $this->prepareNewFolder($storage2, 'TestContacts', 'contact'); */
        /* $this->prepareNewFolder($storage2, 'Calendar', 'event', true); */
        /* $this->prepareNewFolder($storage2, 'TestCalendar', 'event'); */

        /* $this->storage = &$storage2; */
    }

    /**
     * Test destruction.
     *
     * @return NULL
     */
    public function tearDown()
    {
        /* Horde_Imap_Client_Mock::clean(); */
        /* if ($this->storage) { */
        /*     $this->storage->clean(); */
        /* } */
    }

    /**
     * Test class construction.
     *
     * @return NULL
     */
    public function testConstruct()
    {
        $this->markTestSkipped();

        $this->assertTrue($this->storage instanceOf Horde_Kolab_Storage);
    }

    /**
     * Test listing folders.
     *
     * @return NULL
     */
    public function testListFolders()
    {
        $this->markTestSkipped();

        $folders = $this->storage->listFolders();
        $this->assertContains('INBOX/Contacts', $folders);
    }

    /**
     * Test folder retrieval.
     *
     * @return NULL
     */
    public function testGetFolders()
    {
        $this->markTestSkipped();

        $storage = new Horde_Kolab_Storage(
            'Imap',
            array(
                'username' => 'test',
                'password' => 'test',
            )  
        );
        $folders = $storage->getFolders();
        $this->assertEquals(6, count($folders));
        $folder_names = array();
        foreach ($folders as $folder) {
            $folder_names[] = $folder->name;
        }
        $this->assertContains('INBOX/Contacts', $folder_names);
    }

    public function testGetFolder()
    {
        $this->markTestSkipped();
        
        $GLOBALS['language'] = 'de_DE';
        $storage = new Horde_Kolab_Storage(
            new Horde_Kolab_Storage_Connection(),
            'Imap',
            array(
                'username' => 'test',
                'password' => 'test',
            )  
        );
        $folder = $storage->getFolder('INBOX');
        $this->assertEquals('INBOX', $folder->name);
    }

    /**
     * Test retrieving by share ID.
     *
     * @return NULL
     */
    public function testGetByShare()
    {
        $this->markTestSkipped();

        $folder = $this->storage->getByShare('test@example.org', 'event');
        $this->assertEquals('INBOX/Calendar', $folder->name);
    }

    /**
     * Test fetching the folder type.
     *
     * @return NULL
     */
    public function testGetByType()
    {
        $this->markTestSkipped();

        $folders = $this->storage->getByType('event');
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
     *
     * @return NULL
     */
    public function testGetDefault()
    {
        $this->markTestSkipped();

        $folder = $this->storage->getDefault('event');
        $this->assertEquals('INBOX/Calendar', $folder->name);
        $folder = $this->storage->getDefault('contact');
        $this->assertEquals('INBOX/Contacts', $folder->name);
    }

    /**
     * Test foreign folder owner.
     *
     * @return NULL
     */
    public function testGetForeignOwner()
    {
        $this->markTestSkipped();

        $folder = $this->storage->getFolder('user/wrobel');
        $this->assertEquals('wrobel@example.org', $folder->getOwner());
    }

    /**
     * Test retrieving a foreign default folder.
     *
     * @return NULL
     */
    public function testGetForeignDefault()
    {
        $this->markTestSkipped();

        $folder = $this->storage->getForeignDefault('wrobel@example.org', 'event');
        $this->assertEquals('user/wrobel/Calendar', $folder->name);
        $this->assertEquals('user%2Fwrobel%2FCalendar', $folder->getShareId());
        $folder = $this->storage->getForeignDefault('wrobel@example.org', 'contact');
        $this->assertEquals('user/wrobel/Contacts', $folder->name);
        $this->assertEquals('user%2Fwrobel%2FContacts', $folder->getShareId());
    }

    /**
     * Test folder creation.
     *
     * @return NULL
     */
    public function testCreate()
    {
        $this->markTestSkipped();

        $folder = $this->storage->getNewFolder();
        $folder->setName('Notes');
        $folder->save(array());
        $result = $this->storage->getFolder('INBOX/Notes');
        $this->assertTrue($result instanceOf Horde_Kolab_Storage_Folder);
    }

    /**
     * Test cache update.
     *
     * @return NULL
     */
    public function testCacheAdd()
    {
        $this->markTestSkipped();

        $params   = array('driver'   => 'Mock',
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
     *
     * @return NULL
     */
    public function testCacheDelete()
    {
        $this->markTestSkipped();

        $params   = array('driver'   => 'Mock',
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
     *
     * @return NULL
     */
    public function testRename()
    {
        $this->markTestSkipped();

        $folder = &$this->storage->getFolder('INBOX/TestContacts');
        $folder->setName('TestNotes');
        $folder->save(array());
        $this->assertNotContains('INBOX/TestContacts',
                                 $this->storage->listFolders());
        $this->assertContains('INBOX/TestNotes', $this->storage->listFolders());
    }

    /**
     * Test folder removal.
     *
     * @return NULL
     */
    public function testRemove()
    {
        $this->markTestSkipped();

        $folder = &$this->storage->getFolder('INBOX/Calendar');
        $this->assertTrue($folder->exists());
        $this->assertTrue($folder->isDefault());
        $folder->delete();
        $this->assertNotContains('INBOX/Calendar', $this->storage->listFolders());
    }

    /**
     * Test the list cache.
     *
     * @return NULL
     */
    public function testCaching()
    {
        $this->markTestSkipped();

        $params   = array('driver'   => 'Mock',
                          'username' => 'cache@example.org',
                          'password' => 'none');
        $storage3 = Horde_Kolab_Storage::singleton('imap', $params);
        $folders  = $storage3->getFolders();
        $this->assertTrue(count($folders) == 1);
        $folders = $storage3->getByType('event');
        $this->assertTrue(empty($folders));
        $default = $storage3->getDefault('event');
        $this->assertTrue(empty($default));
        $connection = $storage3->getConnection();
        $addfolder  = new Horde_Kolab_Storage_Folder(null);
        $addfolder->restore($storage3, $connection->connection);
        $addfolder->setName('TestFolder');
        $addfolder->save(array('type' => 'event', 'default' => true));
        $this->assertContains('INBOX/TestFolder', $storage3->listFolders());
        $this->assertEquals('test@example.org', $addfolder->getOwner());
        $folders = $storage3->getFolders();
        $names   = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/TestFolder', $names);
        $folders = $storage3->getByType('event');
        $names   = array();
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
        $names   = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/NewCal', $names);
        $folders = $storage3->getByType('event');
        $names   = array();
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
