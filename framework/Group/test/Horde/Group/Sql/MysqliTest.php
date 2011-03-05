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
class Horde_Group_Sql_MysqliTest extends Horde_Group_Test_Sql_Base
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
