<?php
/**
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/gpl GPLv2
 * @category Horde
 * @package Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_StateTest_Sql_MysqliTest extends Horde_ActiveSync_StateTest_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('ACTIVESYNC_SQL_MYSQLI_TEST_CONFIG', dirname(__FILE__) . '/../..');;
        if ($config && !empty($config['activesync']['sql']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['activesync']['sql']['mysqli']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No mysqli configuration';
        }
    }

}