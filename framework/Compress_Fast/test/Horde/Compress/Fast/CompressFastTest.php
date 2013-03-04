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

        $this->assertEquals(
            (extension_loaded('lzf') ? 'Horde_Compress_Fast_Lzf' : 'Horde_Compress_Fast_Null'),
            $ob->driver
        );

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

}
