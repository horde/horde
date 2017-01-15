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
class Horde_History_Sql_MysqliTest extends Horde_History_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('HISTORY_SQL_MYSQLI_TEST_CONFIG', __DIR__ . '/..');
        if ($config && !empty($config['history']['sql']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['history']['sql']['mysqli']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No mysqli configuration';
        }
    }
}
