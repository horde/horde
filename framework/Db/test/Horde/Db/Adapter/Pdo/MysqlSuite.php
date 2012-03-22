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
class Horde_Db_Adapter_Pdo_MysqlSuite extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new self('Horde Framework - Horde_Db - PDO-MySQL Adapter');

        $skip = true;
        if (extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers())) {
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
            $skipTest->adapter = 'PDO_MySQL';
            $suite->addTest($skipTest);
            return $suite;
        }

        require_once __DIR__ . '/MysqlTest.php';
        require_once __DIR__ . '/../Mysql/ColumnTest.php';
        require_once __DIR__ . '/../Mysql/ColumnDefinitionTest.php';
        require_once __DIR__ . '/../Mysql/TableDefinitionTest.php';

        $suite->addTestSuite('Horde_Db_Adapter_Pdo_MysqlTest');
        $suite->addTestSuite('Horde_Db_Adapter_Mysql_ColumnTest');
        $suite->addTestSuite('Horde_Db_Adapter_Mysql_ColumnDefinitionTest');
        $suite->addTestSuite('Horde_Db_Adapter_Mysql_TableDefinitionTest');

        return $suite;
    }

    public function getConnection($overrides = array())
    {
        $config = Horde_Test_Case::getConfig('DB_ADAPTER_PDO_MYSQL_TEST_CONFIG',
                                             null,
                                             array('host' => 'localhost',
                                                   'username' => '',
                                                   'password' => '',
                                                   'dbname' => 'test'));
        if (isset($config['db']['adapter']['pdo']['mysql']['test'])) {
            $config = $config['db']['adapter']['pdo']['mysql']['test'];
        }
        if (!is_array($config)) {
            throw new Exception('No configuration for pdo_mysql test');
        }
        $config = array_merge($config, $overrides);

        $conn = new Horde_Db_Adapter_Pdo_Mysql($config);

        $cache = new Horde_Cache(new Horde_Cache_Storage_Mock());
        $conn->setCache($cache);

        return array($conn, $cache);
    }

    protected function setUp()
    {
        Horde_Db_AllTests::$connFactory = $this;
    }
}
