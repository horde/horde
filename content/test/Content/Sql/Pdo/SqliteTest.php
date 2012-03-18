<?php

require_once __DIR__ . '/../Base.php';

class Content_Sql_Pdo_SqliteTest extends Content_Test_Sql_Base
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