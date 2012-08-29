<?php
/**
 * Test the SQL based token backend.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Token
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the SQL based token backend.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Token
 */
class Horde_Token_Unit_SqlTest extends Horde_Token_BackendTestCase
{
    private static $_db;
    private static $_migration;

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('pdo') ||
            !in_array('sqlite', PDO::getAvailableDrivers())) {
            return;
        }
        self::$_db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:', 'charset' => 'utf-8'));

        $dir = __DIR__ . '/../../../../migration/Horde/Token';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Token/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        require_once $dir . '/1_horde_token_base_tables.php';
        self::$_migration = new HordeTokenBaseTables(self::$_db);
        self::$_migration->up();
    }

    public static function tearDownAfterClass()
    {
        if (self::$_db) {
            if (self::$_migration) {
                self::$_migration->down();
                self::$_migration = null;
            }
            self::$_db = null;
        }
    }

    public function setUp()
    {
        if (!extension_loaded('pdo') ||
            !in_array('sqlite', PDO::getAvailableDrivers())) {
            $this->markTestSkipped('No sqlite extension or no sqlite PDO driver.');
        }
    }

    protected function _getBackend(array $params = array())
    {
        $params = array_merge(
            array(
                'secret' => 'abc',
                'db' => self::$_db
            ),
            $params
        );
        return new Horde_Token_Sql($params);
    }

}