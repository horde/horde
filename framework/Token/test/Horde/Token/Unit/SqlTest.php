<?php
/**
 * Test the SQL based token backend.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Token
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the SQL based token backend.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Token
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
            $this->markTestSkipped('No sqlite extension or no sqlite PDO driver.');
        }
        self::$_db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:', 'charset' => 'utf-8'));

        require_once dirname(__FILE__) . '/../../../../migration/Horde/Token/1_horde_token_base_tables.php';
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