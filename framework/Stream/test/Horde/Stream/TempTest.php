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
        fwrite($stream->stream, 'A B');
        rewind($stream->stream);

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
        fwrite($stream2->stream, 'A  B  ');
        rewind($stream2->stream);

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
        fwrite($stream->stream, 'A B ');

        $this->assertEquals(
            4,
            $stream->length()
        );
        $this->assertFalse(fgetc($stream->stream));

        rewind($stream->stream);

        $this->assertEquals(
            4,
            $stream->length()
        );
        $this->assertEquals(
            'A',
            fgetc($stream->stream)
        );
    }

    public function testGetString()
    {
        $stream = new Horde_Stream_Temp();
        fwrite($stream->stream, 'A B C');

        $this->assertEquals(
            '',
            $stream->getString()
        );
        $this->assertEquals(
            'A B C',
            $stream->getString(0)
        );

        rewind($stream->stream);

        $this->assertEquals(
            'A B C',
            $stream->getString()
        );
        $this->assertEquals(
            'A B C',
            $stream->getString(0)
        );

        fseek($stream->stream, 2, SEEK_SET);
        $this->assertEquals(
            'B C',
            $stream->getString()
        );

        fseek($stream->stream, 2, SEEK_SET);
        $this->assertEquals(
            'B',
            $stream->getString(null, -2)
        );

        fseek($stream->stream, 0, SEEK_END);
        $this->assertEquals(
            '',
            $stream->getString(null, -1)
        );
    }

    public function testPeek()
    {
        $stream = new Horde_Stream_Temp();
        fwrite($stream->stream, 'A B');
        rewind($stream->stream);

        $this->assertEquals(
            'A',
            $stream->peek()
        );
        $this->assertEquals(
            'A',
            fgetc($stream->stream)
        );

        fseek($stream->stream, -1, SEEK_END);

        $this->assertEquals(
            'B',
            $stream->peek()
        );
        $this->assertEquals(
            'B',
            fgetc($stream->stream)
        );
    }

    public function testSearch()
    {
        $stream = new Horde_Stream_Temp();
        fwrite($stream->stream, '0123456789');
        rewind($stream->stream);

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
            ftell($stream->stream)
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
            ftell($stream->stream)
        );

        $this->assertEquals(
            3,
            $stream->search(3, true, false)
        );
        $this->assertEquals(
            3,
            ftell($stream->stream)
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

        fseek($stream->stream, 0);

        $stream2 = new Horde_Stream_Temp();
        $stream2->add($stream->stream, true);

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

        fseek($stream->stream, 0);

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

}
