<?php
/**
 * Tests for the Horde_Mime_Part class.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_PartTest extends PHPUnit_Framework_TestCase
{
    public function testParseMessage()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertEquals(
            'multipart/mixed',
            $part->getType()
        );
        $this->assertEquals(
            1434,
            $part->getBytes()
        );
        $this->assertEquals(
            '=_k4kgcwkwggwc',
            $part->getContentTypeParameter('boundary')
        );

        $part_1 = $part->getPart(1);
        $this->assertEquals(
            'text/plain',
            $part_1->getType()
        );
        $this->assertEquals(
            'flowed',
            $part_1->getContentTypeParameter('format')
        );

        $part_2 = $part->getPart(2);
        $this->assertEquals(
            'message/rfc822',
            $part_2->getType()
        );
        $part_2_2 = $part->getPart('2.2');
        $this->assertEquals(
            'text/plain',
            $part_2_2->getType()
        );
        $this->assertEquals(
            'test.txt',
            $part_2_2->getDispositionParameter('filename')
        );

        $part_3 = $part->getPart(3);
        $this->assertEquals(
            'image/png',
            $part_3->getType()
        );

        $this->assertEquals(
            "Test text.\r\n\r\n",
            Horde_Mime_Part::getRawPartText($msg, 'body', '2.1')
        );

        $this->assertEquals(
            "Content-Type: image/png; name=index.png\r\n" .
            "Content-Disposition: attachment; filename=index.png\r\n" .
            'Content-Transfer-Encoding: base64',
            Horde_Mime_Part::getRawPartText($msg, 'header', '3')
        );

        // Test the length of the resulting MIME string to ensure
        // the incoming multipart data was not output twice.
        $this->assertEquals(1795, strlen($part->toString()));
    }

    public function testArrayAccessImplementation()
    {
        $part = $this->_getTestPart();

        $this->assertEquals(
            true,
            isset($part['1'])
        );
        $this->assertEquals(
            false,
            isset($part['4'])
        );

        $this->assertSame(
            $part->getPart('1'),
            $part['1']
        );
        $this->assertSame(
            $part->getPart('3.1'),
            $part['3.1']
        );

        $part2 = new Horde_Mime_Part();
        $part2->setType('text/plain');

        $part['2'] = $part2;
        $this->assertSame(
            $part2,
            $part->getPart('2')
        );

        unset($part['3']);
        $this->assertEquals(
            null,
            $part->getPart('3')
        );
    }

    public function testCountableImplementation()
    {
        $part = $this->_getTestPart();

        $this->assertEquals(
            3,
            count($part)
        );
    }

    public function testAlterPart()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $map = $part->contentTypeMap();
        $this->assertEquals(
            'message/rfc822',
            $map['2']
        );

        $part2 = new Horde_Mime_Part();
        $part2->setType('text/plain');
        $part2->setContents('foo');

        $part->alterPart('2', $part2);

        $map = $part->contentTypeMap();
        $this->assertEquals(
            'text/plain',
            $map['2']
        );
    }

    public function testUnserialize()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContents('Test');

        $part1 = unserialize(serialize($part));

        $this->assertEquals(
            'Test',
            $part1->getContents()
        );

        $this->assertInternalType(
            'resource',
            $part1->getContents(array('stream' => true))
        );

        $this->assertEquals(
            'Test',
            $part->getContents()
        );
    }

    // Bug #10324
    public function testQuotedPrintableNewlines()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContents("A\r\nBā\r\nC");

        $this->assertEquals(
            "A=0D\nB=C4=81=0D\nC",
            $part->toString()
        );

        $part->setEOL("\r\n");

        $this->assertEquals(
            "A\r\nB=C4=81\r\nC",
            $part->toString()
        );

        $part2 = new Horde_Mime_Part();
        $part2->setType('multipart/mixed');
        $part2->addPart($part);

        $this->assertStringMatchesFormat(
            "This message is in MIME format.

--=_%s
Content-Type: text/plain
Content-Transfer-Encoding: quoted-printable

A
B=C4=81
C
--=_%s--",
            $part2->toString()
        );

        $part->setEOL("\n");

        $this->assertStringMatchesFormat(
            "This message is in MIME format.

--=_%s
Content-Type: text/plain
Content-Transfer-Encoding: quoted-printable

A=0D
B=C4=81=0D
C
--=_%s--",
            $part2->toString()
        );
    }

    public function testFindBody()
    {
        $part = $this->_getTestPart();
        $part31 = $part->getPart('3.1');
        $part31->setType('text/html');

        $this->assertEquals(
            '1',
            $part->findBody()
        );

        $this->assertEquals(
            '3.1',
            $part->findBody('html')
        );

        // Bug #10458
        $part31->setDisposition('attachment');
        $this->assertNull(
            $part->findBody('html')
        );
    }

    protected function _getTestPart()
    {
        $part = new Horde_Mime_Part();
        $part->setType('multipart/mixed');

        $part1 = new Horde_Mime_Part();
        $part1->setType('text/plain');
        $part1->setContents('Test');
        $part->addPart($part1);

        $part2 = new Horde_Mime_Part();
        $part2->setType('application/octet-stream');
        $part->addPart($part2);

        $part3 = new Horde_Mime_Part();
        $part3->setType('multipart/mixed');
        $part->addPart($part3);

        $part3_1 = new Horde_Mime_Part();
        $part3_1->setType('text/plain');
        $part3_1->setContents('Test 2');
        $part3->addPart($part3_1);

        $part->buildMimeIds();

        return $part;
    }

}
