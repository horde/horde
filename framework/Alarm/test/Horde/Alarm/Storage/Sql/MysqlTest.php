<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */
class Horde_Alarm_Storage_Sql_MysqlTest extends Horde_Alarm_Storage_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('ALARM_SQL_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['alarm']['sql']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['alarm']['sql']['mysql']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No mysql configuration';
        }
    }
}
