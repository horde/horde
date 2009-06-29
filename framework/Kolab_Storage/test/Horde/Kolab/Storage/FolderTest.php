<?php
/**
 * Test the Kolab folder handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/FolderTest.php,v 1.11 2009/06/09 23:23:39 slusarz Exp $
 *
 * @package Kolab_Storage
 */

/**
 *  We need the unit test framework 
 */
require_once 'Horde/Kolab/Test/Storage.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Storage/Folder.php';
require_once 'Horde/Kolab/Storage/List.php';
require_once 'Horde/Kolab/IMAP.php';
require_once 'Horde/Kolab/IMAP/test.php';

/**
 * Test the Kolab folder handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/FolderTest.php,v 1.11 2009/06/09 23:23:39 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_FolderTest extends Horde_Kolab_Test_Storage
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
        $this->prepareNewFolder($world['storage'], 'NewContacts', 'contact');

        $this->list = &new Kolab_List();
    }

    /**
     * Test class creation.
     */
    public function testConstruct()
    {
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $this->assertEquals('INBOX/Contacts', $folder->name);
        $this->assertTrue(is_array($folder->_data));
        $this->assertTrue(empty($folder->_data));
        $this->assertTrue(empty($folder->new_name));
    }

    /**
     * Test renaming.
     */
    public function testSetName()
    {
        $folder = &new Kolab_Folder('INBOX/Contacts');
        $folder->setName('TestAÖÜ');
        $this->assertEquals(Horde_String::convertCharset('INBOX/TestAÖÜ', NLS::getCharset(), 'UTF7-IMAP'), $folder->new_name);
    }

    /**
     * Test saving objects.
     */
    public function testSave()
    {
        $folder = &new Kolab_Folder();
        $folder->setList($this->list);

        $result = $folder->save();
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals("Cannot create this folder! The name has not yet been set.", $result->message);
        }
        $folder->setName('TestÄÖÜ');
        $result = $folder->exists();
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertFalse($result);
        $result = $folder->accessible();
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertFalse($result);

        $result = $folder->save();
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $this->assertEquals("wrobel@example.org", $folder->getOwner());
        $this->assertEquals("TestÄÖÜ", $folder->getTitle());
        $this->assertEquals("mail", $folder->getType());
        $this->assertFalse($folder->isDefault());
        $this->assertTrue($folder->exists());
        $this->assertTrue($folder->accessible());

        $folder2 = &new Kolab_Folder();
        $folder2->setList($this->list);
        $folder2->setName('TestEvents');
        $attributes = array(
            'type' => 'event',
            'default' => true,
        );
        $result = $folder2->save($attributes);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $this->assertEquals("wrobel@example.org", $folder2->getOwner());
        $this->assertEquals("TestEvents", $folder2->getTitle());
        $this->assertEquals("event", $folder2->getType());
        $this->assertTrue($folder2->isDefault());

        $attributes = array(
            'default' => false,
            'dummy'   =>'test',
            'desc'   =>'A test folder',
        );
        $result = $folder2->save($attributes);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $this->assertEquals('test', $folder2->getAttribute('dummy'));
        $this->assertEquals('A test folder', $folder2->getAttribute('desc'));

        $folder2->setName('TestEventsNew');
        $result = $folder2->save($attributes);
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);

        $result = $folder->delete();
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
        $result = $folder2->delete();
        if (is_a($result, 'PEAR_Error')) {
            $this->assertEquals('', $result->message);
        }
        $this->assertTrue($result);
    }

    /**
     * Test class construction with missing configuration data.
     */
    public function testGetImapFailNoServer()
    {
        $session = Horde_Kolab_Session::singleton('anonymous', null, true);
        $imap = $session->getImapParams();
        $this->assertEquals('localhost', $imap['hostspec']);
    }

    /**
     * Test triggering.
     */
    public function testTrigger()
    {
        $folder = $this->getMock('Kolab_Folder', array('triggerUrl'));
        $folder->expects($this->once())
            ->method('triggerUrl')
            ->with($this->equalTo('https://fb.example.org/freebusy/trigger/wrobel@example.org/Kalender.pfb'));

        $folder->setList(&$this->list);
        $folder->setName('Kalender');
        $folder->save(array('type' => 'event'));
        
        $folder = $this->getMock('Kolab_Folder', array('triggerUrl'));
        $folder->expects($this->once())
            ->method('triggerUrl')
            ->with($this->equalTo('https://fb.example.org/freebusy/trigger/test@example.org/Kalender.pfb'));

        $folder->setList(&$this->list);
        $folder->setName('user/test/Kalender');
        $folder->save(array('type' => 'event'));
        
    }
}
