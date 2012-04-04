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
class Horde_SessionHandler_Storage_ExternalTest extends Horde_SessionHandler_Storage_Base
{
    public function testWrite()
    {
        $this->_write();
    }

    /**
     * @depends testWrite
     */
    public function testRead()
    {
        $this->_read();
    }

    /**
     * @depends testWrite
     */
    public function testReopen()
    {
        $this->_reopen();
    }

    /**
     * The external driver doesn't support listing, so test for existing
     * sessions manually.
     *
     * @depends testWrite
     */
    public function testList()
    {
        self::$handler->close();
        self::$handler->open(self::$dir, 'sessionname');
        self::$handler->read('sessionid2');
        self::$handler->write('sessionid2', 'sessiondata2');
        /* List while session is active. */
        $this->assertNotEmpty(self::$handler->read('sessionid'));
        $this->assertNotEmpty(self::$handler->read('sessionid2'));
        self::$handler->close();

        /* List while session is inactive. */
        self::$handler->open(self::$dir, 'sessionname');
        $this->assertNotEmpty(self::$handler->read('sessionid'));
        $this->assertNotEmpty(self::$handler->read('sessionid2'));
        self::$handler->close();
    }

    /**
     * @depends testList
     */
    public function testDestroy()
    {
        self::$handler->open(self::$dir, 'sessionname');
        self::$handler->read('sessionid2');
        self::$handler->destroy('sessionid2');
        $this->assertSame('', self::$handler->read('sessionid2'));
    }

    /**
     * @depends testDestroy
     */
    public function testGc()
    {
        self::$handler->open(self::$dir, 'sessionname');
        self::$handler->gc(-1);
        $this->assertSame('', self::$handler->read('sessionid'));
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $external = new Horde_SessionHandler_Storage_File(array('path' => self::$dir));
        self::$handler = new Horde_SessionHandler_Storage_External(
            array('open' => array($external, 'open'),
                  'close' => array($external, 'close'),
                  'read' => array($external, 'read'),
                  'write' => array($external, 'write'),
                  'destroy' => array($external, 'destroy'),
                  'gc' => array($external, 'gc')));
    }
}
