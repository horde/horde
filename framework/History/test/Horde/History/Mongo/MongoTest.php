<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    History
 * @subpackage UnitTests
 */

/**
 * MongoDB History tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    History
 * @subpackage UnitTests
 */
class Horde_History_Mongo_MongoTest extends Horde_History_TestBase
{
    private $_dbname = 'horde_history_mongodbtest';
    private $_mongo;

    public function setUp()
    {
        if (($config = self::getConfig('HISTORY_MONGO_TEST_CONFIG', __DIR__ . '/..')) &&
            isset($config['history']['mongo'])) {
            $factory = new Horde_Test_Factory_Mongo();
            $this->_mongo = $factory->create(array(
                'config' => $config['history']['mongo'],
                'dbname' => $this->_dbname
            ));
        }

        if (empty($this->_mongo)) {
            $this->markTestSkipped('MongoDB not available.');
        }

        self::$history = new Horde_History_Mongo('test', array(
            'mongo_db' => $this->_mongo
        ));
    }

    public function tearDown()
    {
        if (!empty($this->_mongo)) {
            $this->_mongo->selectDB(null)->drop();
        }
    }

}
