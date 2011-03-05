<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Group_Sql_MysqlTest extends Horde_Group_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('GROUP_SQL_MYSQL_TEST_CONFIG',
                                  dirname(__FILE__) . '/..');
        if ($config && !empty($config['group']['sql']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['group']['sql']['mysql']);
            parent::setUpBeforeClass();
        }
    }
}
