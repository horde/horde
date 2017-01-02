<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Part class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @internal
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

        $part_1 = $part[1];
        $this->assertEquals(
            'text/plain',
            $part_1->getType()
        );
        $this->assertEquals(
            'flowed',
            $part_1->getContentTypeParameter('format')
        );

        $part_2 = $part[2];
        $this->assertEquals(
            'message/rfc822',
            $part_2->getType()
        );
        $part_2_2 = $part['2.2'];
        $this->assertEquals(
            'text/plain',
            $part_2_2->getType()
        );
        $this->assertEquals(
            'test.txt',
            $part_2_2->getDispositionParameter('filename')
        );

        $part_3 = $part[3];
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

        $this->assertEquals(
            '',
            $test_part[1]->getDisposition()
        );
    }

    public function testParsingMimeMessageWithEaiAddress()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai_2.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        /* If we reach this point, there was no Exception. */
        $this->assertEquals(
            "asdf",
            trim($part->getContents())
        );
    }

    public function testParsingMimeMessageWithUtf8ContentDispositionParameter()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertEquals(
            'blåbærsyltetøy',
            $part->getDispositionParameter('filename')
        );
    }

    public function testParsingMultipartMimeMessageWithMultipleEaiComponents()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai_3.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertEquals(
            2,
            count($part->getParts())
        );

        $part1 = $part[1];
        $this->assertEquals(
            'text/plain',
            $part1->getType()
        );
        $this->assertEquals(
            'flowed',
            $part1->getContentTypeParameter('format')
        );
        $this->assertEquals(
            'abstürzen',
            $part1->getContentTypeParameter('x-eai-please-do-not')
        );
        $this->assertNull($part1->getContentTypeParameter('filename'));

        $part1 = $part[2];
        $this->assertEquals(
            'text/plain',
            $part1->getType()
        );
        $this->assertEquals(
            'blåbærsyltetøy',
            $part1->getDispositionParameter('filename')
        );
        $this->assertNull($part1->getContentTypeParameter('filename'));
    }

    public function testAddingSizeToContentDisposition()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContents('123');
        $part->setBytes(3);
        $part->setDisposition('attachment');

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
            'text/plain',
            $part['1']->getType()
        );
        $this->assertSame(
            'text/plain',
            $part['3.1']->getType()
        );

        $part2 = new Horde_Mime_Part();
        $part2->setType('text/plain');

        $part['2'] = $part2;
        $this->assertSame(
            $part2,
            $part['2']
        );

        unset($part['3']);
        $this->assertEquals(
            null,
            $part['3']
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

    /**
     * @dataProvider contentsTransferDecodingProvider
     */
    public function testContentsTransferDecoding($data, $encoding, $text)
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');

        $part->setContents($data, array('encoding' => $encoding));

        $this->assertEquals(
            $text,
            $part->getContents()
        );
    }

    public function contentsTransferDecodingProvider()
    {
        return array(
            array(
                'xIE=',
                'base64',
                'ā'
            ),
            array(
                '=C4=81',
                'quoted-printable',
                'ā'
            ),
            array(
                'ā',
                '8bit',
                'ā'
            )
        );
    }

    /**
     * @dataProvider setTypeProvider
     */
    public function testSetType($data, $type, $boundary)
    {
        $part = new Horde_Mime_Part();

        $part->setType($data);

        $this->assertEquals(
            $type,
            $part->getType()
        );

        $b = $part->getContentTypeParameter('boundary');
        if ($boundary) {
            $this->assertNotEmpty($b);
        } else {
            $this->assertEmpty($b);
        }
    }

    public function setTypeProvider()
    {
        return array(
            array(
                'text/plain',
                'text/plain',
                false
            ),
            array(
                'multipart/mixed',
                'multipart/mixed',
                true
            ),
            array(
                'foo/bar',
                'x-foo/bar',
                false
            )
        );
    }

    public function testAppendContents()
    {
        $part = new Horde_Mime_Part();
        $part->appendContents('1');

        $this->assertEquals(
            '1',
            $part->getContents()
        );

        $part->appendContents('2');
        $this->assertEquals(
            '12',
            $part->getContents()
        );

        $tmp = fopen('php://temp', 'r+');
        fwrite($tmp, '3');
        rewind($tmp);
        $part->appendContents($tmp);
        $this->assertEquals(
            '123',
            $part->getContents()
        );

        $tmp = fopen('php://temp', 'r+');
        fwrite($tmp, '5');
        rewind($tmp);
        $part->appendContents(array('4', $tmp, '6'));
        $this->assertEquals(
            '123456',
            $part->getContents()
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

        $part['2'] = $part2;

        $map = $part->contentTypeMap();
        $this->assertEquals(
            'text/plain',
            $map['2']
        );
    }

    /**
     * @dataProvider setDispositionProvider
     */
    public function testSetDisposition($disp)
    {
        $part = new Horde_Mime_Part();
        $part->setDisposition($disp);

        $this->assertEquals(
            $disp,
            $part->getDisposition()
        );
    }

    public function setDispositionProvider()
    {
        return array(
            array('attachment'),
            array('inline'),
            array('')
        );
    }

    public function testUnserialize()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContentTypeParameter('foo', 'bar');
        $part->setContents('Test');

        $part1 = unserialize(serialize($part));

        $this->assertEquals(
            'Test',
            $part1->getContents()
        );

        $this->assertEquals(
            array('foo' => 'bar'),
            $part1->getAllContentTypeParameters()
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

    public function testClone()
    {
        $part = new Horde_Mime_Part();
        $part->setType('multipart/mixed');
        $part->setContentTypeParameter('x-foo', 'foo');

        $part2 = new Horde_Mime_Part();
        $part2->setType('text/plain');
        $part2->setContents('Foo');

        $part[] = $part2;
        $part->buildMimeIds();

        $part3 = clone $part;

        $part->setContentTypeParameter('x-foo', 'bar');
        $part2->setContents('Bar');

        $this->assertEquals(
            'foo',
            $part3->getContentTypeParameter('x-foo')
        );
        $this->assertEquals(
            'Foo',
            $part3[1]->getContents()
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
        $part2[] = $part;

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
        $part31 = $part['3.1'];
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
            $part[] = $new_part;
            $part = $new_part;
        }

        // Part #102
        $new_part = new Horde_Mime_Part();
        $new_part->setType('text/plain');
        $new_part->setContents('Test');
        $part[] = $new_part;

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

        $this->assertEquals(
            'text/plain',
            $part['1']->getType()
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
        $multipart[] = new Horde_Mime_Part();
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

    public function testMimeMessageWithNoContentType()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg4.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertEquals(
            'text/plain',
            $part->getType()
        );

        $this->assertEquals(
            'us-ascii',
            $part->getCharset()
        );
    }

    public function testAccessingMimePartsInRawText()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/samplemultipart_msg.txt');

        $this->assertNotEmpty(
            Horde_Mime_Part::getRawPartText($msg, 'body', '0')
        );
        $this->assertNotEmpty(
            Horde_Mime_Part::getRawPartText($msg, 'body', '1')
        );
        $this->assertNotEmpty(
            Horde_Mime_Part::getRawPartText($msg, 'body', '2')
        );
    }

    /**
     * @dataProvider setCharsetProvider
     */
    public function testSetCharset($charset, $expected)
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setCharset($charset);

        $this->assertEquals(
            $expected,
            $part->getType(true)
        );
    }

    public function setCharsetProvider()
    {
        return array(
            array('utf-8', 'text/plain; charset=utf-8'),
            array('UtF-8', 'text/plain; charset=utf-8'),
            array('us-ascii', 'text/plain'),
            array('', 'text/plain')
        );
    }

    public function testIdSortingInMessageRfc822Part()
    {
        $part = new Horde_Mime_Part();
        $part->setType('message/rfc822');

        $part1 = new Horde_Mime_Part();
        $part1->setType('multipart/alternative');
        $part[] = $part1;

        $part2 = new Horde_Mime_Part();
        $part2->setType('text/plain');
        $part1[] = $part2;

        $part3 = new Horde_Mime_Part();
        $part3->setType('text/html');
        $part1[] = $part3;

        $part->buildMimeIds();

        $this->assertEquals(
            array('1.0', '1', '1.1', '1.2'),
            array_keys($part->contentTypeMap(true))
        );
    }

    public function testNoOverwriteOfPartContentsWithItsOwnStreamData()
    {
        $text = 'foo';

        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContents($text);

        $stream = $part->getContents(array('stream' => true));

        $part->setContents($stream);

        $this->assertEquals(
            $text,
            $part->getContents()
        );
    }

    public function testNullCharactersNotAllowedInMimeHeaderData()
    {
        $part = new Horde_Mime_Part();

        $part->setType("text/pl\0ain");
        $this->assertEquals(
            'text/plain',
            $part->getType()
        );

        $part->setDisposition("inl\0ine");
        $this->assertEquals(
            'inline',
            $part->getDisposition()
        );

        $part->setDispositionParameter('size', '123' . "\0" . '456');
        $this->assertEquals(
            123456,
            $part->getDispositionParameter('size')
        );

        $part->setDispositionParameter('foo', "foo\0bar");
        $this->assertEquals(
            'foobar',
            $part->getDispositionParameter('foo')
        );

        $part->setCharset("utf\0-8");
        $this->assertEquals(
            'utf-8',
            $part->getCharset()
        );

        $part->setName("foo\0bar");
        $this->assertEquals(
            'foobar',
            $part->getName()
        );
        $this->assertEquals(
            'foobar',
            $part->getDispositionParameter('filename')
        );
        $this->assertEquals(
            'foobar',
            $part->getContentTypeParameter('name')
        );

        $part->setLanguage("e\0n");
        $this->assertEquals(
            array('en'),
            $part->getLanguage()
        );

        $part->setLanguage(array("e\0n", "d\0e"));
        $this->assertEquals(
            array('en', 'de'),
            $part->getLanguage()
        );

        $part->setDuration('123' . "\0" . '456');
        $this->assertEquals(
            123456,
            $part->getDuration()
        );

        $part->setBytes('123' . "\0" . '456');
        $this->assertEquals(
            123456,
            $part->getBytes()
        );

        $part->setDescription("foo\0bar");
        $this->assertEquals(
            'foobar',
            $part->getDescription()
        );

        $part->setContentTypeParameter('foo', "foo\0bar");
        $this->assertEquals(
            'foobar',
            $part->getContentTypeParameter('foo')
        );

        $part->setContentId("foo\0bar");
        $this->assertEquals(
            'foobar',
            $part->getContentId()
        );
    }

    public function testSerializeUpgradeFromVersion1()
    {
        $data = base64_decode(
            file_get_contents(__DIR__ . '/fixtures/mime_part_v1.txt')
        );

        $part = unserialize($data);

        $this->assertEquals(
            'text/plain',
            $part->getType()
        );

        $this->assertEquals(
            array('en'),
            $part->getLanguage()
        );

        $this->assertEquals(
            'foo',
            $part->getDescription()
        );

        $this->assertEquals(
            'attachment',
            $part->getDisposition()
        );

        $this->assertEquals(
            'bar',
            $part->getDispositionParameter('foo')
        );

        $this->assertEquals(
            'foo',
            $part->getDispositionParameter('filename')
        );

        $this->assertEquals(
            'foo',
            $part->getContentTypeParameter('name')
        );

        $this->assertEquals(
            'bar',
            $part->getContentTypeParameter('foo')
        );

        $this->assertEquals(
            'us-ascii',
            $part->getCharset()
        );

        $this->assertEquals(
            array(),
            $part->getParts()
        );

        $this->assertEquals(
            '1',
            $part->getMimeId()
        );

        $this->assertEquals(
            "\n",
            $part->getEOL()
        );

        $this->assertEquals(
            'bar',
            $part->getMetadata('foo')
        );

        $this->assertEquals(
            'svl8CVtZEEO5bgqR-wFIFQ8@bigworm.curecanti.org',
            $part->getContentId()
        );

        $this->assertEquals(
            10,
            $part->getDuration()
        );

        $this->assertEquals(
            'foo',
            $part->getContents()
        );
    }

    public function testIteration()
    {
        $iterator = $this->_getTestPart()->partIterator();

        $ids = array(
            '0',
            '1',
            '2',
            '3',
            '3.1',
            '3.2',
            '3.2.1',
            '3.2.2'
        );
        reset($ids);

        foreach ($iterator as $val) {
            $this->assertEquals(
                current($ids),
                $val->getMimeId()
            );

            next($ids);
        }

        $this->assertFalse(current($ids));
    }

    public function testMultipartDigest()
    {
        $part = new Horde_Mime_Part();
        $part->setType('multipart/digest');
        $part->isBasePart(true);

        $part2 = new Horde_Mime_Part();
        $part2->setType('message/rfc822');
        $part2->setContents(
            file_get_contents(__DIR__ . '/fixtures/sample_msg4.txt')
        );
        $part[] = $part2;

        $this->assertStringMatchesFormat(
            "Content-Type: multipart/digest; boundary=\"=_%s\"
MIME-Version: 1.0

This message is in MIME format.

--=_%s

Message-ID: <asdl8ahwhoadsadl@example.com>
Date: Tue, 07 Jul 2013 10:21:48 -0600
From: \"Test Q. User\" <test@example.com>
To: foo@example.com
Subject: Test
MIME-Version: 1.0


Test.

--=_%s--",
            $part->toString(array('headers' => true))
        );
    }

    protected function _getTestPart()
    {
        $part = new Horde_Mime_Part();
        $part->setType('multipart/mixed');

        $part1 = new Horde_Mime_Part();
        $part1->setType('text/plain');
        $part1->setContents('Test');
        $part[] = $part1;

        $part2 = new Horde_Mime_Part();
        $part2->setType('application/octet-stream');
        $part[] = $part2;

        $part3 = new Horde_Mime_Part();
        $part3->setType('multipart/mixed');
        $part[] = $part3;

        $part3_1 = new Horde_Mime_Part();
        $part3_1->setType('text/plain');
        $part3_1->setContents('Test 2');
        $part3[] = $part3_1;

        $part3_2 = new Horde_Mime_Part();
        $part3_2->setType('multipart/mixed');
        $part3[] = $part3_2;

        $part3_2_1 = new Horde_Mime_Part();
        $part3_2_1->setType('text/plain');
        $part3_2_1->setContents('Test 3.2.1');
        $part3_2[] = $part3_2_1;

        $part3_2_2 = new Horde_Mime_Part();
        $part3_2_2->setType('application/octet-stream');
        $part3_2_2->setContents('Test 3.2.2');
        $part3_2[] = $part3_2_2;

        $part->buildMimeIds();

        return $part;
    }

    public function setUp()
    {
        Horde_Mime_Part::$defaultCharset =
            Horde_Mime_Headers::$defaultCharset = 'us-ascii';
    }
}
