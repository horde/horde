<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @group      horde_db
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */
class Horde_Db_Adapter_Pdo_SqliteSuite extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new self('Horde Framework - Horde_Db - PDO-SQLite Adapter');

        $skip = true;
        if (extension_loaded('pdo') && in_array('sqlite', PDO::getAvailableDrivers())) {
            try {
                list($conn,) = $suite->getConnection();
                $skip = false;
                $conn->disconnect();
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }

        if ($skip) {
            $skipTest = new Horde_Db_Adapter_MissingTest('testMissingAdapter');
            $skipTest->adapter = 'PDO_SQLite';
            $suite->addTest($skipTest);
            return $suite;
        }

        require_once __DIR__ . '/SqliteTest.php';
        require_once __DIR__ . '/../Sqlite/ColumnTest.php';
        require_once __DIR__ . '/../Sqlite/ColumnDefinitionTest.php';
        require_once __DIR__ . '/../Sqlite/TableDefinitionTest.php';

        $suite->addTestSuite('Horde_Db_Adapter_Pdo_SqliteTest');
        $suite->addTestSuite('Horde_Db_Adapter_Sqlite_ColumnTest');
        $suite->addTestSuite('Horde_Db_Adapter_Sqlite_ColumnDefinitionTest');
        $suite->addTestSuite('Horde_Db_Adapter_Sqlite_TableDefinitionTest');

        return $suite;
    }

    public function getConnection($overrides = array())
    {
        $config = array(
            'dbname' => ':memory:',
        );
        $config = array_merge($config, $overrides);
        $conn = new Horde_Db_Adapter_Pdo_Sqlite($config);

        $cache = new Horde_Cache(new Horde_Cache_Storage_Mock());
        $conn->setCache($cache);

        return array($conn, $cache);
    }

    protected function setUp()
    {
        Horde_Db_AllTests::$connFactory = $this;
    }
}
