<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @copyright  2011 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Prefs_Unit_Storage_Sql_MysqlTest extends Horde_Prefs_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('PREFS_SQL_MYSQL_TEST_CONFIG',
                                  dirname(__FILE__) . '/../../..');
        if ($config && !empty($config['prefs']['sql']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['prefs']['sql']['mysql']);
            parent::setUpBeforeClass();
        }
    }
}
