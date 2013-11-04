<?php
/**
 * @category   Horde
 * @package    Stream_Filter
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Stream_Filter
 * @subpackage UnitTests
 */
class Horde_Stream_Filter_EolTest extends Horde_Test_Case
{
    public $fp;

    public function setup()
    {
        stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, "A\r\nB\rC\nD\r\n\r\nE\r\rF\n\nG\r\n\n\r\nH\r\n\r\r\nI");
    }

    public function tearDown()
    {
        fclose($this->fp);
    }

    public static function lineEndingProvider()
    {
        return array(
            array("\r", "ABCDEFGHI"),
            array("\n", "A\nB\nC\nD\n\nE\n\nF\n\nG\n\n\nH\n\n\nI"),
            array("\r\n", "A\nB\nC\nD\n\nE\n\nF\n\nG\n\n\nH\n\n\nI"),
            array("", "ABCDEFGHI"),
        );
    }

    /**
     * @dataProvider lineEndingProvider
     */
    public function testFilterLineEndings($eol, $expected)
    {
        $filter = stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, array('eol' => $eol));
        rewind($this->fp);
        $this->assertEquals($expected, stream_get_contents($this->fp));
    }

    public function testBug12673()
    {
        $test = str_repeat(str_repeat("A", 1) . "\r\n", 4000);

        rewind($this->fp);
        fwrite($this->fp, $test);

        $filter = stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, array('eol' => "\r\n"));
        rewind($this->fp);

        $this->assertEquals($test, stream_get_contents($this->fp));

        $test = str_repeat(str_repeat("A", 14) . "\r\n", 2);

        rewind($this->fp);
        ftruncate($this->fp, 0);
        fwrite($this->fp, $test);

        stream_filter_prepend($this->fp, 'horde_eol', STREAM_FILTER_READ, array('eol' => "\r\n"));
        rewind($this->fp);

        $this->assertEquals(
            $test,
            fread($this->fp, 14)
                . fread($this->fp, 1)
                . fread($this->fp, 1)
                . fread($this->fp, 14)
                . fread($this->fp, 2)
                . fread($this->fp, 100)
        );
    }

}
