<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Mongo cache driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Cache_MongoTest extends Horde_Imap_Client_Cache_TestBase
{
    private $_dbname = 'horde_imap_client_cache_mongodbtest';
    private $_mongo;

    protected function _getBackend()
    {
        if (($config = self::getConfig('IMAPCLIENT_TEST_CONFIG', __DIR__ . '/..')) &&
            isset($config['imapclient']['mongo'])) {
            $factory = new Horde_Test_Factory_Mongo();
            $this->_mongo = $factory->create(array(
                'config' => $config['imapclient']['mongo'],
                'dbname' => $this->_dbname
            ));
        }

        if (empty($this->_mongo)) {
            $this->markTestSkipped('MongoDB not available.');
        }

        return new Horde_Imap_Client_Cache_Backend_Mongo(array(
            'hostspec' => self::HOSTSPEC,
            'mongo_db' => $this->_mongo,
            'port' => self::PORT,
            'username' => self::USERNAME
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
