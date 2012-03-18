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
class Horde_Vfs_SqlFile_MysqliTest extends Horde_Vfs_Test_SqlFile_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('VFS_SQLFILE_MYSQLI_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['vfs']['sqlfile']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['vfs']['sqlfile']['mysqli']);
            parent::setUpBeforeClass();
        }
    }
}
