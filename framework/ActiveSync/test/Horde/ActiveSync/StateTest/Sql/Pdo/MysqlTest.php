<?php
require_once dirname(__FILE__) . '/../Base.php';
/**
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/gpl GPLv2
 * @category Horde
 * @package Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_StateTest_Sql_Pdo_MysqlTest extends Horde_ActiveSync_StateTest_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('mysql', PDO::getAvailableDrivers())) {
            self::$reason = 'No mysql extension or no mysql PDO driver';
            return;
        }
        $config = self::getConfig('ACTIVESYNC_SQL_PDO_MYSQL_TEST_CONFIG', dirname(__FILE__) . '/../../..');
        if ($config && !empty($config['activesync']['sql']['pdo_mysql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Mysql($config['activesync']['sql']['pdo_mysql']);
            parent::setUpBeforeClass();
        }
    }
}
