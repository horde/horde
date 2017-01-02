<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Prefs_Test_Sql_Base extends Horde_Test_Case
{
    protected static $db;

    protected static $migrator;

    protected static $reason;

    protected static $prefs;

    public function testCreatePreferences()
    {
        $p = new Horde_Prefs(
            'test',
            array(
                self::$prefs,
                new Horde_Prefs_Stub_Storage('test')
            )
        );
        $p['a'] = 'c';
        $p->store();
        $this->assertEquals(
            1,
            self::$db->selectValue(
                'SELECT COUNT(*) FROM horde_prefs WHERE pref_scope = ?',
                array('test'))
        );
    }

    public function testModifyPreferences()
    {
        $p = new Horde_Prefs(
            'horde',
            array(
                self::$prefs,
            )
        );
        $p['theme'] = "bar\0bie";
        $p->store();
        $this->assertEquals(
            "bar\0bie",
            $this->_readValue(
                self::$db->selectValue('SELECT pref_value FROM horde_prefs WHERE pref_uid = ? AND pref_scope = ? AND pref_name = ?',
                                       array('joe', 'horde', 'theme')))
        );
    }

    public static function setUpBeforeClass()
    {
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Cli());
        //self::$db->setLogger($logger);
        $dir = __DIR__ . '/../../../../../../migration/Horde/Prefs';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Prefs/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_prefs_schema_info'));
        self::$migrator->up();
        self::$db->insert(
            'INSERT INTO horde_prefs (pref_uid, pref_scope, pref_name, pref_value) VALUES (?, ?, ?, ?)',
            array('joe', 'horde', 'theme', new Horde_Db_Value_Binary('silver'))
        );

        self::$prefs = new Horde_Prefs_Storage_Sql('joe', array('db' => self::$db));
    }

    public static function tearDownAfterClass()
    {
        self::$prefs = null;
        if (self::$db) {
            self::$db->delete('DELETE FROM horde_prefs');
        }
        if (self::$migrator) {
            self::$migrator->down();
            self::$migrator = null;
        }
        if (self::$db) {
            self::$db->disconnect();
            self::$db = null;
        }
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        }
    }

    protected function _readValue($value)
    {
        $columns = self::$db->columns('horde_prefs');
        return $columns['pref_value']->binaryToString($value);
    }
}
