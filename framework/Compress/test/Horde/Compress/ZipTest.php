<?php
/**
 * @category   Horde
 * @package    Compress
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Compress
 * @subpackage UnitTests
 */
class Horde_Compress_ZipTest extends Horde_Test_Case
{
    public $testdata;

    public function setup()
    {
        $this->testdata = str_repeat("0123456789ABCDE", 1000);
    }

    public function testZipCreateString()
    {
        $compress = Horde_Compress::factory('Zip');

        $zip_data = $compress->compress(array(array(
            'data' => $this->testdata,
            'name' => 'test.txt'
        )));

        // Better test needed
        $this->assertNotEmpty($zip_data);
    }

    public function testZipCreateStream()
    {
        $compress = Horde_Compress::factory('Zip');

        $fd = fopen('php://temp', 'r+');
        fwrite($fd, $this->testdata);

        $zip_data = $compress->compress(array(array(
            'data' => $fd,
            'name' => 'test.txt'
        )), array(
            'stream' => true
        ));

        // Better test needed
        $this->assertNotEmpty($zip_data);
    }
}
