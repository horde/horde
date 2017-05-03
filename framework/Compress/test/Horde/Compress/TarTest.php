<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package    Compress
 * @subpackage UnitTests
 */

/**
 * Tests the TAR compressor.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package    Compress
 * @subpackage UnitTests
 */
class Horde_Compress_TarTest extends Horde_Test_Case
{
    protected $testdata;

    public function setup()
    {
        $this->testdata = str_repeat("0123456789ABCDE", 1000);
    }

    public function testTarCreateString()
    {
        $compress = Horde_Compress::factory('Tar');

        $tar_data = $compress->compress(array(array(
            'data' => $this->testdata,
            'name' => 'test.txt',
            'time' => 1000000000
        )));

        $this->assertNotEmpty($tar_data);

        return $tar_data;
    }

    /**
     * @depends testTarCreateString
     */
    public function testTarUntarString($tar_data)
    {
        $this->_testTarUntar($tar_data);
    }

    public function testTarCreateStream()
    {
        $compress = Horde_Compress::factory('Tar');

        $fd = fopen('php://temp', 'r+');
        fwrite($fd, $this->testdata);

        $tar_data = $compress->compress(array(array(
            'data' => $fd,
            'name' => 'test.txt',
            'time' => 1000000000
        )), array(
            'stream' => true
        ));

        $this->assertNotEmpty($tar_data);
        $this->assertInternalType('resource', $tar_data);

        return stream_get_contents($tar_data);
    }

    /**
     * @depends testTarCreateStream
     */
    public function testTarUntarStream($tar_data)
    {
        $this->_testTarUntar($tar_data);
    }

    protected function _testTarUntar($tar_data)
    {
        $compress = Horde_Compress::factory('Tar');
        $list = $compress->decompress($tar_data);
        $this->assertEquals(
            array(array(
                'attr' => '----------',
                'date' => 1000000000,
                'name' => 'test.txt',
                'size' => 15000,
                'type' => 'File',
                'data' => $this->testdata
            )),
            $list
        );
    }

    public function testTarDirectory()
    {
        $compress = Horde_Compress::factory('Tar');

        $tar_data = $compress->compressDirectory(
            __DIR__ . '/fixtures/directory'
        );

        $this->assertNotEmpty($tar_data);

        $list = $compress->decompress($tar_data);
        $this->assertCount(3, $list);
        for ($i=0 ; $i<3 ; $i++) {
            $list[$list[$i]['name']] = $list[$i];
            unset($list[$i]);
        }
        $this->assertArrayHasKey($k='one.txt', $list);
        $this->assertEquals(4, $list[$k]['size']);
        $this->assertEquals("One\n", $list[$k]['data']);

        $this->assertArrayHasKey($k='sub/three.txt', $list);
        $this->assertEquals(6, $list[$k]['size']);
        $this->assertEquals("Three\n", $list[$k]['data']);

        $this->assertArrayHasKey($k='two.bin', $list);
        $this->assertEquals(2, $list[$k]['size']);
        $this->assertEquals("\x02\x0a", $list[$k]['data']);
    }
}
