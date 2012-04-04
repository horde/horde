<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

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
        self::$handler->open(self::$dir, 'sessionname');
        $this->assertSame('', self::$handler->read('sessionid'));
        self::$handler->write('sessionid', 'sessiondata');
    }

    protected function _read()
    {
        $this->assertEquals('sessiondata', self::$handler->read('sessionid'));
    }

    protected function _reopen()
    {
        self::$handler->close();
        self::$handler->open(self::$dir, 'sessionname');
        $this->assertEquals('sessiondata', self::$handler->read('sessionid'));
        self::$handler->close();
    }

    protected function _list()
    {
        self::$handler->close();
        self::$handler->open(self::$dir, 'sessionname');
        self::$handler->read('sessionid2');
        self::$handler->write('sessionid2', 'sessiondata2');
        /* List while session is active. */
        $ids = self::$handler->getSessionIDs();
        sort($ids);
        $this->assertEquals(array('sessionid', 'sessionid2'), $ids);
        self::$handler->close();

        /* List while session is inactive. */
        self::$handler->open(self::$dir, 'sessionname');
        $ids = self::$handler->getSessionIDs();
        sort($ids);
        $this->assertEquals(array('sessionid', 'sessionid2'), $ids);
        self::$handler->close();
    }

    protected function _destroy()
    {
        self::$handler->open(self::$dir, 'sessionname');
        self::$handler->read('sessionid2');
        self::$handler->destroy('sessionid2');
        $this->assertEquals(array('sessionid'),
                            self::$handler->getSessionIDs());
    }

    protected function _gc()
    {
        self::$handler->open(self::$dir, 'sessionname');
        self::$handler->gc(-1);
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
