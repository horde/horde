<?php
/**
 * Tests the Kolab mime message generator.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Tests the Kolab mime message generator.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Data_Object_Message_NewTest
extends PHPUnit_Framework_TestCase
{
    public function testMimeEnvelope()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('MIME-Version: 1.0', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeName()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('Content-Disposition: attachment; filename="Kolab Groupware Data"', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeType()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('Content-Type: multipart/mixed;', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeDescriptionType()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('Content-Type: text/plain; name="Kolab Groupware Information"; charset=utf-8', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeDescriptionDisposition()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('Content-Disposition: inline; filename="Kolab Groupware Information"', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeDescriptionContent()
    {
        setlocale(LC_MESSAGES, 'C');
        $this->_getMessage()->store('INBOX');
        $this->assertContains(
            "This is a Kolab Groupware object. To view this object you will need an email\r
client that understands the Kolab Groupware format. For a list of such email\r
clients please visit http://www.kolab.org/content/kolab-clients",
            $this->driver->messages['INBOX'][0]
        );
    }

    public function testEnvelopeHeaderAgent()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('User-Agent: Horde::Kolab::Storage v' . Horde_Kolab_Storage::VERSION, $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeHeaderSubject()
    {
        $message = $this->_getMessage();
        $this->content->expects($this->once())
            ->method('getUid')
            ->will($this->returnValue('UID'));
        $message->store('INBOX');
        $this->assertContains('Subject: UID', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeHeaderFrom()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('From: user', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeHeaderTo()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('To: user', $this->driver->messages['INBOX'][0]);
    }

    public function testEnvelopeHeaderType()
    {
        $message = $this->_getMessage();
        $this->content->expects($this->exactly(2))
            ->method('getMimeType')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $message->store('INBOX');
        $this->assertContains('X-Kolab-Type: application/x-vnd.kolab.note', $this->driver->messages['INBOX'][0]);
    }

    public function testKolabPart()
    {
        $this->_getMessage()->store('INBOX');
        $this->assertContains('Content-Type: application/octet-stream; name=kolab.xml', $this->driver->messages['INBOX'][0]);
    }

    public function testCompleteMessage()
    {
        $message = $this->_getMessage();
        $this->content->expects($this->exactly(2))
            ->method('getMimeType')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $message->store('INBOX');
        $this->assertEquals(
            array(
                0 => 'multipart/mixed',
                1 => 'text/plain',
                2 => 'application/x-vnd.kolab.note'
            ),
            Horde_Mime_Part::parseMessage($this->driver->messages['INBOX'][0])->contentTypeMap(true)
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testDriverException()
    {
        $content = $this->getMock('Horde_Kolab_Storage_Data_Object_Addable');
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('appendMessage')
            ->will($this->returnValue(false));
        $message = new Horde_Kolab_Storage_Data_Object_Message_New($content, $driver);
        $message->store('INBOX');
    }

    private function _getMessage()
    {
        $this->content = $this->getMock('Horde_Kolab_Storage_Data_Object_Addable');
        $this->driver = new Horde_Kolab_Storage_Stub_Driver('user');
        return new Horde_Kolab_Storage_Data_Object_Message_New(
            $this->content, $this->driver
        );
    }
}