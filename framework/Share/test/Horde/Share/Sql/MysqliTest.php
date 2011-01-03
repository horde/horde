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
class Horde_Share_Sql_MysqliTest extends Horde_Share_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            return;
        }
        $config = self::getConfig('SHARE_SQL_MYSQLI_TEST_CONFIG');
        self::$db = new Horde_Db_Adapter_Mysqli($config['share']['sql']['mysqli']);
        parent::setUpBeforeClass();
    }
}
