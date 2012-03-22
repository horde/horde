<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Base.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Vfs_Sql_Pdo_PgsqlTest extends Horde_Vfs_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('pgsql', PDO::getAvailableDrivers())) {
            self::$reason = 'No pgsql extension or no pgsql PDO driver';
            return;
        }
        $config = self::getConfig('VFS_SQL_PDO_PGSQL_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['vfs']['sql']['pdo_pgsql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Pgsql($config['vfs']['sql']['pdo_pgsql']);
            parent::setUpBeforeClass();
        }
    }
}
