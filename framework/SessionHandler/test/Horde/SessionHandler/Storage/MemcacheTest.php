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
class Horde_SessionHandler_Storage_MemcacheTest extends Horde_SessionHandler_Storage_Base
{
    protected static $reason;

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
     * @depends testWrite
     */
    public function testList()
    {
        $this->_list();
    }

    /**
     * @depends testList
     */
    public function testDestroy()
    {
        $this->_destroy();
    }

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('memcache')) {
            self::$reason = 'No memcache extension';
            return;
        }
        $config = self::getConfig('SESSIONHANDLER_MEMCACHE_TEST_CONFIG',
                                  dirname(__FILE__) . '/..');
        if (!$config || empty($config['sessionhandler']['memcache'])) {
            self::$reason = 'No memcache configuration';
            return;
        }
        $memcache = new Horde_Memcache($config);
        $memcache->delete('sessionid');
        $memcache->delete('sessionid2');
        self::$handler = new Horde_SessionHandler_Storage_Memcache(
            array('memcache' => $memcache, 'track' => true));
        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        if (!self::$handler) {
            $this->markTestSkipped(self::$reason);
        }
    }
}
