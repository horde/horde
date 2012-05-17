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
class Horde_Stream_Filter_NullTest extends Horde_Test_Case
{
    public $fp;
    public $testdata;

    public function setup()
    {
        stream_filter_register('horde_null', 'Horde_Stream_Filter_Null');

        $this->testdata = "abcde\0fghij";
        $this->fp = fopen('php://temp', 'r+');
        fwrite($this->fp, $this->testdata);
    }

    public function testNull()
    {
        $params = new stdClass;
        $filter = stream_filter_prepend($this->fp, 'horde_null', STREAM_FILTER_READ, $params);
        rewind($this->fp);
        $this->assertEquals(
            'abcdefghij',
            stream_get_contents($this->fp)
        );
        stream_filter_remove($filter);
    }
}
