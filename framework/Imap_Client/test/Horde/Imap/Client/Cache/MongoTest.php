<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Mongo cache driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
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
        if (extension_loaded('mongo') &&
            class_exists('Horde_Mongo_Client')) {
            $config = self::getConfig('IMAPCLIENT', __DIR__ . '/..');
            if (!is_null($config) && !empty($config['mongo'])) {
                try {
                    $this->_mongo = new Horde_Mongo_Client($config['mongo']);
                    $this->_mongo->selectDB($this->_dbname)->drop();
                } catch (Exception $e) {}
            }
        }

        if (!isset($this->_mongo)) {
            $this->markTestSkipped('MongoDB not available.');
        }

        return new Horde_Imap_Client_Cache_Backend_Mongo(array(
            'dbname' => $this->_dbname,
            'hostspec' => self::HOSTSPEC,
            'mongo_db' => $this->_mongo,
            'port' => self::PORT,
            'username' => self::USERNAME
        ));
    }

    public function tearDown()
    {
        if (isset($this->_mongo)) {
            $this->_mongo->selectDB($this->_dbname)->drop();
        }

        parent::tearDown();
    }

}
