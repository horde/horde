<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    SessionHandler
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_SessionHandler_Storage_Sql_MysqlTest extends Horde_SessionHandler_Storage_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('SESSIONHANDLER_SQL_MYSQL_TEST_CONFIG',
                                  dirname(__FILE__) . '/../..');
        if (!$config || empty($config['sessionhandler']['sql']['mysql'])) {
            self::$reason = 'No mysql configuration';
            return;
        }
        self::$db = new Horde_Db_Adapter_Mysql($config['sessionhandler']['sql']['mysql']);
        parent::setUpBeforeClass();
    }
}
