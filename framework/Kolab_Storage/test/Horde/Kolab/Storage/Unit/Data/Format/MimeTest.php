<?php
/**
 * Test the MIME based format parsing.
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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the MIME based format parsing.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Data_Format_MimeTest
extends Horde_Kolab_Storage_TestCase
{
    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidData()
    {
        $this->_getMime()->parse('a', '1', null, array());
    }

    public function testMatchId()
    {
        $this->assertEquals(
            '2',
            $this->_getMime()->matchMimeId(
                'event',
                $this->_getStructure()->contentTypeMap()
            )
        );
    }

    public function testEvent()
    {
        $mime = $this->_getMime();

        $event = fopen(
            dirname(__FILE__) . '/../../../fixtures/event.xml.qp',
            'r'
        );
        $this->parser->expects($this->once())
            ->method('fetchId')
            ->with('a', '1', '2')
            ->will($this->returnValue($event));
        $object = $mime->parse(
            'a',
            '1',
            $this->_getStructure(),
            array('type' => 'event', 'version' => '1')
        );
        $this->assertEquals('libkcal-543769073.139', $object['uid']);
    }

    public function testMimeEnvelope()
    {
        $this->assertInstanceOf(
            'Horde_Mime_Part', $this->_getMime()->createEnvelope()
        );
    }

    public function testEnvelopeName()
    {
        $this->assertEquals(
            'Kolab Groupware Data',
            $this->_getMime()->createEnvelope()->getName()
        );
    }

    public function testEnvelopeType()
    {
        $this->assertEquals(
            'multipart/mixed',
            $this->_getMime()->createEnvelope()->getType()
        );
    }

    public function testEnvelopeDescription()
    {
        $this->assertInstanceOf(
            'Horde_Mime_Part',
            $this->_getMime()->createEnvelope()->getPart('1')
        );
    }

    public function testEnvelopeDescriptionType()
    {
        $this->assertEquals(
            'text/plain',
            $this->_getMime()->createEnvelope()->getPart('1')->getType()
        );
    }

    public function testEnvelopeDescriptionName()
    {
        $this->assertEquals(
            'Kolab Groupware Information',
            $this->_getMime()->createEnvelope()->getPart('1')->getName()
        );
    }

    public function testEnvelopeDescriptionCharset()
    {
        $this->assertEquals(
            'utf-8',
            $this->_getMime()->createEnvelope()->getPart('1')->getCharset()
        );
    }

    public function testEnvelopeDescriptionDisposition()
    {
        $this->assertEquals(
            'inline',
            $this->_getMime()->createEnvelope()->getPart('1')->getDisposition()
        );
    }

    public function testEnvelopeDescriptionContent()
    {
        $this->assertEquals(
            "This is a Kolab Groupware object. To view this object you will need an email\r
client that understands the Kolab Groupware format. For a list of such email\r
clients please visit http://www.kolab.org/kolab2-clients.html",
            $this->_getMime()->createEnvelope()->getPart('1')->getContents()
        );
    }

    public function testEnvelopeHeaders()
    {
        $this->assertInstanceOf(
            'Horde_Mime_Headers',
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')
        );
    }

    public function testEnvelopeHeaderEol()
    {
        $this->assertEquals(
            "\r\n",
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getEol()
        );
    }

    public function testEnvelopeHeaderDate()
    {
        $this->assertNotEquals(
            '',
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getValue('Date')
        );
    }

    public function testEnvelopeHeaderAgent()
    {
        $this->assertEquals(
            'Horde::Kolab::Storage v' . Horde_Kolab_Storage::VERSION,
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getValue('User-Agent')
        );
    }

    public function testEnvelopeHeaderMimeVersion()
    {
        $this->assertEquals(
            '1.0',
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getValue('MIME-Version')
        );
    }

    public function testEnvelopeHeaderSubject()
    {
        $this->assertEquals(
            'UID',
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getValue('Subject')
        );
    }

    public function testEnvelopeHeaderFrom()
    {
        $this->assertEquals(
            'user',
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getValue('From')
        );
    }

    public function testEnvelopeHeaderTo()
    {
        $this->assertEquals(
            'user',
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getValue('To')
        );
    }

    public function testEnvelopeHeaderType()
    {
        $this->assertEquals(
            'application/x-vnd.kolab.note',
            $this->_getMime()->createEnvelopeHeaders('UID', 'user', 'note')->getValue('X-Kolab-Type')
        );
    }

    public function testKolabType()
    {
        $this->assertEquals(
            'application/x-vnd.kolab.note',
            $this->_getMime()->createKolabPart(
                array('uid' => 'A', 'desc' => 'SUMMARY'),
                array('type' => 'note', 'version' => '1')
            )->getType()
        );
    }

    public function testKolabCharset()
    {
        $this->assertEquals(
            'utf-8',
            $this->_getMime()->createKolabPart(
                array('uid' => 'A', 'desc' => 'SUMMARY'),
                array('type' => 'note', 'version' => '1')
            )->getCharset()
        );
    }

    public function testKolabDisposition()
    {
        $this->assertEquals(
            'inline',
            $this->_getMime()->createKolabPart(
                array('uid' => 'A', 'desc' => 'SUMMARY'),
                array('type' => 'note', 'version' => '1')
            )->getDisposition()
        );
    }

    public function testKolabName()
    {
        $this->assertEquals(
            'kolab.xml',
            $this->_getMime()->createKolabPart(
                array('uid' => 'A', 'desc' => 'SUMMARY'),
                array('type' => 'note', 'version' => '1')
            )->getName()
        );
    }

    public function testKolabContent()
    {
        $this->assertContains(
            '<uid>A</uid>',
            $this->_getMime()->createKolabPart(
                array('uid' => 'A', 'desc' => 'SUMMARY'),
                array('type' => 'note', 'version' => '1')
            )->getContents()
        );
    }

    private function _getMime()
    {
        $this->parser = $this->getMock('Horde_Kolab_Storage_Data_Parser_Structure', array('fetchId'), array(), '', false, false);
        return new Horde_Kolab_Storage_Data_Format_Mime(
            new Horde_Kolab_Storage_Factory(), $this->parser
        );
    }

    private function _getStructure()
    {
        $fixture = dirname(__FILE__) . '/../../../fixtures/event.struct';
        return unserialize(base64_decode(file_get_contents($fixture)));
    }
}
