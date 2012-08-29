<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Ralf Lang <lang@ralf-lang.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Sesha
 * @subpackage UnitTests
 */

class Sesha_TestCase extends PHPUnit_Framework_TestCase
{


    /**
     * The prepared backend driver
     *
     * @var Sesha_Driver
     */

    protected static $db;
    protected static $driver;
    protected static $migrator;
    protected static $injector;

    public static function setUpBeforeClass()
    {
        self::$injector = new Horde_Injector(new Horde_Injector_TopLevel());
        self::$db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => __DIR__ . '/../../migration',
                  'schemaTableName' => 'sesha_test_schema'));
        self::$migrator->up();
        $driver_factory         = new Sesha_Factory_Driver(self::$injector);
        self::$driver = $driver_factory->create('Rdo', 
                            array(
                                'db' => self::$db,
                                'driver' => 'Rdo'
                            )
                        );
    }

}
