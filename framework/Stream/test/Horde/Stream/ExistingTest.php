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
class Horde_Stream_ExistingTest extends Horde_Test_Case
{
    private $fd;

    public function setUp()
    {
        $this->fd = fopen(dirname(__FILE__) . '/fixtures/data.txt', 'r');
        $this->stream = new Horde_Stream_Existing(array('stream' => $this->fd));
    }

    public function testFgetToChar()
    {
        rewind($this->stream->stream);

        $this->assertEquals(
            'A',
            $this->stream->getToChar(' ')
        );
        $this->assertEquals(
            'B',
            $this->stream->getToChar(' ')
        );
        $this->assertEquals(
            '',
            $this->stream->getToChar(' ')
        );
    }

    public function testLength()
    {
        rewind($this->stream->stream);

        $this->assertEquals(
            3,
            $this->stream->length()
        );
        $this->assertEquals(
            'A',
            fgetc($this->stream->stream)
        );
    }

    public function testGetString()
    {
        rewind($this->stream->stream);

        $this->assertEquals(
            'A B',
            $this->stream->getString()
        );
        $this->assertEquals(
            'A B',
            $this->stream->getString(0)
        );

        rewind($this->stream->stream);

        $this->assertEquals(
            'A B',
            $this->stream->getString()
        );
        $this->assertEquals(
            'A B',
            $this->stream->getString(0)
        );

        fseek($this->stream->stream, 2, SEEK_SET);
        $this->assertEquals(
            'B',
            $this->stream->getString()
        );

        fseek($this->stream->stream, 2, SEEK_SET);
        $this->assertEquals(
            'A ',
            $this->stream->getString(0, -1)
        );

        fseek($this->stream->stream, 0, SEEK_END);
        $this->assertEquals(
            '',
            $this->stream->getString(null, -1)
        );
    }

    public function testPeek()
    {
        rewind($this->stream->stream);

        $this->assertEquals(
            'A',
            $this->stream->peek()
        );
        $this->assertEquals(
            'A',
            fgetc($this->stream->stream)
        );

        fseek($this->stream->stream, -1, SEEK_END);

        $this->assertEquals(
            'B',
            $this->stream->peek()
        );
        $this->assertEquals(
            'B',
            fgetc($this->stream->stream)
        );
    }

    public function testSearch()
    {
        rewind($this->stream->stream);

        $this->assertEquals(
            2,
            $this->stream->search('B')
        );
        $this->assertEquals(
            0,
            $this->stream->search('A')
        );
        $this->assertEquals(
            0,
            ftell($this->stream->stream)
        );

        $this->assertEquals(
            2,
            $this->stream->search('B', false, false)
        );
        $this->assertNull($this->stream->search('A', false, false));

        $this->assertEquals(
            0,
            $this->stream->search('A', true)
        );
        $this->assertEquals(
            2,
            ftell($this->stream->stream)
        );

        $this->assertEquals(
            0,
            $this->stream->search('A', true, false)
        );
        $this->assertEquals(
            0,
            ftell($this->stream->stream)
        );
    }

    public function testAddMethod()
    {
    }

    public function testStringRepresentation()
    {
        $this->assertEquals(
            'A B',
            strval($this->stream)
        );
    }

    public function testSerializing()
    {
        $stream2 = unserialize(serialize($this->stream));

        $this->assertEquals(
            'A B',
            strval($stream2)
        );
    }

    public function testClone()
    {
        $stream2 = clone $this->stream;

        fclose($this->stream->stream);

        $this->assertEquals(
            'A B',
            strval($stream2)
        );
    }

}
