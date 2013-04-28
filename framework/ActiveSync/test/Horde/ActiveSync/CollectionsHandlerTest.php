<?php
/**
 * Unit tests Horde_ActiveSync_Collections::
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_CollectionsHandlerTest extends Horde_Test_Case
{

    static protected $_logger;
    protected $_as;
    protected $_cache;

    public function setup()
    {
        // Cache with zero collections.
        $this->_cache = $this->_getMockSyncCache();

        // AS server object.
        $this->_as = $this->_getMockActiveSyncObject();
    }

    /**
     * Tests iniitial state of Collections handler.
     * Assumptions:
     *   $syncCache contains a valid cache for the client which contains 3
     *   syncable collections.
     */
    public function testInitialState()
    {
        $collections = new Horde_ActiveSync_Collections($this->_cache, $this->_as);

        // Should be no loaded collections in the handler.
        $this->assertEquals(0, $collections->collectionCount());

        // No syncable collections either, even though we have a synccache, none
        // of the collections have a 'synckey' and have not been loaded into
        // collection handler.
        $this->assertEquals(false, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWOFIVE));
        $this->assertEquals(false, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWELVEONE));

        // We have 3 collections in the synccache.
        $this->assertEquals(3, $collections->cachedCollectionCount());
        $collections->validateFromCache();
    }

    /**
     * Tests loading the collections from the cache and ensuring we have synckeys
     */
    public function testLoadingCollectionsFromCache()
    {
        $collections = new Horde_ActiveSync_Collections($this->_cache, $this->_as);
        $collections->loadCollectionsFromCache();
        $this->assertEquals(3, $collections->collectionCount());
        $this->assertEquals(true, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWOFIVE));
        $this->assertEquals(true, $collections->haveSyncableCollections(Horde_ActiveSync::VERSION_TWELVEONE));
    }

    /**
     * Tests the setup for a PARTIAL sync request.
     */
    public function testParitalSyncWithUnchangedCollections()
    {
        $this->markTestIncomplete('Need to work out the confirmed_synckeys stuff');
        $collections = new Horde_ActiveSync_Collections($this->_cache, $this->_as);

        // Should return false because we haven't loaded (from incoming xml) any
        // collections yet, so no collections in handler are syncable.
        $this->assertEquals(false, $collections->initPartialSync());

        // Pretent to read a new collection in from xml.
        // This one is identical to what we already have, so this should also
        // fail.
        $col = array(
            'id' => 'INBOX',
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
            'filtertype' => 5,
        );
        $collections->addCollection($col);
        $this->assertEquals(false, $collections->initPartialSync());
    }

    /**
     * Tests the setup for a PARTIAL sync request.
     */
    public function testParitalSyncWithChangedCollections()
    {
        $this->markTestIncomplete('Need to work out the confirmed_synckeys stuff');
        $collections = new Horde_ActiveSync_Collections($this->_cache, $this->_as);

        // Now import a collection that IS different (which is the only reason
        // to have imported colletions with PARTIAL).
        $col = array(
            'id' => 'INBOX',
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
            'filtertype' => 5,
        );
        $collections->addCollection($col);
        $this->assertEquals(true, $collections->initPartialSync());
    }

    /**
     * Test ability to send an empty response.
     */
    public function testCanSendEmptyResponse()
    {
        $collections = new Horde_ActiveSync_Collections($this->_cache, $this->_as);
        $this->assertEquals(true, $collections->canSendEmptyResponse());
        $collections->importedChanges = true;
        $this->assertEquals(false, $collections->canSendEmptyResponse());
    }

    /**
     * Tests initiating a partial sync where 1 collection was passed from
     * client and 2 others had to be loaded from cache.
     */
    public function testMissingCollections()
    {
        $collections = new Horde_ActiveSync_Collections($this->_cache, $this->_as);
        $col = array(
            'id' => 'INBOX',
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
            'filtertype' => 5,
        );
        $collections->addCollection($col);
        $collections->initPartialSync();
        $this->assertEquals(1, $collections->collectionCount());
        $collections->getMissingCollectionsFromCache();
        $this->assertEquals(3, $collections->collectionCount());
    }

    /**
     * Test detecting a change in a collection's filtertype.
     */
    public function testChangingFilterType()
    {
        $collections = new Horde_ActiveSync_Collections($this->_cache, $this->_as);
        $col = array(
            'id' => 'INBOX',
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
        $this->assertEquals(false, $collections->checkFilterType());
    }

// --- Helper methods and callbacks ----
    protected function _getLogger()
    {
        if (empty(self::$_logger)) {
            $log = new Horde_Test_Log();
            self::$_logger = $log->getLogger();
        }

        return self::$_logger;
    }

    protected function _getMockSyncCache()
    {
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Sql');
        $state->expects($this->any())
            ->method('getSyncCache')
            ->will($this->returnValue($this->_getCollectionsFixture()));
        $cache = new Horde_ActiveSync_SyncCache($state, '12345', 'mike', $this->_getLogger());

        return $cache;
    }

    protected function _getMockActiveSyncObject()
    {
        $as = $this->getMockSkipConstructor('Horde_ActiveSync');
        $as->logger = $this->_getLogger();

        return $as;
    }

    protected function _getCollectionsFixture()
    {
        $fixture = array(
            'confirmed_synckeys' => array('{517541cc-b188-478d-9e1a-fa49c0a8015f}96' => true),
            'lasthbsyncstarted' => 1366647963,
            'lastsyncendnormal' => 1366647962,
            'timestamp' => 1366647963,
            'wait' => null,
            'hbinterval' => 650,
            'folders' => array(
                'Trash' => array(
                        'parentid' => 0,
                        'displayname' => 'Trash',
                        'class' => 'Email',
                        'type' => 4,
                        'filtertype' => 0),
                'Sent' => array(
                        'parentid' => 0,
                        'displayname' => 'Sent',
                        'class' => 'Email',
                        'type' => 5,
                        'filtertype' => 0),
                'INBOX' => array(
                        'parentid' => 0,
                        'displayname' => 'Inbox',
                        'class' => 'Email',
                        'type' => 2,
                        'filtertype' => 0),
                'Horde Lists/Dev' => array(
                        'parentid' => 'Horde Lists',
                        'displayname' => 'Dev',
                        'class' => 'Email',
                        'type' => 12,
                        'filtertype' => 0),
                'Horde Lists/Core' => array(
                        'parentid' => 'Horde Lists',
                        'displayname' => 'Core',
                        'class' => 'Email',
                        'type' => 12,
                        'filtertype' => 0),
                'Horde Lists/Bugs' => array(
                        'parentid' => 'Horde Lists',
                        'displayname' => 'Bugs',
                        'class' => 'Email',
                        'type' => 12,
                        'filtertype' => 0),
                'Horde Lists' => array(
                        'parentid' => 0,
                        'displayname' => 'Horde Lists',
                        'class' => 'Email',
                        'type' => 12,
                        'filtertype' => 0),
                '@Tasks@' => array(
                        'parentid' => 0,
                        'displayname' => 'Tasks',
                        'class' => 'Tasks',
                        'type' => 7,
                        'filtertype' => 0),
                '@Notes@' => array(
                        'parentid' => 0,
                        'displayname' => 'Notes',
                        'class' => 'Notes',
                        'type' => 10,
                        'filtertype' => 0),
                '@Contacts@' => array(
                        'parentid' => 0,
                        'displayname' => 'Contacts',
                        'class' => 'Contacts',
                        'type' => 9,
                        'filtertype' => 0),
                '@Calendar@' => array(
                        'parentid' => 0,
                        'displayname' => 'Calendar',
                        'class' => 'Calendar',
                        'type' => 8,
                        'filtertype' => 0)
            ),
            'hierarchy' => '{517541ca-1344-4b29-b691-fa49c0a8015f}3',
            'collections' => array(
                'INBOX' => array(
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
                    'lastsynckey' => '{517541cc-b188-478d-9e1a-fa49c0a8015f}96',
                    'deletesasmoves' => 1,
                    'filtertype' => 5,
                    'pingable' => 1,
                    'id' => 'INBOX'),
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
                    'lastsynckey' => '{517541ce-bd5c-4dbe-9378-2cb8c0a8015f}9',
                    'deletesasmoves' => 1,
                    'pingable' => 1,
                    'id' => '@Contacts@'),
                '@Calendar@' => array(
                    'class' => 'Calendar',
                    'windowsize' => 4,
                    'truncation' => 0,
                    'mimesupport' => 0,
                    'mimetruncation' => 8,
                    'conflict' => 1,
                    'bodyprefs' => array(
                        'wanted' => 1,
                        1 => array(
                            'type' => 1,
                            'truncationsize' => 200000),
                    ),
                    'lastsynckey' => '{517541ce-0260-4da3-bc83-2c42c0a8015f}2',
                    'deletesasmoves' => 1,
                    'filtertype' => 4,
                    'pingable' => 1,
                    'id' => '@Calendar@')
            ),
            'pingheartbeat' => 650,
            'synckeycounter' => array(
                'INBOX' => array('{517541,cc-b188-478d-9e1a-fa49c0a8015f}95' => 1),
                '@Contacts@' => array('{517541ce-bd5c-4dbe-9378-2cb8c0a8015f}9' => 0),
                '@Calendar@' => array('{517541ce-0260-4da3-bc83-2c42c0a8015f}2' => 0),
            ),
            'lastuntil' => 1366645709,
        );

        return $fixture;
    }

}
