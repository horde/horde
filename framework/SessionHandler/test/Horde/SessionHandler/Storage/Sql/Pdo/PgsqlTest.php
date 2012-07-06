<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    SessionHandler
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_SessionHandler_Storage_Sql_Pdo_PgsqlTest extends Horde_SessionHandler_Storage_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('pgsql', PDO::getAvailableDrivers())) {
            self::$reason = 'No pgsql extension or no pgsql PDO driver';
            return;
        }
        $config = self::getConfig('SESSIONHANDLER_SQL_PDO_PGSQL_TEST_CONFIG',
                                  dirname(__FILE__) . '/../../..');
        if ($config && !empty($config['sessionhandler']['sql']['pdo_pgsql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Pgsql($config['sessionhandler']['sql']['pdo_pgsql']);
            parent::setUpBeforeClass();
        }
    }
}
