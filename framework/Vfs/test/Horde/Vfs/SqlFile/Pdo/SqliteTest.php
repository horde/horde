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
class Horde_Vfs_SqlFile_Pdo_SqliteTest extends Horde_Vfs_Test_SqlFile_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('sqlite', PDO::getAvailableDrivers())) {
            self::$reason = 'No sqlite extension or no sqlite PDO driver';
            return;
        }
        self::$db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:', 'charset' => 'utf-8'));
        parent::setUpBeforeClass();
    }
}
