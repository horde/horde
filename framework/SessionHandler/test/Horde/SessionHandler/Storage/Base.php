<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Horde_SessionHandler
 * @subpackage UnitTests
 */
class Horde_SessionHandler_Storage_Base extends Horde_Test_Case
{
    protected static $handler;
    protected static $dir;

    protected function _write()
    {
        $this->assertTrue(self::$handler->open(self::$dir, 'sessionname'));
        $this->assertSame('', self::$handler->read('sessionid'));
        $this->assertTrue(self::$handler->write('sessionid', 'sessiondata'));
    }

    protected function _read()
    {
        $this->assertEquals('sessiondata', self::$handler->read('sessionid'));
    }

    protected function _reopen()
    {
        $this->assertTrue(self::$handler->close());
        $this->assertTrue(self::$handler->open(self::$dir, 'sessionname'));
        $this->assertEquals('sessiondata', self::$handler->read('sessionid'));
        $this->assertTrue(self::$handler->close());
    }

    protected function _list()
    {
        $this->assertTrue(self::$handler->close());
        $this->assertTrue(self::$handler->open(self::$dir, 'sessionname'));
        self::$handler->read('sessionid2');
        $this->assertTrue(self::$handler->write('sessionid2', 'sessiondata2'));
        /* List while session is active. */
        $ids = self::$handler->getSessionIDs();
        sort($ids);
        $this->assertEquals(array('sessionid', 'sessionid2'), $ids);
        $this->assertTrue(self::$handler->close());

        /* List while session is inactive. */
        $this->assertTrue(self::$handler->open(self::$dir, 'sessionname'));
        $ids = self::$handler->getSessionIDs();
        sort($ids);
        $this->assertEquals(array('sessionid', 'sessionid2'), $ids);
        $this->assertTrue(self::$handler->close());
    }

    protected function _destroy()
    {
        $this->assertTrue(self::$handler->open(self::$dir, 'sessionname'));
        self::$handler->read('sessionid2');
        $this->assertTrue(self::$handler->destroy('sessionid2'));
        $this->assertEquals(array('sessionid'),
                            self::$handler->getSessionIDs());
    }

    protected function _gc()
    {
        $this->assertTrue(self::$handler->open(self::$dir, 'sessionname'));
        $this->assertTrue(self::$handler->gc(-1));
        $this->assertEquals(array(),
                            self::$handler->getSessionIDs());
    }

    public static function setUpBeforeClass()
    {
        self::$dir = Horde_Util::createTempDir();
    }

    public static function tearDownAfterClass()
    {
        self::$handler = null;
    }
}
