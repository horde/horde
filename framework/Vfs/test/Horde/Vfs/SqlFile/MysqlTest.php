<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Vfs_SqlFile_MysqlTest extends Horde_Vfs_Test_SqlFile_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('VFS_SQLFILE_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['vfs']['sqlfile']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['vfs']['sqlfile']['mysql']);
            parent::setUpBeforeClass();
        }
    }
}
