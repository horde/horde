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
class Horde_Share_Sql_Pdo_SqliteTest extends Horde_Share_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('sqlite', PDO::getAvailableDrivers())) {
            return;
        }
        try {
            self::$db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:', 'charset' => 'utf-8'));
        } catch (Horde_Db_Exception $e) {
            return;
        }
        parent::setUpBeforeClass();
    }
}
