<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package    Compress
 * @subpackage UnitTests
 */

/**
 * Tests the ZIP compressor.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package    Compress
 * @subpackage UnitTests
 */
class Horde_Compress_ZipTest extends Horde_Test_Case
{
    protected $testdata;

    public function setup()
    {
        $this->testdata = str_repeat("0123456789ABCDE", 1000);
    }

    public function testZipCreateString()
    {
        $compress = Horde_Compress::factory('Zip');

        $zip_data = $compress->compress(array(array(
            'data' => $this->testdata,
            'name' => 'test.txt',
            'time' => 1000000000
        )));

        $this->assertNotEmpty($zip_data);

        return $zip_data;
    }

    /**
     * @depends testZipCreateString
     */
    public function testZipUnzipString($zip_data)
    {
        $this->_testZipUnzip($zip_data);
    }

    public function testZipCreateStream()
    {
        $compress = Horde_Compress::factory('Zip');

        $fd = fopen('php://temp', 'r+');
        fwrite($fd, $this->testdata);

        $zip_data = $compress->compress(array(array(
            'data' => $fd,
            'name' => 'test.txt',
            'time' => 1000000000
        )), array(
            'stream' => true
        ));

        $this->assertNotEmpty($zip_data);
        $this->assertInternalType('resource', $zip_data);

        return stream_get_contents($zip_data);
    }

    /**
     * @depends testZipCreateStream
     */
    public function testZipUnzipStream($zip_data)
    {
        $this->_testZipUnzip($zip_data);
    }

    protected function _testZipUnzip($zip_data)
    {
        $compress = Horde_Compress::factory('Zip');
        $list = $compress->decompress(
            $zip_data, array('action' => Horde_Compress_Zip::ZIP_LIST)
        );
        $this->assertEquals(
            array(array(
                'attr' => '-A---',
                'crc' => 'd72299ec',
                'csize' => 62,
                'date' => 1000000000,
                '_dataStart' => 38,
                'name' => 'test.txt',
                'method' => 'Deflated',
                '_method' => 8,
                'size' => 15000,
                'type' => 'binary',
            )),
            $list
        );

        $data = $compress->decompress(
            $zip_data,
            array(
                'action' => Horde_Compress_Zip::ZIP_DATA,
                'info' => $list,
                'key' => 0
            )
        );
        $this->assertEquals($this->testdata, $data);
    }
}
