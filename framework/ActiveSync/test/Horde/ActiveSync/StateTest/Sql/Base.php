<?php
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
        $this->_testGetDeviceInfo();
    }

    /**
     * @depends testGetDeviceInfo
     */
    public function testCacheInitialState()
    {
        $this->_testCacheInitialState();
    }

    /**
     * @depends testCacheInitialState
     */
    public function testCacheFolders()
    {
        $this->_testCacheFolders();
    }

    /**
     * @depends testCacheFolders
     */
    public function testCacheDataRestrictFields()
    {
        $this->_testCacheDataRestrictFields();
    }

    /**
     * @depends testCacheFolders
     */
    public function testCacheFoldersPersistence()
    {
        $this->_testCacheFoldersPersistence();
    }

    /**
     * @depends testCacheFolders
     */
    public function testCacheUniqueness()
    {
        $this->_testCacheUniqueness();
    }

    /**
     * @depends testCacheFolders
     */
    public function testCacheCollections()
    {
        $this->_testCacheCollections();
    }

    /**
     * @depends testCacheCollections
     */
    public function testLoadCollectionsFromCache()
    {
        return $this->_testLoadCollectionsFromCache();
    }

    /**
     * @depends testCacheCollections
     */
    public function testGettingImapId()
    {
        $this->_testGettingImapId();
    }

    /**
     * @depends testCacheCollections
     */
    public function testCacheRefreshCollections()
    {
        $this->_testCacheRefreshCollections();
    }

    /**
     * @depends testCacheCollections
     */
    public function testCollectionsFromCache()
    {
        $this->_testCollectionsFromCache();
    }

    /**
     * @depends testCacheFolders
     */
    public function testGetStateWithNoState()
    {
        $this->_testGetStateWithNoState();
    }

    /**
     * @depends testCollectionsFromCache
     */
    public function testCollectionHandler()
    {
        $this->_testCollectionHandler();
    }

    /**
     * @depends testCollectionHandler
     */
    public function testPartialSyncWithChangedCollections()
    {
        $this->_testPartialSyncWithChangedCollections();
    }

    /**
     * @depends testCollectionHandler
     */
    public function testPartialSyncWithUnchangedCollections()
    {
        $this->_testPartialSyncWithUnchangedCollections();
    }

    /**
     * @depends testCollectionHandler
     */
    public function testMissingCollections()
    {
        $this->_testMissingCollections();
    }

    /**
     * @depends testCollectionHandler
     */
    public function testChangingFilterType()
    {
        $this->_testChangingFilterType();
    }

    /**
     * @depends testCollectionHandler
     */
    public function testEmptyResponse()
    {
        $this->_testEmptyResponse();
    }

    /**
     * @depends testGetDeviceInfo
     */
    public function testHierarchy()
    {
        $this->_testHierarchy();
    }

    /**
     * @depends testGetDeviceInfo
     */
    public function testListDevices()
    {
        $this->_testListDevices();
    }

    /**
     * @depends testListDevices
     */
    public function testPolicyKeys()
    {
        $this->_testPolicyKeys();
    }

    /**
     * @depends testCollectionHandler
     */
    public function testPartialSyncWithOnlyChangedHbInterval()
    {
        $this->_testPartialSyncWithOnlyChangedHbInterval();
    }

    public static function setUpBeforeClass()
    {
        $dir = dirname(__FILE__) . '/../../../../../migration/Horde/ActiveSync';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_ActiveSync/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$logger = new Horde_Test_Log();
        if (self::$db) {
            self::$migrator = new Horde_Db_Migration_Migrator(
                self::$db,
                self::$logger->getLogger(),
                array('migrationsPath' => $dir,
                      'schemaTableName' => 'horde_activesync_schema'));
            self::$migrator->up();
        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$db) {
            if (self::$migrator) {
                self::$migrator->down();
            }
            self::$db->disconnect();
            self::$db = null;
        }
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
            return;
        }
        self::$state = new Horde_ActiveSync_State_Sql(array('db' => self::$db));
        $backend = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Base');
        $backend->expects($this->any())->method('getUser')->will($this->returnValue('mike'));
        self::$state->setBackend($backend);
    }
}