<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    History
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_History_Sql_Pdo_PgsqlTest extends Horde_History_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('pgsql', PDO::getAvailableDrivers())) {
            self::$reason = 'No pgsql extension or no pgsql PDO driver';
            return;
        }
        $config = self::getConfig('HISTORY_SQL_PDO_PGSQL_TEST_CONFIG', __DIR__ . '/../../');
        if ($config && !empty($config['history']['sql']['pdo_pgsql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Pgsql($config['history']['sql']['pdo_pgsql']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No pdo_pgsql configuration';
        }
    }
}
