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
class Horde_SessionHandler_Storage_Sql_MysqliTest extends Horde_SessionHandler_Storage_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('SESSIONHANDLER_SQL_MYSQLI_TEST_CONFIG',
                                  dirname(__FILE__) . '/../..');
        if (!$config || empty($config['sessionhandler']['sql']['mysqli'])) {
            self::$reason = 'No mysqli configuration';
            return;
        }
        self::$db = new Horde_Db_Adapter_Mysqli($config['sessionhandler']['sql']['mysqli']);
        parent::setUpBeforeClass();
    }
}
