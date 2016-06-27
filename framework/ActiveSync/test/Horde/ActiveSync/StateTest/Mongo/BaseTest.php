<?php
/**
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/gpl GPLv2
 * @category Horde
 * @package Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_StateTest_Mongo_BaseTest extends Horde_ActiveSync_StateTest_Base
{
    protected static $db;
    protected static $reason;

    public function testGetDeviceInfo()
    {
        $this->_testGetDeviceInfo();
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
     * @depends testListDevices
     */
    public function testDuplicatePIMAddition()
    {
        // @TODO. For now, cheat and add the data directly to the db.
        try {
            $mongo = new Horde_Mongo_Client();
            $mongo->activesync_test->HAS_map->insert(array(
                'sync_clientid' => 'abc',
                'sync_user' => 'mike',
                'message_uid' => 'def',
                'sync_devid' => 'dev123'));
            self::$state->loadDeviceInfo('dev123', 'mike');
            $this->assertEquals('def', self::$state->isDuplicatePIMAddition('abc'));
        } catch (MongoConnectionException $e) {}
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
     * @depends testCollectionHandler
     */
    public function testPartialSyncWithOnlyChangedHbInterval()
    {
        $this->_testPartialSyncWithOnlyChangedHbInterval();
    }

    public static function tearDownAfterClass()
    {
        if ((extension_loaded('mongo') || extension_loaded('mongodb')) &&
            class_exists('Horde_Mongo_Client')) {
            try {
                $mongo = new Horde_Mongo_Client();
                $mongo->activesync_test->drop();
            } catch (MongoConnectionException $e) {
            }
        }
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        if (!(extension_loaded('mongo') || extension_loaded('mongodb')) ||
            !class_exists('Horde_Mongo_Client')) {
            $this->markTestSkipped('MongoDB extension not loaded.');
            return;
        }
        try {
            $mongo = new Horde_Mongo_Client();
        } catch (MongoConnectionException $e) {
            $this->markTestSkipped('Mongo connection failed.');
            return;
        }
        $mongo->dbname = 'activesync_test';
        self::$state = new Horde_ActiveSync_State_Mongo(array('connection' => $mongo));
        $backend = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Base');
        $backend->expects($this->any())->method('getUser')->will($this->returnValue('mike'));
        self::$state->setBackend($backend);
        self::$logger = new Horde_Test_Log();
    }

}
