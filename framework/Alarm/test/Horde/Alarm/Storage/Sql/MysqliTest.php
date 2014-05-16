<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */
class Horde_Alarm_Storage_Sql_MysqliTest extends Horde_Alarm_Storage_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('ALARM_SQL_MYSQLI_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['alarm']['sql']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['alarm']['sql']['mysqli']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No mysqli configuration';
        }
    }
}
