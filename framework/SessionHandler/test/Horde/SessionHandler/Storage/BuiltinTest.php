<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Horde_SessionHandler
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_SessionHandler_Storage_BuiltinTest extends Horde_SessionHandler_Storage_Base
{
    public function testWrite()
    {
        session_name('sessionname');
        session_id('sessionid');
        @session_start();
        $this->assertEmpty($_SESSION);
        $_SESSION['sessiondata'] = 'foo';
        session_write_close();
    }

    /**
     * @depends testWrite
     */
    public function testRead()
    {
        $this->assertEquals('sessiondata|s:3:"foo";', self::$handler->read('sessionid'));
    }

    /**
     * @depends testWrite
     */
    public function testReopen()
    {
        session_write_close();
        session_name('sessionname');
        session_id('sessionid');
        @session_start();
        $this->assertEquals('foo', $_SESSION['sessiondata']);
        session_write_close();
    }

    /**
     * @depends testWrite
     */
    public function testList()
    {
        session_write_close();
        session_name('sessionname');
        session_id('sessionid2');
        @session_start();
        $_SESSION['sessiondata2'] = 'foo';
        /* List while session is active. */
        $ids = self::$handler->getSessionIDs();
        sort($ids);
        $this->assertEquals(array('sessionid', 'sessionid2'), $ids);
        session_write_close();

        /* List while session is inactive. */
        $ids = self::$handler->getSessionIDs();
        sort($ids);
        $this->assertEquals(array('sessionid', 'sessionid2'), $ids);
    }

    /**
     * @depends testList
     */
    public function testDestroy()
    {
        session_name('sessionname');
        session_id('sessionid2');
        @session_start();
        session_destroy();
        $this->assertEquals(array('sessionid'),
                            self::$handler->getSessionIDs());
    }

    /**
     * @depends testDestroy
     */
    public function testGc()
    {
        $probability = ini_get('session.gc_probability');
        $divisor     = ini_get('session.gc_divisor');
        $maxlifetime = ini_get('session.gc_maxlifetime');
        ini_set('session.gc_probability', 100);
        ini_set('session.gc_divisor', 1);
        ini_set('session.gc_maxlifetime', -1);
        session_name('sessionname');
        @session_start();
        $this->assertEquals(array(),
                            self::$handler->getSessionIDs());
        ini_set('session.gc_probability', $probability);
        ini_set('session.gc_divisor', $divisor);
        ini_set('session.gc_maxlifetime', $maxlifetime);
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        ini_set('session.save_path', self::$dir);
        self::$handler = new Horde_SessionHandler_Storage_Builtin(array('path' => self::$dir));
    }
}
