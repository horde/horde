<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Horde_SessionHandler
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_SessionHandler_Storage_MongoTest extends Horde_SessionHandler_Storage_Base
{
    protected static $reason;
    protected static $mongo;

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
        if (($config = self::getConfig('SESSIONHANDLER_MONGO_TEST_CONFIG', __DIR__ . '/..')) &&
            isset($config['sessionhandler']['mongo'])) {
            $factory = new Horde_Test_Factory_Mongo();
            self::$mongo = $factory->create(array(
                'config' => $config['sessionhandler']['mongo'],
                'dbname' => 'horde_sessionhandler_test'
            ));
        }
        var_dump($config);
        if (empty(self::$mongo)) {
            self::$reason = 'MongoDB not available.';
            return;
        }
        self::$handler = new Horde_SessionHandler_Storage_Mongo(array(
            'mongo_db' => self::$mongo
        ));
        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        if (!self::$handler) {
            $this->markTestSkipped(self::$reason);
        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$mongo) {
            self::$mongo->selectDB(null)->drop();
        }
        parent::tearDownAfterClass();
    }
}
