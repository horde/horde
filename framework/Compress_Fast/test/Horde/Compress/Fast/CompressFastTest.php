<?php
/**
 * @category   Horde
 * @package    Compress_Fast
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Compress_Fast
 * @subpackage UnitTests
 */
class Horde_Compress_Fast_CompressFastTest extends Horde_Test_Case
{
    private $compress_text = 'Foo Foo Foo Foo Foo Foo Foo Foo Foo Foo';

    public function testConstructor()
    {
        $ob = new Horde_Compress_Fast();

        if (extension_loaded('horde_lz4')) {
            $this->assertEquals(
                'Horde_Compress_Fast_Lz4',
                $ob->driver
            );
        } elseif (extension_loaded('lzf')) {
            $this->assertEquals(
                'Horde_Compress_Fast_Lzf',
                $ob->driver
            );
        } else {
            $this->assertEquals(
                'Horde_Compress_Fast_Null',
                $ob->driver
            );
        }

        $ob = new Horde_Compress_Fast(array(
            'drivers' => array(
                'Horde_Compress_Fast_Null'
            )
        ));

        $this->assertEquals(
            'Horde_Compress_Fast_Null',
            $ob->driver
        );
    }

    public function testNullDriverCompress()
    {
        $ob = new Horde_Compress_Fast(array(
            'drivers' => array(
                'Horde_Compress_Fast_Null'
            )
        ));

        $this->assertEquals(
            $this->compress_text,
            $ob->compress($this->compress_text)
        );

        try {
            $ob->compress(array());
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Fast_Exception $e) {}
    }

    public function testNullDriverDecompress()
    {
        $ob = new Horde_Compress_Fast(array(
            'drivers' => array(
                'Horde_Compress_Fast_Null'
            )
        ));

        $this->assertEquals(
            $this->compress_text,
            $ob->decompress($this->compress_text)
        );

        try {
            $ob->decompress(new stdClass);
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Fast_Exception $e) {}
    }

    public function testLzfDriverCompress()
    {
        try {
            $ob = new Horde_Compress_Fast(array(
                'drivers' => array(
                    'Horde_Compress_Fast_Lzf'
                )
            ));
        } catch (Horde_Compress_Fast_Exception $e) {
            $this->markTestSkipped('LZF extension not available.');
        }

        $this->assertEquals(
            lzf_compress($this->compress_text),
            $ob->compress($this->compress_text)
        );

        try {
            $ob->compress(array());
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Fast_Exception $e) {}
    }

    public function testLzfDriverDecompress()
    {
        try {
            $ob = new Horde_Compress_Fast(array(
                'drivers' => array(
                    'Horde_Compress_Fast_Lzf'
                )
            ));
        } catch (Horde_Compress_Fast_Exception $e) {
            $this->markTestSkipped('LZF extension not available.');
        }

        $this->assertEquals(
            $this->compress_text,
            $ob->decompress(lzf_compress($this->compress_text))
        );

        try {
            $ob->decompress(new stdClass);
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Fast_Exception $e) {}
    }

    public function testLz4DriverCompress()
    {
        try {
            $ob = new Horde_Compress_Fast(array(
                'drivers' => array(
                    'Horde_Compress_Fast_Lz4'
                )
            ));
        } catch (Horde_Compress_Fast_Exception $e) {
            $this->markTestSkipped('Horde LZ4 extension not available.');
        }

        $this->assertEquals(
            horde_lz4_compress($this->compress_text),
            $ob->compress($this->compress_text)
        );

        try {
            $ob->compress(array());
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Fast_Exception $e) {}
    }

    public function testLz4DriverDecompress()
    {
        try {
            $ob = new Horde_Compress_Fast(array(
                'drivers' => array(
                    'Horde_Compress_Fast_Lz4'
                )
            ));
        } catch (Horde_Compress_Fast_Exception $e) {
            $this->markTestSkipped('Horde LZ4 extension not available.');
        }

        $this->assertEquals(
            $this->compress_text,
            $ob->decompress(horde_lz4_compress($this->compress_text))
        );

        try {
            $ob->decompress(new stdClass);
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Fast_Exception $e) {}
    }

    /**
     * @dataProvider providerTestStringInput
     */
    public function testStringInput($data, $success)
    {
        $ob = new Horde_Compress_Fast(array(
            'drivers' => array(
                'Horde_Compress_Fast_Null'
            )
        ));

        try {
            $ob->compress($data);
            if (!$success) {
                $this->fail('Expected exception.');
            }
        } catch (Horde_Compress_Fast_Exception $e) {
            if ($success) {
                $this->fail('Unexpected exception.');
            }
        }
    }

    public function providerTestStringInput()
    {
        // Format: data, expected success
        return array(
            array('a', true),
            array(0.1, true),
            array(1, true),
            array(true, true),
            array(null, true),
            array(array(), false),
            array(new stdClass, false),
            array(opendir(__DIR__), false)
        );
    }

}
