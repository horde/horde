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
        $this->assertInstanceOf(
            'Horde_Mime_Part', $this->_getMessage()->create()
        );
    }

    public function testEnvelopeName()
    {
        $this->assertEquals(
            'Kolab Groupware Data',
            $this->_getMessage()->create()->getName()
        );
    }

    public function testEnvelopeType()
    {
        $this->assertEquals(
            'multipart/mixed',
            $this->_getMessage()->create()->getType()
        );
    }

    public function testEnvelopeDescription()
    {
        $this->assertInstanceOf(
            'Horde_Mime_Part',
            $this->_getMessage()->create()->getPart('1')
        );
    }

    public function testEnvelopeDescriptionType()
    {
        $this->assertEquals(
            'text/plain',
            $this->_getMessage()->create()->getPart('1')->getType()
        );
    }

    public function testEnvelopeDescriptionName()
    {
        $this->assertEquals(
            'Kolab Groupware Information',
            $this->_getMessage()->create()->getPart('1')->getName()
        );
    }

    public function testEnvelopeDescriptionCharset()
    {
        $this->assertEquals(
            'utf-8',
            $this->_getMessage()->create()->getPart('1')->getCharset()
        );
    }

    public function testEnvelopeDescriptionDisposition()
    {
        $this->assertEquals(
            'inline',
            $this->_getMessage()->create()->getPart('1')->getDisposition()
        );
    }

    public function testEnvelopeDescriptionContent()
    {
        setlocale(LC_MESSAGES, 'C');
        $this->assertEquals(
            "This is a Kolab Groupware object. To view this object you will need an email\r
client that understands the Kolab Groupware format. For a list of such email\r
clients please visit http://www.kolab.org/content/kolab-clients",
            $this->_getMessage()->create()->getPart('1')->getContents()
        );
    }

    public function testEnvelopeHeaders()
    {
        $this->assertInstanceOf(
            'Horde_Mime_Headers',
            $this->_getMessage()->createEnvelopeHeaders('user')
        );
    }

    public function testEnvelopeHeaderEol()
    {
        $this->assertEquals(
            "\r\n",
            $this->_getMessage()->createEnvelopeHeaders('user')->getEol()
        );
    }

    public function testEnvelopeHeaderDate()
    {
        $this->assertNotEquals(
            '',
            $this->_getMessage()->createEnvelopeHeaders('user')->getValue('Date')
        );
    }

    public function testEnvelopeHeaderAgent()
    {
        $this->assertEquals(
            'Horde::Kolab::Storage v' . Horde_Kolab_Storage::VERSION,
            $this->_getMessage()->createEnvelopeHeaders('user')->getValue('User-Agent')
        );
    }

    public function testEnvelopeHeaderMimeVersion()
    {
        $this->assertEquals(
            '1.0',
            $this->_getMessage()->createEnvelopeHeaders('user')->getValue('MIME-Version')
        );
    }

    public function testEnvelopeHeaderSubject()
    {
        $message = $this->_getMessage();
        $this->content->expects($this->once())
            ->method('getUid')
            ->will($this->returnValue('UID'));
        $this->assertEquals(
            'UID', $message->createEnvelopeHeaders('user')->getValue('Subject')
        );
    }

    public function testEnvelopeHeaderFrom()
    {
        $this->assertEquals(
            'user',
            $this->_getMessage()->createEnvelopeHeaders('user')->getValue('From')
        );
    }

    public function testEnvelopeHeaderTo()
    {
        $this->assertEquals(
            'user',
            $this->_getMessage()->createEnvelopeHeaders('user')->getValue('To')
        );
    }

    public function testEnvelopeHeaderType()
    {
        $message = $this->_getMessage();
        $this->content->expects($this->once())
            ->method('getMimeType')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $this->assertEquals(
            'application/x-vnd.kolab.note',
            $message->createEnvelopeHeaders('user')->getValue('X-Kolab-Type')
        );
    }

    public function testKolabPart()
    {
        $this->assertEquals(
            'kolab.xml',
            $this->_getMessage()->create()->getPart('2')->getName()
        );
    }

    public function testCompleteMessage()
    {
        $message = $this->_getMessage();
        $this->content->expects($this->exactly(2))
            ->method('getMimeType')
            ->will($this->returnValue('application/x-vnd.kolab.note'));
        $stream = $message->create()->toString(
            array(
                'canonical' => true,
                'stream' => true,
                'headers' => $message->createEnvelopeHeaders('user@localhost')
            )
        );
        rewind($stream);
        $this->assertEquals(
            array(
                0 => 'multipart/mixed',
                1 => 'text/plain',
                2 => 'application/x-vnd.kolab.note'
            ),
            Horde_Mime_Part::parseMessage(stream_get_contents($stream))->contentTypeMap(true)
        );
    }

    private function _getMessage()
    {
        $this->content = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        return new Horde_Kolab_Storage_Data_Object_Message_New(
            $this->content
        );
    }
}