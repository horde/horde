<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Vfs_Sql_Oci8Test extends Horde_Vfs_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('oci8')) {
            self::$reason = 'No oci8 extension';
            return;
        }
        $config = self::getConfig('VFS_SQL_OCI8_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['vfs']['sql']['oci8'])) {
            self::$db = new Horde_Db_Adapter_Oci8($config['vfs']['sql']['oci8']);
            //self::$db->setLogger(new Horde_Log_Logger(new Horde_Log_Handler_Cli()));
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No oci8 configuration';
        }
    }

    public function testWriteLargeData()
    {
        // Write twice to test both INSERT and UPDATE.
        self::$vfs->writeData('', 'large', str_repeat('x', 4001));
        self::$vfs->writeData('', 'large', str_repeat('x', 4001));
        self::$vfs->deleteFile('', 'large');
    }
}
