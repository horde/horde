<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Share_SqlHierarchical_MysqlTest extends Horde_Share_Test_SqlHierarchical_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysql')) {
            return;
        }
        $config = self::getConfig('SHARE_SQL_MYSQL_TEST_CONFIG');
        self::$db = new Horde_Db_Adapter_Mysql($config['share']['sql']['mysql']);
        parent::setUpBeforeClass();
    }
}
