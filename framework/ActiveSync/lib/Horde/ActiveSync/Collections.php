<?php
/**
 * Horde_ActiveSync_Collections::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Collections:: Responsible for all functionality related to
 * collections and managing the sync cache.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal  Not intended for use outside of the ActiveSync library.
 */
class Horde_ActiveSync_Collections implements IteratorAggregate
{
    const COLLECTION_ERR_FOLDERSYNC_REQUIRED = -1;
    const COLLECTION_ERR_SERVER              = -2;
    const COLLECTION_ERR_STALE               = -3;
    const COLLECTION_ERR_SYNC_REQUIRED       = -4;
    const COLLECTION_ERR_PING_NEED_FULL      = -5;

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
    protected $_globalWindowSize = 100;

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
     * Cache of changes.
     *
     * @var array
     */
    protected $_changes;

    /**
     * Flag to indicate the client is requesting a hanging SYNC.
     *
     * @var boolean
     */
    protected $_hangingSync = false;

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
            if (empty($collection['synckey']) && !empty($collection['lastsynckey'])) {
                $collection['synckey'] = $collection['lastsynckey'];
            }
            // Load the class if needed for EAS >= 12.1
            if (empty($collection['class'])) {
                $collection['class'] = $this->getCollectionClass($collection['id']);
            }
            if (empty($collection['serverid'])) {
                try {
                    $collection['serverid'] = $this->getBackendIdForFolderUid($collection['id']);
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    continue;
                }
            }
            $this->_collections[$collection['id']] = $collection;
            $this->_logger->info(sprintf(
                '[%s] Loaded %s from the cache.',
                $this->_procid,
                $collection['serverid']));
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
        case 'hangingSync':
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
        case 'hangingSync':
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
     * Add a new populated collection array to the sync cache.
     *
     * @param array $collection        The collection array.
     * @param boolean $requireSyncKey  Attempt to read missing synckey from
     *                                 cache if true. If not found, set to 0.
     */
    public function addCollection(array $collection, $requireSyncKey = false)
    {
        if ($requireSyncKey && empty($collection['synckey'])) {
            $cached_collections = $this->_cache->getCollections(false);
            $collection['synckey'] = !empty($cached_collections[$collection['id']])
                ? $cached_collections[$collection['id']]['lastsynckey']
                : 0;

            if ($collection['synckey'] === 0) {
                $this->_logger->err(sprintf('[%s] Attempting to add a collection
                    to the sync cache while requiring a synckey, but no
                    synckey could be found. Most likely a client error in
                    requesting a collection during PING before it has issued a
                    SYNC.', $this->_procid));
                throw new Horde_ActiveSync_Exception_StateGone('Synckey required in Horde_ActiveSync_Collections::addCollection, but none was found.');
            }

            $this->_logger->info(sprintf(
                '[%s] Obtained synckey for collection %s from cache: %s',
                $this->_procid, $collection['id'], $collection['synckey']));
        }

        // Load the class if needed for EAS >= 12.1 and ensure we have the
        // backend folder id.
        if (empty($collection['class'])) {
            $collection['class'] = $this->getCollectionClass($collection['id']);
        }

        try {
            $collection['serverid'] = $this->getBackendIdForFolderUid($collection['id']);
        } catch (Horde_ActiveSync_Exception $e) {
            throw new Horde_ActiveSync_Exception_StateGone($e->getMessage());
        }

        $this->_collections[$collection['id']] = $collection;
        $this->_logger->info(sprintf(
            '[%s] Collection added to collection handler: collection: %s, synckey: %s.',
            $this->_procid,
            $collection['serverid'],
            !empty($collection['synckey']) ? $collection['synckey'] : 'NONE'));
    }

    /**
     * Translate an EAS folder uid into a backend serverid.
     *
     * @param $id  The uid.
     *
     * @return string  The backend server id.
     * @throws Horde_ActiveSync_Exception
     */
    public function getBackendIdForFolderUid($folderid)
    {
        // Always use RI for recipient cache.
        if ($folderid == 'RI') {
            return $folderid;
        }
        $folder = $this->_cache->getFolder($folderid);
        if ($folder) {
            return $folder['serverid'];
        } else {
            $this->_logger->err(
                sprintf('[%s] Horde_ActiveSync_Collections::getBackendIdForFolderUid failed because folder was not found in cache.',
                $this->_procid));
            throw new Horde_ActiveSync_Exception('Folder not found in cache.');
        }
    }

    /**
     * Translate a backend id E.g., INBOX into an EAS folder uid.
     *
     * @param string $folderid  The backend id.
     *
     * @return string The EAS uid.
     */
    public function getFolderUidForBackendId($folderid)
    {
        // Always use 'RI' for Recipient cache.
        if ($folderid == 'RI') {
            return $folderid;
        }
        $map = $this->_as->state->getFolderUidToBackendIdMap();
        if (empty($map[$folderid])) {
            return false;
        }

        return $map[$folderid];
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
     *
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
        return !empty($this->_collections[$collection_id]['getchanges']);
    }

    /**
     * Sets the default WINDOWSIZE.
     *
     * Note that this is really a ceiling on the number of TOTAL responses
     * that can be sent (including all collections). This method should be
     * renamed for 3.0
     *
     * @param integer $window  The windowsize
     */
    public function setDefaultWindowSize($window)
    {
        $this->_globalWindowSize = $window;
    }

    public function getDefaultWindowSize()
    {
        return $this->_globalWindowSize;
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
                $this->_logger->info(sprintf(
                    '[%s] Refreshing %s from the cache.',
                    $this->_procid, $id));
                $this->_collections[$id] = $collections[$id];
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
        if ($id == 'RI') {
            return $id;
        }
        if (isset($this->_cache->folders[$id]['class'])) {
            $class = $this->_cache->folders[$id]['class'];
            $this->_logger->info(sprintf(
                '[%s] Obtaining collection class of %s for collection id %s',
                $this->_procid, $class, $id));
            return $class;
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
            $this->_logger->info('No collections - looking in sync_cache.');
            $found = false;
            foreach ($this->_cache->getCollections() as $value) {
                if (isset($value['synckey'])) {
                    $this->_logger->info(sprintf(
                        '[%s] Found a syncable collection: %s : %s. Adding it to the collections object.',
                        $this->_procid, $value['serverid'], $value['synckey']));
                    $this->_collections[$value['id']] = $value;
                    $found = true;
                }
            }
            return $found;
        } elseif (empty($this->_collections)) {
            return false;
        }

        $this->_logger->info('Have syncable collections');

        return true;
    }

    /**
     * Set the looping sync heartbeat values.
     *
     * @param array $hb  An array containing one or both of: hbinterval, wait.
     */
    public function setHeartbeat(array $hb)
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
     * Return whether or not we want a looping sync. We can do a looping sync
     * if we have no imported changes AND we have either a hbinterval, wait,
     * or a shortSync.
     *
     * @return boolean  True if we want a looping sync, false otherwise.
     */
    public function canDoLoopingSync()
    {
        return $this->_hangingSync && !$this->_importedChanges && ($this->_shortSyncRequest || $this->_cache->hbinterval !== false || $this->_cache->wait !== false);
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
     * Prepare for a hierarchy sync.
     *
     * @param string $synckey  The current synckey from the client.
     *
     * @return array  An array of known folders.
     */
    public function initHierarchySync($synckey)
    {
        $this->_as->state->loadState(
            array(),
            $synckey,
            Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC);

        // Refresh the cache since it might have changed like e.g., if synckey
        // was empty.
        $this->_cache->loadCacheFromStorage();

        return $this->_as->state->getKnownFolders();
    }

    /**
     * Update/Add a folder in the hierarchy cache.
     *
     * @param Horde_ActiveSync_Message_Folder $folder  The folder object.
     * @param boolean $update  Update the state objects? @since 2.4.0
     */
    public function updateFolderinHierarchy(
        Horde_ActiveSync_Message_Folder $folder, $update = false)
    {
        $this->_cache->updateFolder($folder);
        $cols = $this->_cache->getCollections(false);
        $cols[$folder->serverid]['serverid'] = $folder->_serverid;
        $this->_cache->updateCollection($cols[$folder->serverid]);
        if ($update) {
            $this->_as->state->updateServerIdInState($folder->serverid, $folder->_serverid);
        }
    }

    /**
     * Delete a folder from the hierarchy cache.
     *
     * @param string $id  The folder's uid.
     */
    public function deleteFolderFromHierarchy($uid)
    {
        $this->_cache->deleteFolder($uid);
        $this->_as->state->removeState(array(
            'id' => $uid,
            'devId' => $this->_as->device->id,
            'user' => $this->_as->device->user));
    }

    /**
     * Return all know hierarchy changes.
     *
     * @return array  An array of changes.
     */
    public function getHierarchyChanges()
    {
        return $this->_as->state->getChanges();
    }

    /**
     * Validate and perform some sanity checks on the hierarchy changes before
     * being sent to the client.
     *
     * @param Horde_ActiveSync_Connector_Exporter $exporter  The exporter.
     * @param array $seenFolders                             An array of folders.
     */
    public function validateHierarchyChanges(Horde_ActiveSync_Connector_Exporter $exporter, array $seenFolders)
    {
        if ($this->_as->device->version < Horde_ActiveSync::VERSION_TWELVEONE ||
            count($exporter->changed)) {
            return;
        }

        // Remove unnecessary changes.
        foreach ($exporter->changed as $key => $folder) {
            if (isset($folder->serverid) &&
                $syncFolder = $this->_cache->getFolder($folder->serverid) &&
                in_array($folder->serverid, $seenfolders) &&
                $syncFolder['parentid'] == $folder->parentid &&
                $syncFolder['displayname'] == $folder->displayname &&
                $syncFolder['type'] == $folder->type) {

                $this->_logger->info(sprintf(
                    '[%s] Ignoring %s from changes because it contains no changes from device.',
                    $this->_procid,
                    $folder->serverid)
                );
                unset($exporter->changed[$key]);
                $exporter->count--;
            }
        }

        // Remove unnecessary deletions.
        foreach ($exporter->deleted as $key => $folder) {
            if (($sid = array_search($folder, $seenfolders)) === false) {
                $this->_logger->info(sprintf(
                    '[%s] Ignoring %s from deleted list because the device does not know it',
                    $this->_procid,
                    $folder)
                );
                unset($exporter->deleted[$key]);
                $exporter->count--;
            }
        }
    }

    /**
     * Update the hierarchy synckey in the cache.
     *
     * @param string $key  The new/existing synckey.
     */
    public function updateHierarchyKey($key)
    {
        $this->_cache->hierarchy = $key;
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
        if (empty($this->_collections)) {
            $this->_logger->err('No collections in collection handler, no PARTIAL allowed.');
            return false;
        }
        $this->_tempSyncCache = clone $this->_cache;
        $c = $this->_tempSyncCache->getCollections();
        foreach ($this->_collections as $key => $value) {
            // Collections from cache might not all have synckeys.
            if (!empty($c[$key])) {
                $v1 = $value;
                foreach ($v1 as $k => $o) {
                    if (is_null($o)) {
                        unset($v1[$k]);
                    }
                }
                unset($v1['id'], $v1['serverid'], $v1['clientids'], $v1['fetchids'],
                      $v1['getchanges'], $v1['changeids'], $v1['pingable'],
                      $v1['class'], $v1['synckey'], $v1['lastsynckey']);
                $v2 = $c[$key];
                foreach ($v2 as $k => $o) {
                    if (is_null($o)) {
                        unset($v2[$k]);
                    }
                }
                unset($v2['id'], $v2['serverid'], $v2['pingable'], $v2['class'], $v2['synckey'], $v2['lastsynckey']);
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
                $this->_tempSyncCache->removeCollection($key);

                // Remove keys from confirmed synckeys array and count them
                if (isset($value['synckey'])) {
                    if (isset($this->_cache->confirmed_synckeys[$value['synckey']])) {
                        $this->_logger->info(sprintf(
                            'Removed %s from confirmed_synckeys',
                            $value['synckey'])
                        );
                        $this->_cache->removeConfirmedKey($value['synckey']);
                        $this->_confirmedCount++;
                    }
                    $this->_synckeyCount++;
                }
            }
        }

        $csk = $this->_cache->confirmed_synckeys;
        if ($csk) {
            $this->_logger->info(sprintf(
                '[%s] Confirmed Synckeys contains %s',
                $this->_procid,
                serialize($csk))
            );
            $this->_logger->err('Some synckeys were not confirmed. Requesting full SYNC');
            $this->save();
            return false;
        }

        if ($this->_haveNoChangesInPartialSync()) {
                $this->_logger->warn(sprintf(
                    '[%s] Partial Request with completely unchanged collections. Request a full SYNC',
                    $this->_procid));
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
            ($this->_hangingSync && ($this->_cache->wait !== false || $this->_cache->hbinterval !== false));
    }

    /**
     * Return if we have no changes, but have requested a partial sync. A
     * partial sync must have either a wait, hbinterval, or some subset of
     * collections to be valid.
     *
     * @return boolean
     */
    protected function _haveNoChangesInPartialSync()
    {
        return $this->_synckeyCount > 0 &&
            $this->_unchangedCount == $this->_synckeyCount &&
            $this->_cache->wait == false && $this->_cache->hbinterval == false;
    }

    /**
     * Populate the collections data with missing data from the syncCache.
     */
    public function getMissingCollectionsFromCache()
    {
        if (empty($this->_tempSyncCache)) {
            throw new Horde_ActiveSync_Exception('Did not initialize the PARTIAL sync.');
        }

        // Update _collections with all data that was not sent, but we
        // have a synckey for in the sync_cache.
        foreach ($this->_tempSyncCache->getCollections() as $value) {
            // The collection might have been updated due to incoming
            // changes. Some clients send COMMANDS in a PARTIAL sync and
            // initializing the PARTIAL afterwards will overwrite the various
            // flags stored in $collection['id'][]
            if (!empty($this->_collections[$value['id']])) {
                continue;
            }
            $this->_logger->info(sprintf(
                'Using SyncCache State for %s',
                $value['serverid']
            ));
            if (empty($value['synckey'])) {
                $value['synckey'] = $value['lastsynckey'];
            }
            $this->_collections[$value['id']] = $value;
        }
    }

    /**
     * Check for an update FILTERTYPE
     *
     * @param string $id      The collection id to check
     * @param string $filter  The new filter value.
     *
     * @return boolean  True if filtertype passed, false if it has changed.
     */
    public function checkFilterType($id, $filter)
    {
        $cc = $this->_cache->getCollections();
        if (!empty($cc[$id]['filtertype']) &&
            !is_null($filter) &&
            $cc[$id]['filtertype'] != $filter) {

            $this->_cache->removeCollection($id, true);
            $this->_cache->save();
            $this->_logger->info('Invalidating SYNCKEY and removing collection - found updated filtertype');

            return false;
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
     * @param boolean $requireSyncKey  Require collection to have a synckey and
     *                                 throw exception if it's not present.
     *
     * @throws Horde_ActiveSync_Exception_InvalidRequest
     */
    public function initCollectionState(array $collection, $requireSyncKey = false)
    {
        // Clear the changes cache.
        $this->_changes = null;

        if (empty($collection['class'])) {
            if (!empty($this->_collections[$collection['id']])) {
                $collection['class'] = $this->_collections[$collection['id']]['class'];
            } else {
                throw new Horde_ActiveSync_Exception_FolderGone('Could not load collection class for ' . $collection['id']);
            }
        }

        // Get the backend serverid.
        if (empty($collection['serverid'])) {
            $f = $this->_cache->getFolder($collection['id']);
            $collection['serverid'] = $f['serverid'];
        }

        if ($requireSyncKey && empty($collection['synckey'])) {
            throw new Horde_ActiveSync_Exception_InvalidRequest(sprintf(
                '[%s] Empty synckey for %s.',
                $this->_procid, $collection['id']));
        }

        // Initialize the state
        $this->_logger->info(sprintf(
            '[%s] Initializing state for collection: %s, synckey: %s',
            $this->_procid,
            $collection['serverid'],
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

        $this->_logger->info(sprintf(
            'Waiting for changes for %s seconds',
            $heartbeat)
        );

        // If pinging, make sure we have pingable collections. Note we can't
        // filter on them here because the collections might change during the
        // loop below.
        if (!empty($options['pingable']) && !$this->havePingableCollections()) {
            $this->_logger->err('No pingable collections.');
            return self::COLLECTION_ERR_SERVER;
        }

        // Need to update AND SAVE the timestamp for race conditions to be
        // detected.
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
            // an error in refreshing them from the cache). Ideally this should
            // NEVER happen.
            if (!count($this->_collections)) {
                $this->_logger->err('NO COLLECTIONS! This should not happen!');
                return self::COLLECTION_ERR_SERVER;
            }

            // Check for WIPE request. If so, force a foldersync so it is
            // performed.
            if ($this->_as->provisioning != Horde_ActiveSync::PROVISIONING_NONE) {
                $rwstatus = $this->_as->state->getDeviceRWStatus($this->_as->device->id);
                if ($rwstatus == Horde_ActiveSync::RWSTATUS_PENDING || $rwstatus == Horde_ActiveSync::RWSTATUS_WIPED) {
                    return self::COLLECTION_ERR_FOLDERSYNC_REQUIRED;
                }
            }

            // Check each collection we are interested in.
            foreach ($this->_collections as $id => $collection) {

                // Initialize the collection's state data in the state handler.
                try {
                    $this->initCollectionState($collection, true);
                } catch (Horde_ActiveSync_Exception_StateGone $e) {
                    $this->_logger->notice(sprintf(
                        '[%s] State not found for %s. Continuing.',
                        $this->_procid,
                        $id)
                    );
                    if (!empty($options['pingable'])) {
                        return self::COLLECTION_ERR_PING_NEED_FULL;
                    }
                    $dataavailable = true;
                    $this->setGetChangesFlag($id);
                    continue;
                } catch (Horde_ActiveSync_Exception_InvalidRequest $e) {
                    // Thrown when state is unable to be initialized because the
                    // collection has not yet been synched, but was requested to
                    // be pinged.
                    $this->_logger->err(sprintf(
                        '[%s] Unable to initialize state for %s. Ignoring during pollForChanges: %s.',
                        $this->_procid,
                        $id,
                        $e->getMessage()));
                    continue;
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                    $this->_logger->warn('Folder gone for collection ' . $collection['id']);
                    return self::COLLECTION_ERR_FOLDERSYNC_REQUIRED;
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err('Error loading state: ' . $e->getMessage());
                    $this->_as->state->loadState(
                        array(),
                        null,
                        Horde_ActiveSync::REQUEST_TYPE_SYNC,
                        $id);
                    $this->setGetChangesFlag($id);
                    $dataavailable = true;
                    continue;
                }

                if (!empty($options['pingable']) && !$this->_cache->collectionIsPingable($id)) {
                    $this->_logger->notice(sprintf(
                        '[%s] Skipping %s because it is not PINGable.',
                        $this->_procid, $id));
                    continue;
                }

                try {
                    if ($cnt = $this->getCollectionChangeCount(true)) {
                        $dataavailable = true;
                        $this->setGetChangesFlag($id);
                        if (!empty($options['pingable'])) {
                            $this->_cache->setPingChangeFlag($id);
                        }
                    }
                } catch (Horde_ActiveSync_Exception_StaleState $e) {
                    $this->_logger->notice(sprintf(
                        '[%s] SYNC terminating and force-clearing device state: %s',
                        $this->_procid,
                        $e->getMessage())
                    );
                    $this->_as->state->loadState(
                        array(),
                        null,
                        Horde_ActiveSync::REQUEST_TYPE_SYNC,
                        $id);
                    $this->setGetChangesFlag($id);
                    $dataavailable = true;
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                    $this->_logger->notice(sprintf(
                        '[%s] SYNC terminating: %s',
                        $this->_procid,
                        $e->getMessage())
                    );
                    // If we are missing a folder, we should clear the PING
                    // cache also, to be sure it picks up any hierarchy changes
                    // since most clients don't seem smart enough to figure this
                    // out on their own.
                    $this->resetPingCache();
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
            }

            if (!empty($dataavailable)) {
                $this->_logger->info(sprintf(
                    '[%s] Found changes!',
                    $this->_procid)
                );
                break;
            }

            // Wait.
            $this->_logger->info(sprintf(
                '[%s] Sleeping for %s seconds.', $this->_procid, $interval));
            sleep ($interval);

            // Refresh the collections.
            $this->updateCollectionsFromCache();
        }

        // Check that no other Sync process already started
        // If so, we exit here and let the other process do the export.
        if ($this->checkStaleRequest()) {
            $this->_logger->info('Changes in cache determined during Sync Wait/Heartbeat, exiting here.');

            return self::COLLECTION_ERR_STALE;
        }

        $this->_logger->info(sprintf(
            '[%s] Looping Sync complete: DataAvailable: %s, DataImported: %s',
            $this->_procid,
            $dataavailable,
            $this->importedChanges)
        );

        return $dataavailable;
    }

    /**
     * Check if we have any pingable collections.
     *
     * @return boolean  True if we have collections marked as pingable.
     */
    public function havePingableCollections()
    {
        foreach (array_keys($this->_collections) as $id) {
            if ($this->_cache->collectionIsPingable($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Marks all loaded collections with a synckey as pingable.
     */
    public function updatePingableFlag()
    {
        $collections = $this->_cache->getCollections(false);
        foreach ($collections as $id => $collection) {
            if (!empty($this->_collections[$id]['synckey'])) {
                $this->_logger->info(sprintf(
                    'Setting collection %s (%s) PINGABLE.',
                    $collection['serverid'],
                    $id));
                $this->_cache->setPingableCollection($id);
            } else {
                $this->_logger->info(sprintf(
                    'UNSETTING collection %s (%s) PINGABLE flag.',
                    $collection['serverid'],
                    $id));
                $this->_cache->removePingableCollection($id);
            }
        }
    }

    /**
     * Force reset all collection's PINGABLE flag. Used to force client
     * to issue a non-empty PING request.
     *
     */
    public function resetPingCache()
    {
        $collections = $this->_cache->getCollections(false);
        foreach ($collections as $id => $collection) {
            $this->_logger->info(sprintf(
                'UNSETTING collection %s (%s) PINGABLE flag.',
                $collection['serverid'],
                $id));
            $this->_cache->removePingableCollection($id);
        }
    }

    /**
     * Return the any changes for the current collection, and cache them if
     * we are not PINGing.
     *
     * @param boolean $ping  True if this is a PING request, false otherwise.
     * @param array $ensure  An array of UIDs that should be sent in the
     *                       current response if possible, and not put off
     *                       because of a MOREAVAILABLE situation.
     *
     * @return array  The changes array.
     */
    public function getCollectionChanges($ping = false, array $ensure = array())
    {
        if (empty($this->_changes)) {
            $this->_changes = $this->_as->state->getChanges(array('ping' => $ping));
        }

        if (!empty($ensure)) {
            $this->_changes = $this->_reorderChanges($ensure);
        }

        return $this->_changes;
    }

    protected function _reorderChanges(array $ensure)
    {
        $changes = array();
        foreach ($this->_changes as $change) {
            if (array_search($change['id'], $ensure) !== false) {
                $this->_logger->info(sprintf(
                    'Placing %s at beginning of changes array.', $change['id']));
                array_unshift($changes, $change);
            } else {
                $changes[] = $change;
            }
        }

        return $changes;
    }

    /**
     * Return the count of the current collection's chagnes.
     *
     * @param boolean $ping  Only ping the collection if true.
     *
     * @return integer  The change count.
     */
    public function getCollectionChangeCount($ping = false)
    {
        if (empty($this->_changes)) {
            $this->getCollectionChanges($ping);
        }

        return count($this->_changes);
    }

    /**
     * Iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_collections);
    }

}
