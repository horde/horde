<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Share
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Share_Sql_Pdo_PgsqlTest extends Horde_Share_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('pgsql', PDO::getAvailableDrivers())) {
            return;
        }
        $config = self::getConfig('SHARE_SQL_PDO_PGSQL_TEST_CONFIG');
        if ($config) {
            try {
                self::$db = new Horde_Db_Adapter_Pdo_Pgsql($config['share']['sql']['pdo_pgsql']);
            } catch (Horde_Db_Exception $e) {
                return;
            }
            parent::setUpBeforeClass();
        }
    }
}
