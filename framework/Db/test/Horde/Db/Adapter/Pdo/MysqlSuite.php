<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
            } catch (Exception $e) {}
        }

        if ($skip) {
            $skipTest = new Horde_Db_Adapter_MissingTest('testMissingAdapter');
            $skipTest->adapter = 'PDO_MySQL';
            $suite->addTest($skipTest);
            return $suite;
        }

        require_once dirname(__FILE__) . '/MysqlTest.php';
        require_once dirname(__FILE__) . '/../Mysql/ColumnTest.php';
        require_once dirname(__FILE__) . '/../Mysql/ColumnDefinitionTest.php';
        require_once dirname(__FILE__) . '/../Mysql/TableDefinitionTest.php';

        $suite->addTestSuite('Horde_Db_Adapter_Pdo_MysqlTest');
        $suite->addTestSuite('Horde_Db_Adapter_Mysql_ColumnTest');
        $suite->addTestSuite('Horde_Db_Adapter_Mysql_ColumnDefinitionTest');
        $suite->addTestSuite('Horde_Db_Adapter_Mysql_TableDefinitionTest');

        return $suite;
    }

    public function getConnection()
    {
        if (!class_exists('CacheMock')) eval('class CacheMock { function get($key) { return $this->$key; } function set($key, $val) { $this->$key = $val; } } ?>');
        $cache = new CacheMock;

        $config = array(
            'adapter' => 'pdo_mysql',
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'dbname' => 'test',
            'cache' => $cache,
        );
        if (isset($_ENV['HORDE_DB_TEST_DSN_PDO_MYSQL']))
            $config = array_merge($config, @json_decode($_ENV['HORDE_DB_TEST_DSN_PDO_MYSQL'], true));

        $conn = Horde_Db_Adapter::factory($config);
        return array($conn, $cache);
    }

    protected function setUp()
    {
        $this->sharedFixture = $this;
    }

}
