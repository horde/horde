<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * Tests for the Db cache driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @ignore
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
class Horde_Imap_Client_Cache_DbTest extends Horde_Imap_Client_Cache_CacheTest
{
    private function _getBackend()
    {
        $factory_db = new Horde_Test_Factory_Db();

        try {
            $db = $factory_db->create(array(
                'migrations' => array(
                    'migrationsPath' => __DIR__ . '/../../../../../migration/Horde/Imap/Client'
                )
            ));
        } catch (Horde_Test_Exception $e) {
            $this->markTestSkipped('Sqlite not available.');
        }

        return new Horde_Imap_Client_Cache_Backend_Db(array(
            'db' => $db,
            'hostspec' => self::HOSTSPEC,
            'port' => self::PORT,
            'username' => self::USERNAME
        ));
    }

}
