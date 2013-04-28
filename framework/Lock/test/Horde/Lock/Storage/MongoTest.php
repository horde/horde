<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @package    Lock
 * @subpackage UnitTests
 */
class Horde_Lock_Storage_MongoTest extends Horde_Lock_Storage_TestBase
{
    private $_dbname = 'horde_lock_mongodbtest';
    private $_mongo;

    protected function _getBackend()
    {
        if (($config = self::getConfig('IMAPCLIENT', __DIR__ . '/..')) &&
            isset($config['mongo'])) {
            $factory = new Horde_Test_Factory_Mongo();
            $this->_mongo = $factory->create(array(
                'config' => $config['mongo'],
                'dbname' => $this->_dbname
            ));
        }

        if (empty($this->_mongo)) {
            $this->markTestSkipped('MongoDB not available.');
        }

        return new Horde_Lock_Mongo(array(
            'mongo_db' => $this->_mongo,
        ));
    }

    public function tearDown()
    {
        if (!empty($this->_mongo)) {
            $this->_mongo->selectDB(null)->drop();
        }

        parent::tearDown();
    }

}
