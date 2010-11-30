<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @group      horde_db
 * @category   Horde
 * @package    Horde_Db
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
            } catch (Exception $e) {}
        }

        if ($skip) {
            $skipTest = new Horde_Db_Adapter_MissingTest('testMissingAdapter');
            $skipTest->adapter = 'PDO_SQLite';
            $suite->addTest($skipTest);
            return $suite;
        }

        require_once dirname(__FILE__) . '/SqliteTest.php';
        require_once dirname(__FILE__) . '/../Sqlite/ColumnTest.php';
        require_once dirname(__FILE__) . '/../Sqlite/ColumnDefinitionTest.php';
        require_once dirname(__FILE__) . '/../Sqlite/TableDefinitionTest.php';

        $suite->addTestSuite('Horde_Db_Adapter_Pdo_SqliteTest');
        $suite->addTestSuite('Horde_Db_Adapter_Sqlite_ColumnTest');
        $suite->addTestSuite('Horde_Db_Adapter_Sqlite_ColumnDefinitionTest');
        $suite->addTestSuite('Horde_Db_Adapter_Sqlite_TableDefinitionTest');

        return $suite;
    }

    public function getConnection()
    {
        $conn = new Horde_Db_Adapter_Pdo_Sqlite(array(
            'dbname' => ':memory:',
        ));

        $cache = new Horde_Cache_Storage_Mock();
        $conn->setCache($cache);

        return array($conn, $cache);
    }

    protected function setUp()
    {
        Horde_Db_AllTests::$connFactory = $this;
    }
}
