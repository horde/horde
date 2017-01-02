<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Stream
 * @subpackage UnitTests
 */

/**
 * Common testing code for Horde_Stream class implementations.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Stream
 * @subpackage UnitTests
 */
abstract class Horde_Stream_Stream_TestBase extends Horde_Test_Case
{
    abstract protected function _getOb();

    public function testPos()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $this->assertEquals(
            3,
            $stream->pos()
        );
    }

    public function testRewind()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $this->assertTrue($stream->rewind());
        $this->assertEquals(
            0,
            $stream->pos()
        );
    }

    public function testSeek()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $this->assertTrue($stream->seek(-2));
        $this->assertEquals(
            1,
            $stream->pos()
        );

        $this->assertTrue($stream->seek(1));
        $this->assertEquals(
            2,
            $stream->pos()
        );

        $this->assertTrue($stream->seek(1, false));
        $this->assertEquals(
            1,
            $stream->pos()
        );

        $this->assertTrue($stream->seek(-10));
        $this->assertEquals(
            0,
            $stream->pos()
        );

        $stream->seek(2);

        $this->assertTrue($stream->seek(-10, true));
        $this->assertEquals(
            0,
            $stream->pos()
        );

        $stream2 = $this->_getOb();
        $stream2->add('Aönön');
        $stream2->utf8_char = true;
        $stream2->rewind();

        $this->assertTrue($stream2->seek(2));
        $this->assertEquals(
            2,
            $stream2->pos()
        );

        $this->assertTrue($stream2->seek(2));
        $this->assertEquals(
            4,
            $stream2->pos()
        );

        $this->assertTrue($stream2->seek(2, false));
        $this->assertEquals(
            2,
            $stream2->pos()
        );

        $this->assertTrue($stream2->seek(2, false, true));
        $this->assertEquals(
            3,
            $stream2->pos()
        );

        $this->assertTrue($stream2->seek(2, true, true));
        $this->assertEquals(
            6,
            $stream2->pos()
        );

        $this->assertTrue($stream2->seek(-2, true, true));
        $this->assertEquals(
            3,
            $stream2->pos()
        );

        $this->assertTrue($stream2->seek(-10, true, true));
        $this->assertEquals(
            0,
            $stream2->pos()
        );
    }

    public function testEnd()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $stream->rewind();

        $this->assertTrue($stream->end());
        $this->assertEquals(
            3,
            $stream->pos()
        );
    }

    public function testEof()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $this->assertFalse($stream->eof());

        $stream->getChar();

        $this->assertTrue($stream->eof());
    }

    public function testGetToChar()
    {
        $stream = $this->_getOb();
        $stream->add('A B', true);

        $this->assertEquals(
            'A',
            $stream->getToChar(' ')
        );
        $this->assertEquals(
            'B',
            $stream->getToChar(' ')
        );
        $this->assertEquals(
            '',
            $stream->getToChar(' ')
        );

        $stream2 = $this->_getOb();
        $stream2->add('A  B  ', true);

        $this->assertEquals(
            'A',
            $stream2->getToChar(' ')
        );
        $this->assertEquals(
            'B',
            $stream2->getToChar(' ')
        );
        $this->assertEquals(
            '',
            $stream2->getToChar(' ')
        );

        $stream3 = $this->_getOb();
        $stream3->add("A\n\n\nB\n", true);

        $this->assertEquals(
            'A',
            $stream3->getToChar("\n")
        );
        $this->assertEquals(
            'B',
            $stream3->getToChar("\n")
        );
        $this->assertEquals(
            '',
            $stream3->getToChar("\n")
        );

        $stream3->rewind();

        $this->assertEquals(
            'A',
            $stream3->getToChar("\n", false)
        );
        $this->assertEquals(
            '',
            $stream3->getToChar("\n", false)
        );
        $this->assertEquals(
            '',
            $stream3->getToChar("\n", false)
        );
        $this->assertEquals(
            'B',
            $stream3->getToChar("\n", false)
        );
        $this->assertEquals(
            '',
            $stream3->getToChar("\n", false)
        );

        $long_string = str_repeat('A', 15000);
        $stream4 = $this->_getOb();
        $stream4->add($long_string . "B\n", true);

        $this->assertEquals(
            $long_string,
            $stream4->getToChar('B', false)
        );
        $stream4->rewind();
        $this->assertEquals(
            $long_string . "B",
            $stream4->getToChar("\n", false)
        );
        $stream4->rewind();
        $this->assertEquals(
            $long_string,
            $stream4->getToChar("B\n", false)
        );
    }

    public function testLength()
    {
        $stream = $this->_getOb();
        $stream->add('A B ');

        $this->assertEquals(
            4,
            $stream->length()
        );
        $this->assertFalse($stream->getChar());

        $stream->rewind();

        $this->assertEquals(
            4,
            $stream->length()
        );
        $this->assertEquals(
            'A',
            $stream->getChar()
        );
    }

    public function testGetString()
    {
        $stream = $this->_getOb();
        $stream->add('A B C');

        $this->assertEquals(
            '',
            $stream->getString()
        );
        $this->assertEquals(
            'A B C',
            $stream->getString(0)
        );

        $stream->rewind();

        $this->assertEquals(
            'A B C',
            $stream->getString()
        );
        $this->assertEquals(
            'A B C',
            $stream->getString(0)
        );

        $stream->seek(2, false);
        $this->assertEquals(
            'B C',
            $stream->getString()
        );

        $stream->seek(2, false);
        $this->assertEquals(
            'B',
            $stream->getString(null, -2)
        );

        $stream->end();
        $this->assertEquals(
            '',
            $stream->getString(null, -1)
        );
    }

    public function testPeek()
    {
        $stream = $this->_getOb();
        $stream->add('A B', true);

        $this->assertEquals(
            'A',
            $stream->peek()
        );
        $this->assertEquals(
            'A',
            $stream->getChar()
        );

        $stream->end(-1);

        $this->assertEquals(
            'B',
            $stream->peek()
        );
        $this->assertEquals(
            'B',
            $stream->getChar()
        );

        $stream->rewind();

        $this->assertEquals(
            'A ',
            $stream->peek(2)
        );
        $this->assertEquals(
            'A',
            $stream->getChar()
        );
    }

    public function testSearch()
    {
        $stream = $this->_getOb();
        $stream->add('0123456789', true);

        $this->assertEquals(
            5,
            $stream->search(5)
        );
        $this->assertEquals(
            8,
            $stream->search(8)
        );
        $this->assertEquals(
            3,
            $stream->search(3)
        );
        $this->assertEquals(
            0,
            $stream->pos()
        );

        $this->assertEquals(
            5,
            $stream->search(5, false, false)
        );
        $this->assertEquals(
            8,
            $stream->search(8, false, false)
        );
        $this->assertNull($stream->search(3, false, false));

        $this->assertEquals(
            3,
            $stream->search(3, true)
        );
        $this->assertEquals(
            8,
            $stream->pos()
        );

        $this->assertEquals(
            3,
            $stream->search(3, true, false)
        );
        $this->assertEquals(
            3,
            $stream->pos()
        );

        $stream->rewind();

        $this->assertEquals(
            3,
            $stream->search('34')
        );

        $this->assertNull($stream->search('35'));
    }

    public function testAddMethod()
    {
        $stream = $this->_getOb();
        $stream->add('foo');

        $this->assertEquals(
            3,
            $stream->length()
        );
        $this->assertEquals(
            'foo',
            $stream->getString(0)
        );

        $stream->rewind();

        $stream2 = $this->_getOb();
        $stream2->add($stream, true);

        $this->assertEquals(
            3,
            $stream2->length()
        );
        $this->assertEquals(
            'foo',
            $stream2->getString()
        );
        $this->assertEquals(
            'foo',
            $stream2->getString(0)
        );

        $stream->rewind();

        $stream3 = $this->_getOb();
        $stream3->add($stream);

        $this->assertEquals(
            3,
            $stream3->length()
        );
        $this->assertEquals(
            '',
            $stream3->getString()
        );
        $this->assertEquals(
            'foo',
            $stream3->getString(0)
        );
    }

    public function testStringRepresentation()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $this->assertEquals(
            '123',
            strval($stream)
        );
    }

    public function testSerializing()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $stream2 = unserialize(serialize($stream));

        $this->assertEquals(
            '123',
            strval($stream2)
        );
    }

    public function testClone()
    {
        $stream = $this->_getOb();
        $stream->add('123');

        $stream2 = clone $stream;

        $stream->close();

        $this->assertEquals(
            '123',
            strval($stream2)
        );
    }

    public function testEolDetection()
    {
        $stream = $this->_getOb();
        $stream->add("123\n456");

        $this->assertEquals(
            "\n",
            $stream->getEOL()
        );

        $stream = $this->_getOb();
        $stream->add("123\r\n456");

        $this->assertEquals(
            "\r\n",
            $stream->getEOL()
        );

        $stream = $this->_getOb();
        $stream->add("123456");

        $this->assertNull($stream->getEOL());

        $stream = $this->_getOb();
        $stream->add("\n123456\n");

        $this->assertEquals(
            "\n",
            $stream->getEOL()
        );
    }

    public function testUtf8Parsing()
    {
        $test = 'Aönön';

        $stream = $this->_getOb();
        $stream->add($test, true);

        $this->assertEquals(
            7,
            $stream->length()
        );

        $this->assertEquals(
            'A',
            $stream->getToChar('ö')
        );

        $stream = $this->_getOb();
        $stream->add($test, true);
        $stream->utf8_char = true;

        $this->assertEquals(
            5,
            $stream->length(true)
        );

        $this->assertEquals(
            'Aö',
            $stream->getToChar('n')
        );

        $stream->rewind();

        $this->assertEquals(
            'Aö',
            $stream->peek(2)
        );
        $this->assertEquals(
            'A',
            $stream->getChar()
        );

        $stream->rewind();

        $this->assertEquals(
            1,
            $stream->search('ön')
        );

        $stream->end();

        $this->assertEquals(
            4,
            $stream->search('ön', true)
        );
    }

    public function testParsingAnExistingStreamObject()
    {
        $stream = $this->_getOb();
        // 100000 byte stream.
        $stream->add(str_repeat('1234567890', 10000));
        $stream->rewind();

        $this->assertEquals(
            100000,
            $stream->length()
        );

        $stream2 = $this->_getOb();
        $stream2->add($stream);

        $this->assertEquals(
            100000,
            $stream2->length()
        );
    }

    public function testSubstring()
    {
        $stream = $this->_getOb();
        $stream->add('1234567890');
        $stream->rewind();

        $this->assertEquals(
            '123',
            $stream->substring(0, 3)
        );
        $this->assertEquals(
            '456',
            $stream->substring(0, 3)
        );
        $this->assertEquals(
            '7890',
            $stream->substring(0)
        );
        $this->assertEquals(
            '',
            $stream->substring(0, 3)
        );

        $stream->rewind();

        $this->assertEquals(
            '456',
            $stream->substring(3, 3)
        );
        $this->assertEquals(
            '',
            $stream->substring(10, 3)
        );

        $stream->rewind();

        $this->assertEquals(
            '123',
            $stream->substring(-3, 3)
        );
        $this->assertEquals(
            '123',
            $stream->substring(-3, 3)
        );

        $stream2 = $this->_getOb();
        $stream2->add('AönönAönön');
        $stream2->utf8_char = true;
        $stream2->rewind();

        $this->assertEquals(
            'Aö',
            $stream2->substring(0, 3)
        );

        $stream2->rewind();

        $this->assertEquals(
            'Aön',
            $stream2->substring(0, 3, true)
        );

        $stream2->rewind();

        $this->assertEquals(
            'AönönAönön',
            $stream2->substring(0, null, true)
        );

        $stream2->rewind();

        $this->assertEquals(
            'Aönön',
            $stream2->substring(5, null, true)
        );
    }

}
