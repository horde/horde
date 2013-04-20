<?php
require_once dirname(__FILE__) . '/../Base.php';

/**
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/gpl GPLv2
 * @category Horde
 * @package Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_StateTest_Sql_Base extends Horde_ActiveSync_StateTest_Base
{

    protected static $db;
    protected static $migrator;
    protected static $reason;

    public function testGetDeviceInfo()
    {
        // First with no existing deivce.
        $this->assertEquals(false, (boolean)self::$state->deviceExists('123', 'mike'));
        // Can't use setExpectedException here since it stops the rest
        // of the method from running when it's thrown.
        try {
            self::$state->loadDeviceInfo('123', 'mike');
            $this->fail('Did not raise expected Horde_ActiveSync_Exception.');
        } catch (Horde_ActiveSync_Exception $e) {
        }

        // Add the device, then retreive it.
        $deviceInfo = new Horde_ActiveSync_Device();
        $deviceInfo->rwstatus = 0;
        $deviceInfo->deviceType = 'Test Device';
        $deviceInfo->userAgent = 'Horde Tests';
        $deviceInfo->id = '123';
        $deviceInfo->user = 'mike';
        $deviceInfo->policykey = 0;
        $deviceInfo->supported = array();

        self::$state->setDeviceInfo($deviceInfo);
        $this->assertEquals(true, (boolean)self::$state->deviceExists('123', 'mike'));

        $di = self::$state->loadDeviceInfo('123', 'mike');
        $this->assertEquals($deviceInfo, $di);
    }

    /**
     * @depends testGetDeviceInfo
     */
    public function testGetStateWithNoState()
    {
        $this->_testGetStateWithNoState();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $dir = dirname(__FILE__) . '/../../../../../migration/Horde/ActiveSync';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_ActiveSync/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_activesync_test_schema'));
        self::$migrator->up();
        self::$state = new Horde_ActiveSync_State_Sql(array('db' => self::$db));
    }

    public static function tearDownAfterClass()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        }
    }
}