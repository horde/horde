<?php
/**
 * Test the Kolab folder handler.
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
 * Test the Kolab folder handler.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_FolderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test setup.
     */
    public function setUp()
    {
        /* $world = $this->prepareBasicSetup(); */

        /* $this->storage = $this->authenticate($world['auth'], */
        /*                  'wrobel@example.org', */
        /*                  'none'); */

        /* $this->prepareNewFolder($this->storage, 'Contacts', 'contact', true); */
        /* $this->prepareNewFolder($this->storage, 'NewContacts', 'contact'); */
    }

    /**
     * Test destruction.
     */
    public function tearDown()
    {
        /* Horde_Imap_Client_Mock::clean(); */
        /* if ($this->storage) { */
        /*     $this->storage->clean(); */
        /* } */
    }

    /**
     * Test class creation.
     */
    public function testConstruct()
    {
        $this->markTestIncomplete('Currently broken');
        $GLOBALS['language'] = 'de_DE';
        $folder = new Horde_Kolab_Storage_Folder_Base(
            'INBOX/Contacts',
            new Horde_Kolab_Storage_Driver_Namespace_Fixed()
        );
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
        $this->markTestIncomplete('Currently broken');
        $GLOBALS['language'] = 'de_DE';
        $storage = $this->getMock('Horde_Kolab_Storage', array(), array(), '', false, false);
        $connection = $this->getMock('Horde_Kolab_Storage_Driver');
        $connection->expects($this->once())
            ->method('getNamespace')
            ->will($this->returnValue(new Horde_Kolab_Storage_Driver_Namespace_Fixed()));
        $folder = new Horde_Kolab_Storage_Folder_Base('INBOX/Contacts');
        $folder->restore($storage, $connection);
        $folder->setName('TestAÖÜ');
        $this->assertEquals(Horde_String::convertCharset('INBOX/TestAÖÜ', 'UTF-8', 'UTF7-IMAP'), $folder->new_name);
    }

    /**
     * Test saving objects.
     */
    public function testSave()
    {
        $this->markTestIncomplete('Currently broken');
        $folder = $this->storage->getNewFolder();

        try {
            $result = $folder->save();
        } catch (Exception $e) {
            $this->assertEquals(Horde_Kolab_Storage_Exception::FOLDER_NAME_UNSET , $e->getCode());
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

        $folder2 = $this->storage->getNewFolder();
        $folder2->setName('TestEvents');
        $attributes = array(
            'type' => 'event',
            'default' => true,
        );
        $result = $folder2->save($attributes);
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
        $this->markTestIncomplete('Currently broken');
        $session = Horde_Kolab_Session::singleton('anonymous', null, true);
        $imap = $session->getImapParams();
        $this->assertEquals('localhost', $imap['hostspec']);
    }

    /**
     * Test triggering.
     */
    public function testTriggerOwn()
    {
        $this->markTestIncomplete('Currently broken');
        $folder = $this->getMock('Horde_Kolab_Storage_Folder', array('triggerUrl'));
        $folder->expects($this->once())
            ->method('triggerUrl')
            ->with($this->equalTo('https://fb.example.org/freebusy/trigger/wrobel@example.org/Kalender.pfb'));

        $connection = $this->storage->getConnection();
        $folder->restore($this->storage, $connection->connection);
        $folder->setName('Kalender');
        $folder->save(array('type' => 'event'));
    }

    /**
     * Test triggering.
     */
    public function testTriggerForeign()
    {
        $this->markTestIncomplete('Currently broken');
        $folder = $this->getMock('Horde_Kolab_Storage_Folder', array('triggerUrl'));
        $folder->expects($this->exactly(2))
            ->method('triggerUrl')
            ->with($this->equalTo('https://fb.example.org/freebusy/trigger/test@example.org/Kalender.pfb'));

        $connection = $this->storage->getConnection();
        $folder->restore($this->storage, $connection->connection);
        $folder->setName('user/test/Kalender');
        $folder->save(array('type' => 'event'));
    }
}
