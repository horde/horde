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
class Horde_Db_Adapter_Pdo_PgsqlSuite extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new self('Horde Framework - Horde_Db - PDO-PostgreSQL Adapter');

        $skip = true;
        if (extension_loaded('pdo') && in_array('pgsql', PDO::getAvailableDrivers())) {
            try {
                list($conn,) = $suite->getConnection();
                $skip = false;
                $conn->disconnect();
            } catch (Exception $e) {}
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
        if (!class_exists('CacheMock', false)) eval('class CacheMock { function get($key) { return $this->$key; } function set($key, $val) { $this->$key = $val; } } ?>');
        $cache = new CacheMock;

        $config = array(
            'adapter' => 'pdo_pgsql',
            'username' => '',
            'password' => '',
            'dbname' => 'test',
            'cache' => $cache,
        );
        if (isset($_ENV['HORDE_DB_TEST_DSN_PDO_PGSQL']))
            $config = array_merge($config, @json_decode($_ENV['HORDE_DB_TEST_DSN_PDO_PGSQL'], true));

        $conn = Horde_Db_Adapter::factory($config);
        return array($conn, $cache);
    }

    protected function setUp()
    {
        $this->sharedFixture = $this;
    }

}
