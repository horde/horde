<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */

/**
 * This is the base test class for all SQL backends.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_Sql_Base extends Horde_Cache_TestBase
{
    protected function _getCache($params = array())
    {
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Cli());
        //$this->db->setLogger($logger);
        $dir = __DIR__ . '/../../../../migration/Horde/Cache';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Cache/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        $this->migrator = new Horde_Db_Migration_Migrator(
            $this->db,
            null,//$logger,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_cache_schema_info'));
        $this->migrator->up();

        return new Horde_Cache(
            new Horde_Cache_Storage_File(array_merge(
                array('db'   => $this->db),
                $params
            ))
        );
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->db->delete('DELETE FROM horde_cache');
        if ($this->migrator) {
            $this->migrator->down();
        }
        if ($this->db) {
            $this->db->disconnect();
        }
        $this->db = $this->migrator = null;
    }
}
