<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pack
 * @subpackage UnitTests
 */

/**
 * Tests for the drivers.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pack
 * @subpackage UnitTests
 */
abstract class Horde_Pack_Driver_TestBase extends Horde_Test_Case
{
    static protected $pack;

    protected $drivername;

    static public function setUpBeforeClass()
    {
        self::$pack = new Horde_Pack();
    }

    protected function setUp()
    {
        if (!call_user_func(array($this->drivername, 'supported'))) {
            $this->markTestSkipped(
                sprintf('Driver %s is not available.', $this->drivername)
            );
        }
    }

    public function testBoolean()
    {
        $this->_runTest(true);
    }

    public function testBooleanWithCompression()
    {
        $this->_runTest(true, true);
    }

    public function testString()
    {
        $this->_runTest(str_repeat('foo', 1000));
    }

    public function testStringWithCompression()
    {
        $this->_runTest(str_repeat('foo', 1000), true);
    }

    public function testSimpleArray()
    {
        $this->_runTest(range(1, 1000));
    }

    public function testSimpleArrayWithCompression()
    {
        $this->_runTest(range(1, 1000));
    }

    public function testNestedArray()
    {
        $tmp = array(
            '1' => 'foo',
            'bar' => 'baz'
        );
        $this->_runTest(array_fill(0, 1, $tmp));
    }

    public function testNestedArrayWithCompression()
    {
        $tmp = array(
            '1' => 'foo',
            'bar' => 'baz'
        );
        $this->_runTest(array_fill(0, 1, $tmp), true);
    }

    public function testObject()
    {
        $ob = new stdClass;
        $ob->foo = 'bar';
        $ob->foo2 = array(1, 2, 3);
        $ob->foo3 = 4;
        $ob->foo4 = true;
    }

    public function testPhpObject()
    {
        /* Not all backends support. */
        $driver = new $this->drivername();
        if ($driver->phpob) {
            $this->_runTest(self::$pack);
        }
    }

    public function testPhpObjectWithCompression()
    {
        /* Not all backends support. */
        $driver = new $this->drivername();
        if ($driver->phpob) {
            $this->_runTest(self::$pack, true);
        }
    }

    /* Internal methods. */

    protected function _runTest($data, $compress = false)
    {
        $packed = self::$pack->pack(
            $data,
            array(
                'compress' => $compress ? 0 : false,
                'drivers' => array(
                    $this->drivername
                )
            )
        );

        $this->assertNotEquals(
            $packed,
            $data
        );

        $unpacked = self::$pack->unpack($packed);

        $this->assertEquals(
            $data,
            $unpacked
        );
    }

}
