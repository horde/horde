<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    History
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_History_Sql_Oci8Test extends Horde_History_TestBase
{
    protected static $db;
    protected static $logger;
    protected static $migrator;
    protected static $reason;

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('oci8')) {
            self::$reason = 'No oci8 extension';
            return;
        }
        $config = self::getConfig('HISTORY_SQL_OCI8_TEST_CONFIG', __DIR__ . '/..');
        if ($config && !empty($config['history']['sql']['oci8'])) {
            self::$db = new Horde_Db_Adapter_Oci8($config['history']['sql']['oci8']);

            $dir = dirname(__FILE__) . '/../../../../migration/Horde/History';
            if (!is_dir($dir)) {
                error_reporting(E_ALL & ~E_DEPRECATED);
                $dir = PEAR_Config::singleton()
                    ->get('data_dir', null, 'pear.horde.org')
                    . '/Horde_History/migration';
                error_reporting(E_ALL | E_STRICT);
            }
            self::$logger = new Horde_Test_Log();
            self::$migrator = new Horde_Db_Migration_Migrator(
                self::$db,
                self::$logger->getLogger(),
                array('migrationsPath' => $dir, 'schemaTableName' => 'horde_histories_schema'));
            self::$history = new Horde_History_Sql('test_user', self::$db);


        } else {
            self::$reason = 'No oci8 configuration';
        }
    }

    public function setUp()
    {
        // No idea why we need to call this here, but if we don't, the up
        // migration fails after the first test. It seems that the tearDown()
        // method also causes the schema table to be dropped as well. Can't
        // figure it out.
        self::setUpBeforeClass();
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
            return;
        }
        self::$migrator->up();
    }

   public function tearDown()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
    }

}
