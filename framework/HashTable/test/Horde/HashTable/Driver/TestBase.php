<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    HashTable
 * @subpackage UnitTests
 */

/**
 * Tests for the HashTable storage drivers.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    HashTable
 * @subpackage UnitTests
 */
abstract class Horde_HashTable_Driver_TestBase extends Horde_Test_Case
{
    protected static $_driver;

    protected static $_skip = false;

    public function setUp()
    {
        if (self::$_skip) {
            $this->markTestSkipped(self::$_skip);
        }
    }

    public function testSet()
    {
        $this->assertTrue(self::$_driver->set('foo', 1));

        /* This should immediately expire. */
        $this->assertTrue(self::$_driver->set('foo2', 1, array('expire' => -1)));
        $this->assertFalse(self::$_driver->set('foo3', 1, array('replace' => true)));
        $this->assertTrue(self::$_driver->set('foo3', 1));
        $this->assertTrue(self::$_driver->set('foo3', 2, array('replace' => true)));
        /* @todo BC: 'timeout' will work also for 1.x. */
        $this->assertTrue(self::$_driver->set('foo4', 1, array('timeout' => -1)));
    }

    /**
     * @depends testSet
     */
    public function testExists()
    {
        $this->assertTrue(self::$_driver->exists('foo'));
        $this->assertFalse(self::$_driver->exists('foo2'));
        $this->assertTrue(self::$_driver->exists('foo3'));
        $this->assertFalse(self::$_driver->exists('foo4'));
    }

    /**
     * @depends testSet
     * @depends testExists
     */
    public function testGet()
    {
        $this->assertEquals(
            1,
            self::$_driver->get('foo')
        );
        $this->assertFalse(self::$_driver->get('foo2'));
        $this->assertEquals(
            2,
            self::$_driver->get('foo3')
        );
        $this->assertFalse(self::$_driver->get('foo4'));
    }

    /**
     * @depends testExists
     * @depends testSet
     * @depends testGet
     */
    public function testDelete()
    {
        $this->assertTrue(self::$_driver->delete('foo'));
        $this->assertTrue(self::$_driver->delete('foo2'));
        $this->assertTrue(self::$_driver->delete('foo3'));
        $this->assertTrue(self::$_driver->delete('foo4'));
    }

}
