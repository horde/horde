<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */
class Horde_Alarm_Storage_Sql_Oci8Test extends Horde_Alarm_Storage_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('oci8')) {
            self::$reason = 'No oci8 extension';
            return;
        }
        $config = self::getConfig('ALARM_SQL_OCI8_TEST_CONFIG',
                                  __DIR__ . '/../..');
        if ($config && !empty($config['alarm']['sql']['oci8'])) {
            self::$db = new Horde_Db_Adapter_Oci8($config['alarm']['sql']['oci8']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No oci8 configuration';
        }
    }
}
