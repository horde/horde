<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Content
 * @subpackage UnitTests
 * @copyright  2010 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Content_Sql_MysqliTest extends Content_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('GROUP_SQL_MYSQLI_TEST_CONFIG',
                                  dirname(__FILE__) . '/..');
        if ($config && !empty($config['group']['sql']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['group']['sql']['mysqli']);
            parent::setUpBeforeClass();
        }
    }
}
