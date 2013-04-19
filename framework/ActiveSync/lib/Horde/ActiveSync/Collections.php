<?php
/**
 * Horde_ActiveSync_Collections::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Collections:: Functionality related to a group of collections
 * being handled during a sync request.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Collections implements IteratorAggregate
{

    const COLLECTION_ERR_FOLDERSYNC_REQUIRED = -1;
    const COLLECTION_ERR_SERVER              = -2;
    const COLLECTION_ERR_STALE               = -3;
    const COLLECTION_ERR_SYNC_REQUIRED       = -4;

    /**
     * The collection data
     *
     * @var array
     */
    protected $_collections = array();

    /**
     * Cache a temporary syncCache.
     *
     * @var Horde_ActiveSync_SyncCache
     */
    protected $_tempSyncCache;

    /**
     * The syncCache
     *
     * @var Horde_ActiveSync_SyncCache
     */
    protected $_cache;

    /**
     * The logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Count of unchanged collections calculated for PARTIAL sync.
     *
     * @var integer
     */
    protected $_unchangedCount = 0;

    /**
     * Count of available synckeys
     *
     * @var integer
     */
    protected $_synckeyCount = 0;

    /**
     * Count of confirmed collections calculated for PARTIAL sync.
     *
     * @var integer
     */
    protected $_confirmedCount = 0;

    /**
     * Global WINDOWSIZE
     *
     * @var integer
     */
    protected $_windowSize = null;

    /**
     * Imported changes flag.
     *
     * @var boolean
     */
    protected $_importedChanges = false;

    /**
     * Short sync request flag.
     *
     * @var boolean
     */
    protected $_shortSyncRequest = false;

    /**
     * Cache of collections that have had changes detected.
     *
     * @var array
     */
    protected $_changedCollections = array();

    /**
     * The ActiveSync server object.
     *
     * @var Horde_ActiveSync
     */
    protected $_as;

    /**
     * Cache the process id for logging.
     *
     * @var integer
     */
    protected $_procid;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_SyncCache $cache  The SyncCache.
     * @param Horde_ActiveSync $as               The ActiveSync server object.
     */
    public function __construct(
        Horde_ActiveSync_SyncCache $cache,
        Horde_ActiveSync $as)
    {

        $this->_cache = $cache;
        $this->_as = $as;
        $this->_logger = $as->logger;
        $this->_procid = getmypid();
    }

    /**
     * Load all the collections we know about from the cache.
     */
    public function loadCollectionsFromCache()
    {
        foreach ($this->_cache->getCollections(false) as $collection) {
            $this->_logger->debug(sprintf(
                '[%s] Loading %s from the cache.',
                $this->_procid,
                $collection['id']));
            if (empty($collection['synckey']) && !empty($collection['lastsynckey'])) {
                $collection['synckey'] = $collection['lastsynckey'];
            }
            $this->_collections[$collection['id']] = $collection;
        }
    }

    /**
     * Magic...
     */
    public function __call($method, $parameters)
    {
        switch ($method) {
        case 'hasPingChangeFlag':
        case 'addConfirmedKey':
        case 'updateCollection':
        case 'collectionExists':
        case 'updateWindowSize':
            return call_user_func_array(array($this->_cache, $method), $parameters);
        }

        throw new BadMethodCallException('Unknown method: ' . $method);
    }

    /**
     * Property getter
     */
    public function __get($property)
    {
        switch ($property) {
        case 'hbinterval':
        case 'wait':
        case 'confirmed_synckeys':
        case 'lasthbsyncstarted':
        case 'lastsyncendnormal':
            return $this->_cache->$property;
        case 'importedChanges':
        case 'shortSyncRequest':
            $p = '_' . $property;
            return $this->$p;
        }

        throw new InvalidArgumentException('Unknown property: ' . $property);
    }

    /**
     * Property setter.
     */
    public function __set($property, $value)
    {
        switch ($property) {
        case 'importedChanges':
        case 'shortSyncRequest':
            $p = '_' . $property;
            $this->$p = $value;
            return;
        case 'lasthbsyncstarted':
        case 'lastsyncendnormal':
        case 'hbinterval':
        case 'wait':
            $this->_cache->$property = $value;
            return;

        case 'confirmed_synckeys':
            throw new InvalidArgumentException($property . ' is READONLY.');
        }

        throw new InvalidArgumentException('Unknown property: ' . $property);
    }

    /**
     * Get a new collection array, populated with default values.
     *
     * @return array
     */
    public function getNewCollection()
    {
        return array(
            'truncation' => Horde_ActiveSync::TRUNCATION_ALL,
            'clientids' => array(),
            'fetchids' => array(),
            'windowsize' => 100,
            'conflict' => Horde_ActiveSync::CONFLICT_OVERWRITE_PIM,
            'bodyprefs' => array(),
            'mimesupport' => Horde_ActiveSync::MIME_SUPPORT_NONE,
            'mimetruncation' => Horde_ActiveSync::TRUNCATION_8,
        );
    }

    /**
     * Add a new populated collection array to this collection.
     *
     * @param array $collection  The collection array.
     */
    public function addCollection($collection)
    {
        // @TODO: Some sanity checking on synckey or id?
        $this->_collections[$collection['id']] = $collection;
    }

    /**
     * Return the count of available collections.
     *
     * @return integer
     */
    public function collectionCount()
    {
        return count($this->_collections);
    }

    /**
     * Return the count of collections in the cache only.
     *
     * @return integer
     */
    public function cachedCollectionCount()
    {
        return $this->_cache->countCollections();
    }

    /**
     * Set the getchanges flag on the specified collection.
     *
     * @param string $collection_id  The collection id.
     * @throws Horde_ActiveSync_Exception
     */
    public function setGetChangesFlag($collection_id)
    {
        if (empty($this->_collections[$collection_id])) {
            throw new Horde_ActiveSync_Exception('Missing collection data');
        }
        $this->_collections[$collection_id]['getchanges'] = true;
    }

    /**
     * Get the getchanges flag on the specified collection.
     *
     * @param string $collection_id  The collection id.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function getChangesFlag($collection_id)
    {
        if (empty($this->_collections[$collection_id])) {
            throw new Horde_ActiveSync_Exception('Missing collection data');
        }
        return !(empty($this->_collections[$collection_id]['getchanges']));
    }

    /**
     * Sets the default WINDOWSIZE
     *
     * @param integer $window  The windowsize
     */
    public function setDefaultWindowSize($window)
    {
        $this->_windowSize = $window;
    }

    /**
     * Validates the collection data from the syncCache, filling in missing
     * values from the folder cache.
     */
    public function validateFromCache()
    {
        $this->_cache->validateCollectionsFromCache($this->_collections);
    }

    /**
     * Updates data from the cache for collectons that are already loaded. Used
     * to ensure looping SYNC and PING requests are operating on the most
     * recent syncKey.
     */
    public function updateCollectionsFromCache()
    {
        $this->_cache->refreshCollections();
        $collections = $this->_cache->getCollections();
        foreach (array_keys($this->_collections) as $id) {
            if (!empty($collections[$id])) {
                $this->_logger->debug(sprintf(
                    '[%s] Refreshing %s from the cache.',
                    $this->_procid, $id));
                $this->_collections[$id] = $collections[$id];
            } else {
                unset($this->_collections[$id]);
            }
        }
    }

    /**
     * Return a collection class given the collection id.
     *
     * @param string $id  The collection id.
     *
     * @return string|boolean  The collection class or false if not found.
     */
    public function getCollectionClass($id)
    {
        if (isset($this->_cache->folders[$id]['class'])) {
            return $this->_cache->folders[$id]['class'];
        }

        return false;
    }

    /**
     * Determine if we have any syncable collections either locally or in the
     * sync cache.
     *
     * @param long $version  The EAS version
     *
     * @return boolean
     */
    public function haveSyncableCollections($version)
    {
        // Ensure we have syncable collections, using the cache if needed.
        if ($version >= Horde_ActiveSync::VERSION_TWELVEONE && empty($this->_collections)) {
            $this->_logger->debug('No collections - looking in sync_cache.');
            $found = false;
            foreach ($this->_cache->getCollections() as $value) {
                if (isset($value['synckey'])) {
                    $this->_logger->debug(sprintf(
                        '[%s] Found a syncable collection: %s : %s. Adding it to the collections object.',
                        $this->_procid, $value['id'], $value['synckey']));
                    $this->_collections[$value['id']] = $value;
                    $found = true;
                }
            }
            return $found;
        } elseif (empty($this->_collections)) {
            return false;
        }

        $this->_logger->debug('Have syncable collections');

        return true;
    }

    /**
     * Set the looping sync heartbeat values.
     *
     * @param array $hb  An array containing one or both of: hbinterval, wait.
     */
    public function setHeartbeat($hb)
    {
        if (isset($hb['wait'])) {
            $this->_cache->wait = $hb['wait'];
        }
        if (isset($hb['hbinterval'])) {
            $this->_cache->hbinterval = $hb['hbinterval'];
        }
    }

    /**
     * Return the heartbeat interval. Always returned as the heartbeat (seconds)
     * not wait interval (minutes).
     *
     * @return integer|boolean  The number of seconds in a heartbeat, or false
     *                          if no heartbeat set.
     */
    public function getHeartbeat()
    {
        return !empty($this->_cache->hbinterval)
            ? $this->_cache->hbinterval
            : (!empty($this->_cache->wait)
                ? $this->_cache->wait * 60
                : false);
    }

    /**
     * Return whether or not we want a looping sync.
     *
     * @return boolean  True if we want a looping sync, false otherwise.
     */
    public function canDoLoopingSync()
    {
        // do we need the shortSynRequest?
        return ($this->_shortSyncRequest || $this->_cache->hbinterval !== false || $this->_cache->wait !== false) &&
            !$this->_importedChanges;
    }

    /**
     * Return if the current looping sync is stale. A stale looping sync is one
     * which has begun earlier than the most recently running sync reported by
     * the syncCache.
     *
     * @return boolean  True if the current looping sync is stale. False
     *                  otherwise.
     */
    public function checkStaleRequest()
    {
        return !$this->_cache->validateCache(true);
    }

    /**
     * Return if we have a current folder hierarchy.
     *
     * @return boolean
     */
    public function haveHierarchy()
    {
        return isset($this->_cache->hierarchy);
    }

    /**
     * Prepares the syncCache for a full sync request.
     */
    public function initFullSync()
    {
        $this->_cache->confirmed_synckeys = array();
        $this->_cache->clearCollectionKeys();
    }

    /**
     * Prepares the syncCache for a partial sync request and checks that
     * it is allowed.
     *
     * @return boolean True if parital sync is possible, false otherwise.
     */
    public function initPartialSync()
    {
        $this->_tempSyncCache = clone $this->_cache;
        foreach ($this->_collections as $key => $value) {
            $v1 = $this->_collections[$key];
            unset($v1['id'], $v1['clientids'], $v1['fetchids'],
                  $v1['getchanges'], $v1['changeids']);
            $c = $this->_tempSyncCache->getCollections();
            $v2 = $c[$value['id']];
            ksort($v1);
            if (isset($v1['bodyprefs'])) {
                ksort($v1['bodyprefs']);
                foreach (array_keys($v1['bodyprefs']) as $k) {
                    if (is_array($v1['bodyprefs'][$k])) {
                        ksort($v1['bodyprefs'][$k]);
                    }
                }
            }
            ksort($v2);
            if (isset($v2['bodyprefs'])) {
                ksort($v2['bodyprefs']);
                foreach (array_keys($v2['bodyprefs']) as $k) {
                    if (is_array($v2['bodyprefs'][$k])) {
                        ksort($v2['bodyprefs'][$k]);
                    }
                }
            }
            if (md5(serialize($v1)) == md5(serialize($v2))) {
                $this->_unchangedCount++;
            }
            // Unset in tempSyncCache, since we have it from device.
            // Afterwards, anything left in tempSyncCache needs to be
            // added to _collections.
            $this->_tempSyncCache->removeCollection($value['id']);

            // Remove keys from confirmed synckeys array and count them
            if (isset($value['synckey'])) {
                if (isset($this->_cache->confirmed_synckeys[$value['synckey']])) {
                    $this->_logger->debug(sprintf(
                        'Removed %s from confirmed_synckeys',
                        $value['synckey'])
                    );
                    $this->_cache->removeConfirmedKey($value['synckey']);
                    $this->_confirmedCount++;
                }
                $this->_synckeyCount++;
            }
        }

        if (!$this->_cache->validateTimestamps()) {
            $this->_logger->debug('Request full sync, timestamp validation failed.');
            return false;
        }

        return true;
    }

    /**
     * Return if we can do an empty response
     *
     * @return boolean
     */
    public function canSendEmptyResponse()
    {
        return !$this->_importedChanges &&
            ($this->_cache->wait !== false || $this->_cache->hbinterval !== false);
    }

    /**
     * Return if we have no changes, but have requested a partial sync.
     *
     * @return boolean
     */
    public function haveNoChangesInPartialSync()
    {
        return $this->_synckeyCount > 0 &&
            $this->_confirmedCount == 0 &&
            $this->_unchangedCount == $this->_synckeyCount &&
            ($this->_cache->wait == false && $this->_cache->hbinterval == false);
    }

    /**
     * Populate the collections data with missing data from the syncCache.
     */
    public function getMissingCollectionsFromCache()
    {
        // Update _collections with all data that was not sent, but we
        // have a synckey for in the sync_cache.
        foreach ($this->_tempSyncCache->getCollections() as $value) {
            if (isset($this->_windowSize)) {
                $value['windowsize'] = $this->_windowSize;
            }
            $this->_logger->debug(sprintf(
                'Using SyncCache State for %s',
                $value['id']
            ));
            $this->_collections[$value['id']] = $value;
        }
    }

    /**
     * Check the loop counters for any possible infinite sync attempts. Will
     * reset the collection state for any collection that has reached the
     * MAXIMUM_SYNCKEY_COUNTER value.
     *
     * @param Horde_ActiveSync_State_Base  The state object.
     *
     * @return boolean  True if counters validate, false if we have reached the
     *                  MAXIMUM_SYNCKEY_COUNTER value and cleared the state.
     */
    public function checkLoopCounters($state)
    {
        $counters = $this->_cache->synckeycounter;
        foreach ($this->_collections as $id => $collection) {
            if (!empty($counters[$id][$collection['synckey']]) &&
                $counters[$id][$collection['synckey']] > Horde_ActiveSync::MAXIMUM_SYNCKEY_COUNT) {

                $this->_logger->err('Reached MAXIMUM_SYNCKEY_COUNT possible sync loop. Clearing state.');
                $state->loadState(
                    array(),
                    null,
                    Horde_ActiveSync::REQUEST_TYPE_SYNC,
                    $collection['id']);
                return false;
            } elseif (empty($counters[$collection['id']][$collection['synckey']])) {
                // First time for this synckey. Remove others.
                $counters[$collection['id']] = array($collection['synckey'] => 0);
            } else {
                $this->_logger->debug('LOOP COUNTER: ' . $collection['synckey'] . ' : ' . $counters[$id][$collection['synckey']]);
            }
        }
        $this->_cache->synckeycounter = $counters;

        return true;
    }

    public function incrementLoopCounter($id, $key)
    {
        $counters = $this->_cache->synckeycounter;
        if (empty($counters[$id][$key])) {
            $counters[$id][$key] = 0;
        }
        if (++$counters[$id][$key] > 1) {
            $this->_logger->debug('Incrementing loop counter. We saw this synckey before.');
        }
        $this->_cache->synckeycounter = $counters;
    }

    /**
     * Check for an update FILTERTYPE
     *
     * @return boolean  True if filtertype passed, false if it has changed.
     */
    public function checkFilterType()
    {
        foreach ($this->_collections as $id => $collection) {
            $cc = $this->_cache->getCollections();
            if (!empty($cc[$id]['filtertype']) &&
                !empty($collection['filtertype']) &&
                $cc[$id]['filtertype'] != $collection['filtertype']) {

                $this->_cache->removeCollection($id);
                $this->_cache->save();
                $this->_logger->debug('Invalidating SYNCKEY - found updated filtertype');

                return false;
            }
        }

        return true;
    }

    /**
     * Update the syncCache with current collection data.
     */
    public function updateCache()
    {
        foreach ($this->_collections as $value) {
            $this->_cache->updateCollection($value);
        }
    }

    /**
     * Save the syncCache to storage.
     */
    public function save()
    {
        $this->_cache->save();
    }

    /**
     * Attempt to initialize the sync state.
     *
     * @param array $collection  The collection array.
     */
    public function initCollectionState($collection)
    {
        // Initialize the state
        $this->_logger->debug(sprintf(
            '[%s] Initializing state for collection: %s, synckey: %s',
            $this->_procid,
            $collection['id'],
            $collection['synckey']));
        $this->_as->state->loadState(
            $collection,
            $collection['synckey'],
            Horde_ActiveSync::REQUEST_TYPE_SYNC,
            $collection['id']);
    }

    /**
     * Poll the backend for changes.
     *
     * @param integer $heartbeat  The heartbeat lifetime to wait for changes.
     * @param integer $interval  The wait interval between poll iterations.
     * @param array $options  An options array containing any of:
     *   - pingable: (boolean)  Only poll collections with the pingable flag set.
     *                DEFAULT: false
     *
     * @return boolean|integer True if changes were detected in any of the
     *                         collections, false if no changes detected
     *                         or a status code if failed.
     */
    public function pollForChanges($heartbeat, $interval, array $options = array())
    {
        $dataavailable = false;
        $started = time();
        $until = $started + $heartbeat;

        $this->_logger->debug(sprintf(
            'Waiting for changes for %s seconds',
            $heartbeat)
        );
        $this->lasthbsyncstarted = $started;
        $this->save();

        while (($now = time()) < $until) {
            // Try not to go over the heartbeat interval.
            if ($until - $now < $interval) {
                $interval = $until - $now;
            }

            // See if another process has altered the sync_cache.
            if ($this->checkStaleRequest()) {
                return self::COLLECTION_ERR_STALE;
            }

            // Make sure the collections are still there (there might have been
            // an error in refreshing them from the cache).
            if (!count($this->_collections)) {
                $this->_logger->err('NO COLLECTIONS??');

                return self::COLLECTION_ERR_SYNC_REQUIRED;
            }

            // Check for WIPE request. If so, force a foldersync so it is performed.
            if ($this->_as->provisioning != Horde_ActiveSync::PROVISIONING_NONE) {
                $rwstatus = $this->_as->state->getDeviceRWStatus($this->_as->device->id);
                if ($rwstatus == Horde_ActiveSync::RWSTATUS_PENDING || $rwstatus == Horde_ActiveSync::RWSTATUS_WIPED) {
                    return self::COLLECTION_ERR_FOLDERSYNC_REQUIRED;
                }
            }

            // Check each collection we are interested in.
            foreach ($this->_collections as $id => $collection) {
                // Skip non-pingable collections if requested.
                if (!empty($options['pingable']) && !$this->_cache->collectionIsPingable($id)) {
                    $this->_logger->debug(sprintf(
                        '[%s] Skipping %s because it is not PINGable.',
                        $this->_procid, $id));
                    continue;
                }

                try {
                    $this->initCollectionState($this->_collections[$id]);
                } catch (Horde_ActiveSync_Exception_StateGone $e) {
                    $this->_logger->err(sprintf(
                        '[%s] State not found for %s, continuing',
                        $this->_procid,
                        $id)
                    );
                    $dataavailable = true;
                    $this->setGetChangesFlag($id);
                    continue;
                } catch (Horde_ActiveSync_Exception $e) {
                    return self::COLLECTION_ERR_SERVER;
                }

                $sync = $this->_as->getSyncObject();
                try {
                    $sync->init($this->_as->state, null, $collection, true);
                } catch (Horde_ActiveSync_Expcetion_StaleState $e) {
                    $this->_logger->err(sprintf(
                        '[%s] SYNC terminating and force-clearing device state: %s',
                        $this->_procid,
                        $e->getMessage())
                    );
                    $this->_as->state->loadState(
                        array(),
                        null,
                        Horde_ActiveSync::REQUEST_TYPE_SYNC,
                        $id);
                    $changecount = 1;
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                    $this->_logger->err(sprintf(
                        '[%s] SYNC terminating: %s',
                        $this->_procid,
                        $e->getMessage())
                    );
                    return self::COLLECTION_ERR_FOLDERSYNC_REQUIRED;

                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err(sprintf(
                        '[%s] Sync object cannot be configured, throttling: %s',
                        $this->_procid,
                        $e->getMessage())
                    );
                    sleep(30);
                    continue;
                }
                $changecount = $sync->getChangeCount();
                if (($changecount > 0)) {
                    $dataavailable = true;
                    $this->setGetChangesFlag($id);
                }
            }

            if (!empty($dataavailable)) {
                $this->_logger->debug(sprintf(
                    '[%s] Found changes!',
                    $this->_procid)
                );
                //$this->save();
                break;
            }

            // Wait.
            $this->_logger->debug(sprintf(
                '[%s] Sleeping for %s seconds.', $this->_procid, $interval));
            sleep ($interval);

            // Refresh the collections.
            $this->updateCollectionsFromCache();
        }

        // Check that no other Sync process already started
        // If so, we exit here and let the other process do the export.
        if ($this->checkStaleRequest()) {
            $this->_logger->debug('Changes in cache determined during Sync Wait/Heartbeat, exiting here.');

            return self::COLLECTION_ERR_STALE;
        }

        $this->_logger->debug(sprintf(
            '[%s] Looping Sync complete: DataAvailable: %s, DataImported: %s',
            $this->_procid,
            $dataavailable,
            $this->importedChanges)
        );

        return $dataavailable;
    }

    /**
     * Iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_collections);
    }

}