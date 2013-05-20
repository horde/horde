<?php
/**
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/gpl GPLv2
 * @category Horde
 * @package Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_StateTest_Sql_MysqlTest extends Horde_ActiveSync_StateTest_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension.';
            return;
        }
        $config = self::getConfig('ACTIVESYNC_SQL_MYSQL_TEST_CONFIG', dirname(__FILE__) . '/../..');
        if ($config && !empty($config['activesync']['sql']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['activesync']['sql']['mysql']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No mysql configuration';
        }
    }

}