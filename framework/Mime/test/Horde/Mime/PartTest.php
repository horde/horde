<?php
/**
 * Tests for the Horde_Mime_Part class.
 *
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

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
        $this->assertEquals(1777, strlen($part->toString()));

        // Message with a single part.
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg3.txt');

        $this->assertEquals(
            "\r\nTest.\r\n",
            Horde_Mime_Part::getRawPartText($msg, 'body', '1')
        );
    }

    public function testParsingMultipartAlternativeDoesNotProduceAttachment()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/samplemultipart_msg.txt');
        $part = Horde_Mime_Part::parseMessage($msg);
        $part->isBasePart(true);
        $msg = $part->toString(array('headers' => true));
        $test_part = Horde_Mime_Part::parseMessage($msg);
        $map = array(
            'multipart/alternative',
            'text/plain',
            'text/html');
        $this->assertEquals($map, $test_part->contentTypeMap());

        $part_one = $test_part->getPart(1);
        $this->assertEquals('', $test_part->getPart(1)->getDisposition());
    }

    public function testAddingSizeToContentDisposition()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContents('123');
        $part->setBytes(3);

        $this->assertEquals(
            "Content-Type: text/plain\r\n" .
            "Content-Disposition: attachment; size=3\r\n" .
            "\r\n" .
            '123',
            $part->toString(array(
                'canonical' => true,
                'headers' => true
            ))
        );
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
            "A\r\nB=C4=81\r\nC",
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

    // Deeply nested creation is OK
    public function testDeeplyNestedPartCreation()
    {
        // Part #1
        $base_part = $part = new Horde_Mime_Part();
        $part->setType('multipart/mixed');

        // Part #2-101
        for ($i = 0; $i < 100; ++$i) {
            $new_part = new Horde_Mime_Part();
            $new_part->setType('multipart/mixed');
            $part->addPart($new_part);
            $part = $new_part;
        }

        // Part #102
        $new_part = new Horde_Mime_Part();
        $new_part->setType('text/plain');
        $new_part->setContents('Test');
        $part->addPart($new_part);

        $base_part->isBasePart(true);
        $base_part->buildMimeIds();

        $this->assertEquals(
            102,
            count($base_part->contentTypeMap())
        );
    }

    // Deeply nested parsing is limited
    public function testDeeplyNestedPartParsing()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/deeply_nested.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertEquals(
            100,  // Actual levels: 102
            count($part->contentTypeMap())
        );
    }

    public function testBug12161()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg2.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertEquals(
            '=_k4kgcwkwggwc',
            $part->getContentTypeParameter('boundary')
        );

        $part_1 = $part->getPart(1);
        $this->assertEquals(
            'text/plain',
            $part_1->getType()
        );
    }

    public function testBug12536()
    {
        // This is a broken MIME message - it is a multipart with no subparts
        $msg = file_get_contents(__DIR__ . '/fixtures/bug12536.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertTrue(isset($part['0']));
        $this->assertFalse(isset($part['1']));
    }

    public function testBug12842()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/bug12842_a.txt') .
            str_replace("\n", "\r\n", file_get_contents(__DIR__ . '/fixtures/bug12842_b.txt'));
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertTrue(isset($part['1']));
        $this->assertTrue(isset($part['2']));
    }

    public function testBug13117()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/bug13117.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertTrue(isset($part['2']));
        $this->assertTrue(isset($part['2.0']));
        $this->assertTrue(isset($part['2.1']));
        $this->assertFalse(isset($part['2.2']));

        $multipart = $part['2.0'];
        $multipart->addPart(new Horde_Mime_Part());
        $multipart->buildMimeIds('2.0');

        $this->assertTrue(isset($part['2']));
        $this->assertTrue(isset($part['2.0']));
        $this->assertTrue(isset($part['2.1']));
        $this->assertTrue(isset($part['2.2']));
    }

    public function testSettingBytes()
    {
        $part = new Horde_Mime_Part();
        $part->setBytes(10);
        $part->setTransferEncoding('base64');

        $this->assertEquals(
            10,
            $part->getBytes()
        );
        $this->assertEquals(
            7,
            $part->getBytes(true)
        );

        $part2 = new Horde_Mime_Part();
        $part2->setBytes(10);
        $part2->setTransferEncoding('base64');
        $part2->setContents('TestTes', array('encoding' => '7bit'));

        $this->assertEquals(
            7,
            $part2->getBytes()
        );
        $this->assertEquals(
            7,
            $part2->getBytes(true)
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
