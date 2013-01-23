<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Prefs_Unit_Storage_Sql_MysqlTest extends Horde_Prefs_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('PREFS_SQL_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/../../..');
        if ($config && !empty($config['prefs']['sql']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['prefs']['sql']['mysql']);
            parent::setUpBeforeClass();
        }
    }
}
