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
class Horde_Compress_Fast_Driver_TestBase extends Horde_Test_Case
{
    protected $classname;

    private $compress_text = 'Foo Foo Foo Foo Foo Foo Foo Foo Foo Foo';
    private $ob;

    protected function setUp()
    {
        if (!call_user_func(array($this->classname, 'supported'))) {
            $this->markTestSkipped(
                sprintf('Driver %s is not available.', $this->classname)
            );
        }

        $this->ob = new Horde_Compress_Fast(array(
            'drivers' => array($this->classname)
        ));
    }

    public function testCompress()
    {
        $data = $this->ob->decompress(
            $this->ob->compress($this->compress_text)
        );

        $this->assertEquals(
            $this->compress_text,
            $data
        );
    }

    /**
     * @expectedException Horde_Compress_Fast_Exception
     */
    public function testBadCompress()
    {
        $this->ob->compress(array());
    }

    /**
     * @expectedException Horde_Compress_Fast_Exception
     */
    public function testBadDecompress()
    {
        $this->ob->decompress(new stdClass);
    }

}
