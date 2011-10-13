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
 * @copyright  2010 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Share_Sqlng_MysqliTest extends Horde_Share_Test_Sqlng_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('mysqli')) {
            return;
        }
        $config = self::getConfig('SHARE_SQL_MYSQLI_TEST_CONFIG',
                                  dirname(__FILE__) . '/..');
        if ($config && !empty($config['share']['sql']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['share']['sql']['mysqli']);
            parent::setUpBeforeClass();
        }
    }
}
