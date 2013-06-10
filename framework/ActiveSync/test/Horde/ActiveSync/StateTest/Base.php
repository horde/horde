<?php
/**
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/gpl GPLv2
 * @category Horde
 * @package Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_StateTest_Base extends Horde_Test_Case
{
    protected static $state;
    protected static $logger;

    protected function _testGetDeviceInfo()
    {
        // First with no existing deivce.
        $this->assertEquals(false, (boolean)self::$state->deviceExists('dev123', 'mike'));

        // Can't use setExpectedException here since it stops the rest
        // of the method from running when it's thrown.
        try {
            self::$state->loadDeviceInfo('dev123', 'mike');
            $this->fail('Did not raise expected Horde_ActiveSync_Exception.');
        } catch (Horde_ActiveSync_Exception $e) {
        }

        // Add the device, then retreive it.
        $deviceInfo = new Horde_ActiveSync_Device(self::$state);
        $deviceInfo->rwstatus = 0;
        $deviceInfo->deviceType = 'Test Device';
        $deviceInfo->userAgent = 'Horde Tests';
        $deviceInfo->id = 'dev123';
        $deviceInfo->user = 'mike';
        $deviceInfo->policykey = 0;
        $deviceInfo->supported = array();

        self::$state->setDeviceInfo($deviceInfo);
        $this->assertEquals(true, (boolean)self::$state->deviceExists('dev123', 'mike'));

        $di = self::$state->loadDeviceInfo('dev123', 'mike');
        $this->assertEquals($deviceInfo, $di);
    }

    protected function _testCacheInitialState()
    {
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $this->assertEquals(array(), $cache->getCollections());
        $this->assertEquals(array(), $cache->getCollections(true));
        $this->assertEquals(0, $cache->countCollections());
        $this->assertEquals(false, $cache->collectionExists('@Contacts@'));
        $this->assertEquals(false, $cache->collectionIsPingable('@Contacts@'));
        $this->assertEquals(false, $cache->collectionIsPingable('@Contacts@'));
        $this->assertEquals(array(), $cache->getFolders());
        $this->assertEquals(false, $cache->getFolder('@Contacts@'));
    }

    protected function _testCacheFolders()
    {
        $log = new Horde_Test_Log();
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());

        // First Fixture
        $folder = new Horde_ActiveSync_Message_Folder((array('logger' => $log->getLogger(), 'protocolversion' => Horde_ActiveSync::VERSION_TWELVEONE)));
        $folder->type = Horde_ActiveSync::FOLDER_TYPE_CONTACT;
        $folder->serverid = '@Contacts@';
        $folder->_serverid = '@Contacts@';
        $cache->updateFolder($folder);

        // Second fixture
        $folder = new Horde_ActiveSync_Message_Folder((array('logger' => $log->getLogger(), 'protocolversion' => Horde_ActiveSync::VERSION_TWELVEONE)));
        $folder->type = Horde_ActiveSync::FOLDER_TYPE_INBOX;
        $folder->serverid = '519422f1-4c5c-4547-946a-1701c0a8015f';
        $folder->_serverid = 'INBOX';
        $cache->updateFolder($folder);

        $expected = array(
            '@Contacts@' => array(
                'class' => 'Contacts',
                'serverid' => '@Contacts@',
            ),
            '519422f1-4c5c-4547-946a-1701c0a8015f' => array(
                'class' => 'Email',
                'serverid' => 'INBOX'
            )
        );
        $this->assertEquals($expected, $cache->getFolders());
        $expected = array(
            'class' => 'Email',
            'serverid' => 'INBOX'
        );
        $this->assertEquals($expected, $cache->getFolder('519422f1-4c5c-4547-946a-1701c0a8015f'));
        $cache->save();
    }

    protected function _testCacheFoldersPersistence()
    {
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $expected = array(
            '@Contacts@' => array(
                'class' => 'Contacts',
                'serverid' => '@Contacts@',
            ),
            '519422f1-4c5c-4547-946a-1701c0a8015f' => array(
                'class' => 'Email',
                'serverid' => 'INBOX'
            )
        );
        $this->assertEquals($expected, $cache->getFolders());
        $expected = array(
            'class' => 'Email',
            'serverid' => 'INBOX'
        );
        $this->assertEquals($expected, $cache->getFolder('519422f1-4c5c-4547-946a-1701c0a8015f'));
    }

    protected function _testCacheCollections()
    {
        $collections = array(
            '519422f1-4c5c-4547-946a-1701c0a8015f' => array(
                'class' => 'Email',
                'windowsize' => 5,
                'truncation' => 0,
                'mimesupport' => 0,
                'mimetruncation' => 8,
                'conflict' => 1,
                'bodyprefs' => array(
                    'wanted' => 2,
                    2 => array(
                        'type' => 2,
                        'truncationsize' => 200000)
                ),
                'deletesasmoves' => 1,
                'filtertype' => 5,
                'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
                'serverid' => 'INBOX'),
            '@Contacts@' => array(
                'class' => 'Contacts',
                'windowsize' => 4,
                'truncation' => 0,
                'mimesupport' => 0,
                'mimetruncation' => 8,
                'conflict' => 1,
                'bodyprefs' => array(
                    'wanted' => 1,
                    1 => array(
                        'type' => 1,
                        'truncationsize' => 200000)

                ),
                'deletesasmoves' => 1,
                'id' => '@Contacts@',
                'serverid' => '@Contacts@')
        );
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        foreach ($collections as $collection) {
            $cache->addCollection($collection);
        }

        // Mangle the fixture to match what we expect.
        $collections['519422f1-4c5c-4547-946a-1701c0a8015f']['rtftruncation'] = null;
        $collections['@Contacts@']['rtftruncation'] = null;
        $collections['@Contacts@']['filtertype'] = null;


        $this->assertEquals(2, $cache->countCollections());
        $this->assertEquals($collections, $cache->getCollections(false));
        $this->assertEquals(array(), $cache->getCollections(true));
        $this->assertEquals(true, $cache->collectionExists('@Contacts@'));
        $this->assertEquals(true, $cache->collectionExists('519422f1-4c5c-4547-946a-1701c0a8015f'));
        $this->assertEquals(false, $cache->collectionExists('foo'));

        $this->assertEquals(false, $cache->collectionIsPingable('@Contacts@'));
        $cache->setPingableCollection('@Contacts@');
        $this->assertEquals(true, $cache->collectionIsPingable('@Contacts@'));
        $cache->removePingableCollection('@Contacts@');
        $this->assertEquals(false, $cache->collectionIsPingable('@Contacts@'));
        $cache->updateCollection(
            array('id' => '519422f1-4c5c-4547-946a-1701c0a8015f', 'newsynckey' => '{51941e99-0b9c-41f8-b678-1532c0a8015f}2'),
            array('newsynckey' => true));
        $cache->save();

        // Now we should have a lastsynckey
        $this->assertEquals(1, count($cache->getCollections()));

        // And still have 2 if we don't need a synckey
        $this->assertEquals(2, count($cache->getCollections(false)));
    }

    protected function _testLoadCollectionsFromCache()
    {
        $collections = $this->getCollectionHandler();
        $collections->loadCollectionsFromCache();
        $this->assertEquals(2, $collections->collectionCount());
    }

    protected function _testCollectionsFromCache()
    {
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $collections = array('519422f1-4c5c-4547-946a-1701c0a8015f' => array('id' => '519422f1-4c5c-4547-946a-1701c0a8015f'));
        $expected = array('519422f1-4c5c-4547-946a-1701c0a8015f' => array(
            'class' => 'Email',
            'windowsize' => 5,
            'truncation' => 0,
            'mimesupport' => 0,
            'mimetruncation' => 8,
            'bodyprefs' => array(
                'wanted' => 2,
                2 => array(
                    'type' => 2,
                    'truncationsize' => 200000)
            ),
            'filtertype' => 5,
            'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
            'serverid' => 'INBOX'));
        $cache->validateCollectionsFromCache($collections);
        $this->assertEquals($expected, $collections);
    }

    protected function _testCacheRefreshCollections()
    {
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $newcache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $newcache->updateCollection(
            array('id' => '519422f1-4c5c-4547-946a-1701c0a8015f', 'newsynckey' => '{51941e99-0b9c-41f8-b678-1532c0a8015f}3'),
            array('newsynckey' => true));
        sleep(1);
        $newcache->save();

        $cache->refreshCollections();
        $collection = $cache->getCollections();
        $this->assertEquals($collection['519422f1-4c5c-4547-946a-1701c0a8015f']['lastsynckey'], '{51941e99-0b9c-41f8-b678-1532c0a8015f}3');

        // Test timestamp
        $this->assertEquals(false, $cache->validateCache());
    }

    protected function _testValidateAfterUpdateTimestamp()
    {
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $this->assertEquals(true, $cache->validateCache());
        $cache->updateTimestamp();
        $this->assertEquals(true, $cache->validateCache());
    }

    /**
     *
     */
    protected function _testCacheUniqueness()
    {
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'bob', self::$logger->getLogger());
        $this->assertEquals(array(), $cache->getFolders());

        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev456', 'mike', self::$logger->getLogger());
        $this->assertEquals(array(), $cache->getFolders());
    }

    protected function _testGetStateWithNoState()
    {
        self::$state->loadDeviceInfo('dev123');
        self::$state->loadState(array(), 0, Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC);
    }

    protected function _testCollectionHandler()
    {
        $collections = $this->getCollectionHandler();

        // Initial state
        $this->assertEquals(0, $collections->collectionCount());

        // No syncable collections either, even though we have a synccache, none
        // of the collections have a 'synckey' and have not been loaded into
        // collection handler.
        $this->assertEquals(false, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWOFIVE));
        $this->assertEquals(false, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWELVEONE));
        $this->assertEquals(2, $collections->cachedCollectionCount());

        // Now load the collections
        $collections->loadCollectionsFromCache();
        $this->assertEquals(2, $collections->collectionCount());
        $this->assertEquals(true, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWOFIVE));
        $this->assertEquals(true, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWELVEONE));
    }

    /**
     * Tests the setup for a PARTIAL sync request.
     */
    protected function _testPartialSyncWithChangedCollections()
    {
        $collections = $this->getCollectionHandler();
        $collections->loadCollectionsFromCache();

        // Now import a collection that IS different (which is the only reason
        // to have imported colletions with PARTIAL).
        $col = array(
            'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
            'windowsize' => 5,
            'truncation' => 0,
            'mimesupport' => 0,
            'mimetruncation' => 8,
            'conflict' => 1,
            'bodyprefs' => array(
                'wanted' => 2,
                2 => array(
                    'type' => 2,
                    'truncationsize' => 100000)
            ),
            'synckey' => '{51941e99-0b9c-41f8-b678-1532c0a8015f}3',
            'deletesasmoves' => 1,
            'filtertype' => 5,
        );
        $collections->addCollection($col);
        $this->assertEquals(2, $collections->collectionCount());
        $this->assertEquals(true, $collections->initPartialSync());
    }

    /**
     * Tests the setup for a PARTIAL sync request.
     */
    protected function _testPartialSyncWithUnchangedCollections()
    {
        // Pretend the heartbeat was not sent by the client.
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $cache->hbinterval = false;
        $cache->wait = false;
        $cache->save();
        $collections = $this->getCollectionHandler();
        $collections->loadCollectionsFromCache();
        // Should return false because we haven't loaded (from incoming xml) any
        // collections yet, so no collections in handler are syncable.
        $this->assertEquals(false, $collections->initPartialSync());

        // Pretent to read a new collection in from xml.
        // This one is identical to what we already have, so this should also
        // fail.
        $col = array(
            'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
            'windowsize' => 5,
            'truncation' => 0,
            'mimesupport' => 0,
            'mimetruncation' => 8,
            'conflict' => 1,
            'bodyprefs' => array(
                'wanted' => 2,
                2 => array(
                    'type' => 2,
                    'truncationsize' => 200000)
            ),
            'synckey' => '{517541cc-b188-478d-9e1a-fa49c0a8015f}3',
            'deletesasmoves' => 1,
            'filtertype' => 5,
        );
        $collections->addCollection($col);
        $this->assertEquals(false, $collections->initPartialSync());

        // Change the filtertype to simulate a new filtertype request from client.
        // This should now return true.
        $col['filtertype'] = 6;
        $collections->addCollection($col);
        $this->assertEquals(true, $collections->initPartialSync());
    }

    protected function _testPartialSyncWithOnlyChangedHbInterval()
    {
        $this->markTestSkipped('No idea why the cache does not load the collections here.');
        // Only a changed hbinterval should also allow a partial.
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $cache->hbinterval = 1;
        $cache->save();

        $collections = $this->getCollectionHandler();
        $collections->loadCollectionsFromCache();
        $this->assertEquals(true, $collections->initPartialSync());
    }

    protected function _testEmptyResponse()
    {
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        // Reset these from other tests.
        $cache->hbinterval = 100;
        $cache->save();

        $collections = $this->getCollectionHandler();
        $this->assertEquals(true, $collections->canSendEmptyResponse());
        $collections->importedChanges = true;
        $this->assertEquals(false, $collections->canSendEmptyResponse());
    }

    /**
     * Tests initiating a partial sync where 1 collection was passed from
     * client and 2 others had to be loaded from cache.
     */
    protected function _testMissingCollections()
    {
        // Need to prime the cache with a synckey for contacts so we have
        // another one to load for the test.
        $col = array('id' => '@Contacts@', 'newsynckey' => '{517541cc-b188-478d-aaaa-fa49c0a8015f}35');
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $cache->updateCollection($col, array('newsynckey' => true));
        $cache->save();
        $collections = $this->getCollectionHandler();
        $col = array(
            'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
            'windowsize' => 5,
            'truncation' => 0,
            'mimesupport' => 0,
            'mimetruncation' => 8,
            'conflict' => 1,
            'bodyprefs' => array(
                'wanted' => 2,
                2 => array(
                    'type' => 2,
                    'truncationsize' => 300000)
            ),
            'synckey' => '{517541cc-b188-478d-9e1a-fa49c0a8015f}3',
            'deletesasmoves' => 1,
            'filtertype' => 5,
        );
        $collections->addCollection($col);
        $collections->initPartialSync();
        $this->assertEquals(1, $collections->collectionCount());
        $collections->getMissingCollectionsFromCache();
        $this->assertEquals(2, $collections->collectionCount());
    }

    /**
     * Test detecting a change in a collection's filtertype.
     */
    protected function _testChangingFilterType()
    {
        $collections = $this->getCollectionHandler();
        $col = array(
            'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
            'windowsize' => 5,
            'truncation' => 0,
            'mimesupport' => 0,
            'mimetruncation' => 8,
            'conflict' => 1,
            'bodyprefs' => array(
                'wanted' => 2,
                2 => array(
                    'type' => 2,
                    'truncationsize' => 200000)
            ),
            'synckey' => '{517541cc-b188-478d-9e1a-fa49c0a8015f}96',
            'deletesasmoves' => 1,
            'filtertype' => 4,
        );
        $collections->addCollection($col);
        $this->assertEquals(false, $collections->checkFilterType($col['id'], $col['filtertype']));
    }

    protected function _testGettingImapId()
    {
        $collections = $this->getCollectionHandler();
        $this->assertEquals('INBOX', $collections->getBackendIdForFolderUid('519422f1-4c5c-4547-946a-1701c0a8015f'));
        $this->assertEquals('@Contacts@', $collections->getBackendIdForFolderUid('@Contacts@'));
    }

    protected function _testHierarchy()
    {
        self::$state->setBackend($this->getMockDriver());
        $collections = $this->getCollectionHandler(true);
        $seen = $collections->initHierarchySync(0);
        $this->assertEquals(array(), $seen);
        $expected = array(
            array(
                'type' => 'change',
                'flags' => 'NewMessage',
                'id' => '@Contacts@',
                'serverid' => '@Contacts@'
            ),
            array(
                'type' => 'change',
                'flags' => 'NewMessage',
                'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
                'serverid' => 'INBOX'
            )
        );
        $changes = $collections->getHierarchyChanges();
        $this->assertEquals($expected, $changes);
    }

    public function getMockDriver()
    {
        $fixture = array(
            array(
                'id' => '519422f1-4c5c-4547-946a-1701c0a8015f',
                'mod' => 'Inbox',
                'parent' => 0,
                'serverid' => 'INBOX'
            ),
            array(
                'id' => '@Contacts@',
                'mod' => '@Contacts@',
                'parent' => 0,
                'serverid' => '@Contacts@'
            )
        );
        $driver = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Base');
        $driver->expects($this->any())->method('getFolderList')->will($this->returnValue($fixture));
        $driver->expects($this->any())->method('getSyncStamp')->will($this->returnValue(100));

        return $driver;
    }

    public function getCollectionHandler($addDevice = false)
    {
        $as = $this->getMockSkipConstructor('Horde_ActiveSync');
        $as->logger = self::$logger->getLogger();
        $as->state = self::$state;
        if ($addDevice) {
            $as->device = self::$state->loadDeviceInfo('dev123', 'mike');
        }
        $cache = new Horde_ActiveSync_SyncCache(self::$state, 'dev123', 'mike', self::$logger->getLogger());
        $collections = new Horde_ActiveSync_Collections($cache, $as);

        return $collections;
    }

}