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
class Horde_Db_Adapter_Pdo_PgsqlSuite extends PHPUnit_Framework_TestSuite
{
    public static $conn = null;

    public static function suite()
    {
        $suite = new self('Horde Framework - Horde_Db - PDO-PostgreSQL Adapter');

        $skip = true;
        if (extension_loaded('pdo') &&
            in_array('pgsql', PDO::getAvailableDrivers())) {
            try {
                self::$conn = $suite->getConnection();
                $skip = false;
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }

        if ($skip) {
            $skipTest = new Horde_Db_Adapter_MissingTest('testMissingAdapter');
            $skipTest->adapter = 'PDO_PostgreSQL';
            $suite->addTest($skipTest);
            return $suite;
        }

        require_once dirname(__FILE__) . '/PgsqlTest.php';
        require_once dirname(__FILE__) . '/../Postgresql/ColumnTest.php';
        require_once dirname(__FILE__) . '/../Postgresql/ColumnDefinitionTest.php';
        require_once dirname(__FILE__) . '/../Postgresql/TableDefinitionTest.php';

        $suite->addTestSuite('Horde_Db_Adapter_Pdo_PgsqlTest');
        $suite->addTestSuite('Horde_Db_Adapter_Postgresql_ColumnTest');
        $suite->addTestSuite('Horde_Db_Adapter_Postgresql_ColumnDefinitionTest');
        $suite->addTestSuite('Horde_Db_Adapter_Postgresql_TableDefinitionTest');

        return $suite;
    }

    public function getConnection()
    {
        if (!is_null(self::$conn)) {
            return self::$conn;
        }

        $config = Horde_Test_Case::getConfig('DB_ADAPTER_PDO_PGSQL_TEST_CONFIG',
                                             array('username' => '',
                                                   'password' => '',
                                                   'dbname' => 'test'));
        if (isset($config['db']['adapter']['pdo']['pgsql']['test'])) {
            $config = $config['db']['adapter']['pdo']['pgsql']['test'];
        }
        if (!is_array($config)) {
            throw new Exception('No configuration for pdo_pgsql test');
        }

        $conn = new Horde_Db_Adapter_Pdo_Pgsql($config);

        $cache = new Horde_Cache(new Horde_Cache_Storage_Mock());
        $conn->setCache($cache);

        return array($conn, $cache);
    }

    protected function setUp()
    {
        Horde_Db_AllTests::$connFactory = $this;
    }
}
