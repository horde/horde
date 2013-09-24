<?php
/**
 * @category   Horde
 * @package    Stream
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Stream
 * @subpackage UnitTests
 */
class Horde_Stream_TempTest extends Horde_Test_Case
{
    public function testFgetToChar()
    {
        $stream = new Horde_Stream_Temp();
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

        $stream2 = new Horde_Stream_Temp();
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
    }

    public function testLength()
    {
        $stream = new Horde_Stream_Temp();
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
        $stream = new Horde_Stream_Temp();
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
        $stream = new Horde_Stream_Temp();
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
    }

    public function testSearch()
    {
        $stream = new Horde_Stream_Temp();
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
    }

    public function testAddMethod()
    {
        $stream = new Horde_Stream_Temp();
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

        $stream2 = new Horde_Stream_Temp();
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

        $stream3 = new Horde_Stream_Temp();
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
        $stream = new Horde_Stream_Temp();
        $stream->add('123');

        $this->assertEquals(
            '123',
            strval($stream)
        );
    }

    public function testSerializing()
    {
        $stream = new Horde_Stream_Temp();
        $stream->add('123');

        $stream2 = unserialize(serialize($stream));

        $this->assertEquals(
            '123',
            strval($stream2)
        );
    }

    public function testClone()
    {
        $stream = new Horde_Stream_Temp();
        $stream->add('123');

        $stream2 = clone $stream;

        unset($stream);

        $this->assertEquals(
            '123',
            strval($stream2)
        );
    }

    public function testEolDetection()
    {
        $stream = new Horde_Stream_Temp();
        $stream->add("123\n456");

        $this->assertEquals(
            "\n",
            $stream->getEOL()
        );

        $stream = new Horde_Stream_Temp();
        $stream->add("123\r\n456");

        $this->assertEquals(
            "\r\n",
            $stream->getEOL()
        );

        $stream = new Horde_Stream_Temp();
        $stream->add("123456");

        $this->assertNull($stream->getEOL());

        $stream = new Horde_Stream_Temp();
        $stream->add("\n123456\n");

        $this->assertEquals(
            "\n",
            $stream->getEOL()
        );
    }

    public function testUtf8Parsing()
    {
        $test = 'Aönön';

        $stream = new Horde_Stream_Temp();
        $stream->add($test, true);

        $i = 0;
        while ($stream->getChar() !== false) {
            ++$i;
        }

        $this->assertEquals(
            7,
            $i
        );

        $stream->rewind();

        $this->assertEquals(
            $test,
            $stream->getToChar('ö')
        );

        $stream = new Horde_Stream_Temp();
        $stream->add($test, true);
        $stream->utf8_char = true;

        $i = 0;
        while ($stream->getChar() !== false) {
            ++$i;
        }

        $this->assertEquals(
            5,
            $i
        );

        $stream->rewind();

        $this->assertEquals(
            'Aö',
            $stream->getToChar('n')
        );
    }

}
