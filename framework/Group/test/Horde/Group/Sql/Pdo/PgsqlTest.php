<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Group_Sql_Pdo_PgsqlTest extends Horde_Group_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('pgsql', PDO::getAvailableDrivers())) {
            self::$reason = 'No pgsql extension or no pgsql PDO driver';
            return;
        }
        $config = self::getConfig('GROUP_SQL_PDO_PGSQL_TEST_CONFIG',
                                  dirname(__FILE__) . '/../..');
        if ($config && !empty($config['group']['sql']['pdo_pgsql'])) {
            self::$db = new Horde_Db_Adapter_Pdo_Pgsql($config['group']['sql']['pdo_pgsql']);
            parent::setUpBeforeClass();
        }
    }
}
