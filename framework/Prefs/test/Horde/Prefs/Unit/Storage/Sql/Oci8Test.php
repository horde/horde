<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Base.php';

/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Prefs_Unit_Storage_Sql_Oci8Test extends Horde_Prefs_Test_Sql_Base
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('oci8')) {
            self::$reason = 'No oci8 extension';
            return;
        }
        $config = self::getConfig('PREFS_SQL_OCI8_TEST_CONFIG',
                                  __DIR__ . '/../../..');
        if ($config && !empty($config['prefs']['sql']['oci8'])) {
            self::$db = new Horde_Db_Adapter_Oci8($config['prefs']['sql']['oci8']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No oci8 configuration';
        }
    }

    public function testLargePreferences()
    {
        $p = new Horde_Prefs(
            'test',
            array(
                self::$prefs,
                new Horde_Prefs_Stub_Storage('test')
            )
        );
        $value = str_repeat('x', 4001);
        $p['a'] = $value;
        $p->store();
        $this->assertEquals(
            $value,
            $this->_readValue(
                self::$db->selectValue(
                    'SELECT pref_value FROM horde_prefs WHERE pref_uid = ? AND pref_scope = ? AND pref_name = ?',
                    array('joe', 'test', 'a')
                )
            )
        );
    }
}
