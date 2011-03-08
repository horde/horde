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
    public function setup()
    {
        stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, "A\r\nB\rC\nD\r\n\r\nE\r\rF\n\nG\r\n\n\r\nH\r\n\r\r\nI");
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
        stream_filter_remove($filter);
        fclose($this->fp);
    }
}
