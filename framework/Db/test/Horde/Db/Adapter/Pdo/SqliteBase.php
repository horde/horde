<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @group      horde_db
 * @category   Horde
 * @package    Db
 * @subpackage UnitTests
 */
class Horde_Db_Adapter_Pdo_SqliteBase extends Horde_Test_Case
{
    protected static $skip = true;

    public static function setUpBeforeClass()
    {
        if (extension_loaded('pdo') &&
            in_array('sqlite', PDO::getAvailableDrivers())) {
            try {
                list($conn,) = self::getConnection();
                self::$skip = false;
                $conn->disconnect();
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

    public static function getConnection($overrides = array())
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
        if (self::$skip) {
            $this->markTestSkipped('The PDO_SQLite adapter is not available');
        }
    }
}
