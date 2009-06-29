<?php
/**
 * Test the Kolab list handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/ListTest.php,v 1.7 2009/01/06 17:49:28 jan Exp $
 *
 * @package Kolab_Storage
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Storage.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Storage/List.php';

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

        $this->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                                        array('password' => 'none')));

        $this->prepareNewFolder($world['storage'], 'Contacts', 'contact', true);
        $this->prepareNewFolder($world['storage'], 'Calendar', 'event', true);

        $this->assertTrue($world['auth']->authenticate('test@example.org',
                                                        array('password' => 'test')));

        $this->prepareNewFolder($world['storage'], 'Contacts', 'contact', true);
        $this->prepareNewFolder($world['storage'], 'TestContacts', 'contact');
        $this->prepareNewFolder($world['storage'], 'Calendar', 'event', true);
        $this->prepareNewFolder($world['storage'], 'TestCalendar', 'event');

        $this->list = &new Kolab_List();
    }

    /**
     * Test class construction.
     */
    public function testConstruct()
    {
        $this->assertEquals(0, $this->list->validity);
        $this->assertTrue(is_array($this->list->_folders));
        $this->assertTrue(empty($this->list->_folders));
        $this->assertTrue(empty($this->list->_defaults));
        $this->assertTrue(empty($this->list->_types));
    }

    /**
     * Test listing folders.
     */
    public function testListFolders()
    {
        $folders = $this->list->listFolders();
        $this->assertFalse(is_a($folders, 'PEAR_Error'));
        $this->assertContains('INBOX/Contacts', $folders);
    }

    /**
     * Test folder retrieval.
     */
    public function testGetFolders()
    {
        $folders = $this->list->getFolders();
        $this->assertEquals(6, count($folders));
        $this->assertContains('INBOX/Contacts', array_keys($this->list->_folders));
    }

    /**
     * Test retrieving by share ID.
     */
    public function testGetByShare()
    {
        $folder = $this->list->getByShare('test@example.org', 'event');
        if (is_a($folder, 'PEAR_Error')) {
            $this->assertEquals('', $folder->message);
        }
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
        $folder = &new Kolab_Folder(null);
        $folder->setList($this->list);
        $folder->setName('Notes');
        $folder->save(array());
        $this->assertContains('INBOX/Notes', array_keys($this->list->_folders));
        $this->assertEquals(1, $this->list->validity);
    }

    /**
     * Test cache update.
     */
    public function testCacheUpdate()
    {
        $this->list = $this->getMock('Kolab_List', array('updateCache'));
        $this->list->expects($this->once())
            ->method('updateCache');

        $folder = &new Kolab_Folder(null);
        $folder->setList($this->list);
        $folder->setName('Notes');
        $this->assertEquals('INBOX/Notes', $folder->new_name);
        $folder->save(array());
        $type = $folder->getType();
        if (is_a($type, 'PEAR_Error')) {
            $this->assertEquals('', $type->getMessage());
        }
        $this->assertEquals('mail', $type);
        $this->assertEquals('INBOX/Notes', $folder->name);
    }
        

    /**
     * Test renaming folders.
     */
    public function testRename()
    {
        $folder = &new Kolab_Folder('INBOX/TestContacts');
        $folder->setList($this->list);
        $folder->setName('TestNotes');
        $folder->save(array());
        $this->assertNotContains('INBOX/TestContacts', array_keys($this->list->_folders));
        $this->assertContains('INBOX/TestNotes', array_keys($this->list->_folders));
        $this->assertEquals(1, $this->list->validity);
    }

    /**
     * Test folder removal.
     */
    public function testRemove()
    {
        $folder = &new Kolab_Folder('INBOX/Calendar');
        $folder->setList($this->list);
        $this->assertTrue($folder->exists());
        $this->assertTrue($folder->isDefault());
        $folder->delete();
        $this->assertNotContains('INBOX/Calendar', array_keys($this->list->_folders));
        $this->assertEquals(1, $this->list->validity);
    }

    /**
     * Test the list cache.
     */
    public function testCaching()
    {
        $GLOBALS['KOLAB_TESTING'] = array();
        $this->list = &new Kolab_List();
        $folders = $this->list->getFolders();
        $this->assertTrue(empty($folders));
        $folders = $this->list->getByType('event');
        $this->assertTrue(empty($folders));
        $default = $this->list->getDefault('event');
        $this->assertTrue(empty($default));
        $addfolder = &new Kolab_Folder(null);
        $addfolder->setName('TestFolder');
        $addfolder->setList($this->list);
        $addfolder->save(array('type' => 'event', 'default' => true));
        $this->assertContains('INBOX/TestFolder', array_keys($this->list->_folders));
        $this->assertEquals('test@example.org', $addfolder->getOwner());
        $folders = $this->list->getFolders();
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/TestFolder', $names);
        $folders = $this->list->getByType('event');
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/TestFolder', $names);
        $default = $this->list->getDefault('event');
        $this->assertTrue($default !== false);
        $this->assertEquals('INBOX/TestFolder', $default->name);
        $addfolder->setName('NewCal');
        $addfolder->save();
        $folders = $this->list->getFolders();
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/NewCal', $names);
        $folders = $this->list->getByType('event');
        $names = array();
        foreach ($folders as $folder) {
            $names[] = $folder->name;
        }
        $this->assertContains('INBOX/NewCal', $names);
        $default = $this->list->getDefault('event');
        $this->assertEquals('INBOX/NewCal', $default->name);
        $addfolder->delete();
        $folders = $this->list->getFolders();
        $this->assertTrue(empty($folders));
        $folders = $this->list->getByType('event');
        $this->assertTrue(empty($folders));
        $default = $this->list->getDefault('event');
        $this->assertTrue(empty($default));
    }
}
