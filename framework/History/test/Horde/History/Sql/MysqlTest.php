<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    History
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_History_Sql_MysqlTest extends Horde_History_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('HISTORY_SQL_MYSQL_TEST_CONFIG', __DIR__ . '/..');
        if ($config && !empty($config['history']['sql']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['history']['sql']['mysql']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No mysql configuration';
        }
    }

}
