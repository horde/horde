<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
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
class Horde_Db_Adapter_Pdo_PgsqlBase extends Horde_Test_Case
{
    protected static $skip = true;

    protected static $conn;

    public static function setUpBeforeClass()
    {
        if (extension_loaded('pdo') &&
            in_array('pgsql', PDO::getAvailableDrivers())) {
            try {
                self::$conn = self::getConnection();
                self::$skip = false;
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

    public static function getConnection()
    {
        if (!is_null(self::$conn)) {
            return self::$conn;
        }

        $config = Horde_Test_Case::getConfig('DB_ADAPTER_PDO_PGSQL_TEST_CONFIG',
                                             null,
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
        if (self::$skip) {
            $this->markTestSkipped('The PDO_PostgreSQL adapter is not available');
        }
    }
}
