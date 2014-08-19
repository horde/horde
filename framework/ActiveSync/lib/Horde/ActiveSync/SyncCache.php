<?php
/**
 * Horde_ActiveSync_SyncCache::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_SyncCache:: Wraps all functionality related to maintaining
 * the ActiveSync SyncCache.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal  Not intended for use outside of the Horde_ActiveSync library.
 *
 * @property array   $folders              The folders cache.
 * @property integer   $hbinterval         The heartbeat interval (in seconds).
 * @property integer   $wait               The wait interval (in minutes).
 * @property integer   $pingheartbeat      The heartbeat used in PING requests.
 * @property string   $hierarchy           The hierarchy synckey.
 * @property array   $confirmed_synckeys   Array of synckeys being confirmed during
 *                                       a looping sync.
 * @property integer   $lastuntil          Timestamp representing the last planned
 *                                       looping sync end time.
 * @property integer   $lasthbsyncstarted  Timestamp of the start of the last
 *                                       looping sync.
 * @property integer   $lastsyncendnormal  Timestamp of the last looping sync that
 *                                       ended normally.
 */
class Horde_ActiveSync_SyncCache
{
    /**
     * The cache data.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * The state driver
     *
     * @var Horde_ActiveSync_State_Base $state
     */
    protected $_state;

    /**
     * The username
     *
     * @var string
     */
    protected $_user;

    /**
     * The device id
     *
     * @var string
     */
    protected $_devid;

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Track dirty properties.
     *
     * @var array
     */
    protected $_dirty = array();

    /**
     * Process id for logging.
     *
     * @var integer
     */
    protected $_procid;

    /**
     * Constructor
     *
     * @param Horde_ActiveSync_State_Base $state  The state driver
     * @param string $devid                       The device id
     * @param string $user                        The username
     *
     * @return Horde_ActiveSync_SyncCache
     */
    public function __construct(
        Horde_ActiveSync_State_Base $state,
        $devid,
        $user,
        $logger = null)
    {
        $this->_state = $state;
        $this->_devid = $devid;
        $this->_user = $user;
        $this->_logger = $logger;
        $this->loadCacheFromStorage();
        $this->_procid = getmypid();
    }

    public function __get($property)
    {
        if (!$this->_isValidProperty($property)) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }

        return !empty($this->_data[$property]) ? $this->_data[$property] : false;
    }

    public function __set($property, $value)
    {
        if (!$this->_isValidProperty($property)) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        $this->_data[$property] = $value;
        $this->_dirty[$property] = true;
    }

    public function  __isset($property)
    {
        if (!$this->_isValidProperty($property)) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        return !empty($this->_data[$property]);
    }

    protected function _isValidProperty($property)
    {
        return in_array($property, array(
            'hbinterval', 'wait', 'hierarchy', 'confirmed_synckeys', 'timestamp',
            'lasthbsyncstarted', 'lastsyncendnormal', 'folders', 'pingheartbeat'));
    }

    /**
     * Validate the cache. Compares the cache timestamp with the current cache
     * timestamp in the state backend. If the timestamps are different, some
     * other request has modified the cache, so it should be invalidated.
     *
     * @param boolean $hb_only  If true, only validate the hb timestamps. @since 2.4.0
     *
     * @return boolean
     */
    public function validateCache($hb_only = false)
    {
        $cache = $this->_state->getSyncCache(
            $this->_devid,
            $this->_user,
            array('lasthbsyncstarted', 'timestamp'));

        if ((!$hb_only && $cache['timestamp'] > $this->_data['timestamp']) ||
            (!empty($cache['lasthbsyncstarted']) && $cache['lasthbsyncstarted'] > $this->_data['lasthbsyncstarted'])) {
            return false;
        }

        return true;
    }

    /**
     * Repopulate the cache data from storage.
     */
    public function loadCacheFromStorage()
    {
        $this->_data = $this->_state->getSyncCache($this->_devid, $this->_user);
        $this->_dirty = array();
    }

    /**
     * Perform some sanity checking on the various timestamps to ensure we
     * are in a valid state. Basically checks that we are not currently running
     * a looping sync and that the last looping sync ending normally.
     *
     * @return boolean
     * @deprecated  Not needed any longer. Remove in H6.
     */
    public function validateTimestamps()
    {
        if ((!empty($this->_data['lasthbsyncstarted']) && empty($this->_data['lastsyncendnormal'])) ||
            (!empty($this->_data['lasthbsyncstarted']) && !empty($this->_data['lastsyncendnormal']) &&
            ($this->_data['lasthbsyncstarted'] > $this->_data['lastsyncendnormal']))) {

            return false;
        }

        return true;
    }

    /**
     * Update the cache timestamp to the current time.
     */
    public function updateTimestamp()
    {
        $this->timestamp = time();
    }

    /**
     * Return all the collections in the syncCache.
     *
     * @param boolean $requireKey  If true, only return collections with an
     *                             existing synckey in the cache. Otherwise
     *                             return all collections.
     *
     * @return array
     */
    public function getCollections($requireKey = true)
    {
        $collections = array();
        foreach ($this->_data['collections'] as $key => $collection) {
            if (!$requireKey || ($requireKey && !empty($collection['lastsynckey']))) {
                $collection['id'] = $key;
                $collections[$key] = $collection;
            }
        }

        return $collections;
    }

    /**
     * Return the count of available collections in the cache
     *
     * @param integer  The count.
     */
    public function countCollections()
    {
        if (empty($this->_data['collections'])) {
            return 0;
        }

        return count($this->_data['collections']);
    }

    /**
     * Remove all collection data.
     */
    public function clearCollections()
    {
        $this->_logger->info(sprintf(
            '[%s] Clearing collections data from cache.',
            $this->_procid));
        $this->_data['collections'] = array();
        $this->_dirty['collections'] = true;
    }

    /**
     * Check for the existance of a specific collection in the cache.
     *
     * @param stirng $collectionid  The collection id to search for.
     *
     * @return boolean
     */
    public function collectionExists($collectionid)
    {
        return !empty($this->_data['collections'][$collectionid]);
    }

    /**
     * Set a specific collection to be PINGable.
     *
     * @param string  $id  The collection id.
     */
    public function setPingableCollection($id)
    {
        if (empty($this->_data['collections'][$id])) {
            throw new InvalidArgumentException('Collection does not exist');
        }
        $this->_data['collections'][$id]['pingable'] = true;
        $this->_markCollectionsDirty($id);
    }

    /**
     * Set a collection as non-PINGable.
     *
     * @param string $collectionid  The collection id.
     */
    public function removePingableCollection($id)
    {
        if (empty($this->_data['collections'][$id])) {
            $this->_logger->warn(sprintf(
                '[%s] Collection %s was asked to be removed from PINGABLE but does not exist.',
                $this->_procid,
                $id));
            return;
        }
        $this->_data['collections'][$id]['pingable'] = false;
        $this->_markCollectionsDirty($id);
    }

    /**
     * Check if a specified collection is PINGable.
     *
     * @param string $id  The collection id.
     *
     * @return boolean
     */
    public function collectionIsPingable($id)
    {
        return !empty($this->_data['collections'][$id]) &&
               !empty($this->_data['collections'][$id]['pingable']);
    }

    /**
     * Set the ping change flag on a collection. Indicatates that the last
     * PING was terminated with a change in this collection.
     *
     * @param string $id  The collection id.
     * @throws InvalidArgumentException
     * @since 2.3.0
     */
    public function setPingChangeFlag($id)
    {
        if (empty($this->_data['collections'][$id])) {
            throw new InvalidArgumentException('Collection does not exist.');
        }

        $this->_data['collections'][$id]['pingchange'] = true;
        $this->_markCollectionsDirty($id);
    }

    /**
     * Checks the status of the ping change flag. If true, the last PING request
     * detected a change in the specified collection.
     *
     * @param string $collectionid  The collection id to check.
     *
     * @return boolean
     * @since 2.3.0
     */
    public function hasPingChangeFlag($collectionid)
    {
        return !empty($this->_data['collections'][$collectionid]['pingchange']);
    }

    /**
     * Reset the specified collection's ping change flag.
     *
     * @param string  $id  The collectionid to reset.
     * @since 2.3.0
     */
    public function resetPingChangeFlag($id)
    {
        $this->_data['collections'][$id]['pingchange'] = false;
        $this->_markCollectionsDirty($id);
    }

    /**
     * Refresh the cached collections from the state backend.
     *
     */
    public function refreshCollections()
    {
        $syncCache = $this->_state->getSyncCache(
            $this->_devid,
            $this->_user,
            array('collections')
        );
        $cache_collections = !is_array($syncCache['collections']) ? array() : $syncCache['collections'];
        foreach ($cache_collections as $id => $cache_collection) {
            if (!isset($cache_collection['lastsynckey'])) {
                continue;
            }
            $cache_collection['id'] = $id;
            $cache_collection['synckey'] = $cache_collection['lastsynckey'];
            $this->_data['collections'][$id] = $cache_collection;
            $this->_markCollectionsDirty($id);
        }
        $this->_logger->info(sprintf(
            '[%s] SyncCache collections refreshed.',
            $this->_procid));
    }

    /**
     * Save the synccache to storage.
     */
    public function save()
    {
        // Iterate over the collections and persist the last known synckey.
        foreach ($this->_data['collections'] as $id => &$collection) {
            if (!empty($collection['synckey'])) {
                $collection['lastsynckey'] = $collection['synckey'];
                unset($collection['synckey']);
                $this->_markCollectionsDirty($id);
            }
        }
        $this->timestamp = time();
        $this->_state->saveSyncCache(
            $this->_data,
            $this->_devid,
            $this->_user,
            $this->_dirty);

        $this->_dirty = array();
    }

    /**
     * Add a new collection to the cache
     *
     * @param array $collection  The collection array
     */
    public function addCollection(array $collection)
    {
        $this->_data['collections'][$collection['id']] = array(
            'class' => $collection['class'],
            'windowsize' => isset($collection['windowsize']) ? $collection['windowsize'] : null,
            'deletesasmoves' => isset($collection['deletesasmoves']) ? $collection['deletesasmoves'] : null,
            'filtertype' => isset($collection['filtertype']) ? $collection['filtertype'] : null,
            'truncation' => isset($collection['truncation']) ? $collection['truncation'] : null,
            'rtftruncation' => isset($collection['rtftruncation']) ? $collection['rtftruncation'] : null,
            'mimesupport' => isset($collection['mimesupport']) ? $collection['mimesupport'] : null,
            'mimetruncation' => isset($collection['mimetruncation']) ? $collection['mimetruncation'] : null,
            'conflict' => isset($collection['conflict']) ? $collection['conflict'] : null,
            'bodyprefs' => isset($collection['bodyprefs']) ? $collection['bodyprefs'] : null,
            'serverid' => isset($collection['serverid']) ? $collection['serverid'] : $collection['id']
        );
        $this->_markCollectionsDirty($collection['id']);
    }

    /**
     * Remove a collection from the cache.
     *
     * @param string $id      The collection id.
     * @param boolean $purge  If true, completely remove the collection entry
     *                        otherwise, just resets the synckey.
     */
    public function removeCollection($id, $purge = true)
    {
        if (!$purge) {
            $this->_logger->info(sprintf(
                '[%s] Removing collection %s from SyncCache.',
                $this->_procid, $id)
            );
            unset($this->_data['collections'][$id]);
            $this->_dirty['collections'] = true;
        } elseif (!empty($this->_data['collections'][$id])) {
            $this->_data['collections'][$id]['synckey'] = '0';
            $this->_markCollectionsDirty($id);
        }
    }

    /**
     * Update the windowsize for the specified collection.
     *
     * @param string $id     The collection id.
     * @param integer $size  The updated windowsize.
     */
    public function updateWindowSize($id, $windowsize)
    {
        $this->_data['collections'][$id]['windowsize'] = $windowsize;
        $this->_markCollectionsDirty($id);
    }

    /**
     * Clear all synckeys from the known collections.
     *
     */
    public function clearCollectionKeys()
    {
        $this->_logger->info(sprintf(
            '[%s] Clearing all collection synckeys from the cache.',
            $this->_procid)
        );
        foreach ($this->_data['collections'] as $id => &$c) {
            unset($c['synckey']);
        }
        $this->_dirty['collections'] = true;
    }

    /**
     * Add a confirmed synckey to the cache.
     *
     * @param string $key  The synckey to add.
     */
    public function addConfirmedKey($key)
    {
        $this->_data['confirmed_synckeys'][$key] = true;
        $this->_dirty['confirmed_synckeys'] = true;
    }

    /**
     * Remove a confirmed sycnkey from the cache
     *
     * @param string $key  The synckey to remove.
     */
    public function removeConfirmedKey($key)
    {
        unset($this->_data['confirmed_synckeys'][$key]);
    }

    /**
     * Update a collection in the cache.
     *
     * @param array $collection  The collection data to add/update.
     * @param array $options     Options:
     *  - newsynckey: (boolean) Set the new synckey in the collection.
     *             DEFAULT: false (Do not set the new synckey).
     *  - unsetChanges: (boolean) Unset the GETCHANGES flag in the collection.
     *             DEFAULT: false (Do not unset the GETCHANGES flag).
     *  - unsetPingChangeFlag: (boolean) Unset the PINGCHANGES flag in the collection.
     *             DEFUALT: false (Do not uset the PINGCHANGES flag).
     *             @since 2.3.0
     */
    public function updateCollection(array $collection, array $options = array())
    {
        $options = array_merge(
            array('newsynckey' => false, 'unsetChanges' => false, 'unsetPingChangeFlag' => false),
            $options
        );
        if (!empty($collection['id'])) {
            if ($options['newsynckey']) {
                $this->_data['collections'][$collection['id']]['synckey'] = $collection['newsynckey'];
                $this->_markCollectionsDirty($collection['id']);
            } elseif (isset($collection['synckey'])) {
                $this->_data['collections'][$collection['id']]['synckey'] = $collection['synckey'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['class'])) {
                $this->_data['collections'][$collection['id']]['class'] = $collection['class'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['windowsize'])) {
                $this->_data['collections'][$collection['id']]['windowsize'] = $collection['windowsize'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['deletesasmoves'])) {
                $this->_data['collections'][$collection['id']]['deletesasmoves'] = $collection['deletesasmoves'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['filtertype'])) {
                $this->_data['collections'][$collection['id']]['filtertype'] = $collection['filtertype'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['truncation'])) {
                $this->_data['collections'][$collection['id']]['truncation'] = $collection['truncation'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['rtftruncation'])) {
                $this->_data['collections'][$collection['id']]['rtftruncation'] = $collection['rtftruncation'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['mimesupport'])) {
                $this->_data['collections'][$collection['id']]['mimesupport'] = $collection['mimesupport'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['mimetruncation'])) {
                $this->_data['collections'][$collection['id']]['mimetruncation'] = $collection['mimetruncation'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['conflict'])) {
                $this->_data['collections'][$collection['id']]['conflict'] = $collection['conflict'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['bodyprefs'])) {
                $this->_data['collections'][$collection['id']]['bodyprefs'] = $collection['bodyprefs'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['pingable'])) {
                $this->_data['collections'][$collection['id']]['pingable'] = $collection['pingable'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if (isset($collection['serverid'])) {
                $this->_data['collections'][$collection['id']]['serverid'] = $collection['serverid'];
                $this->_markCollectionsDirty($collection['id']);
            }
            if ($options['unsetChanges']) {
                unset($this->_data['collections'][$collection['id']]['getchanges']);
                $this->_markCollectionsDirty($collection['id']);
            }
            if ($options['unsetPingChangeFlag']) {
                unset($this->_data['collections'][$collection['id']]['pingchange']);
                $this->_markCollectionsDirty($collection['id']);
            }
        } else {
            $this->_logger->info(sprintf(
                '[%s] Collection without id found: %s',
                $this->_procid,
                serialize($collection))
            );
        }
    }

    /**
     * Validate the collections from the cache and fill in any missing values
     * from the folder cache.
     *
     * @param array $collections  A reference to an array of collections.
     */
    public function validateCollectionsFromCache(&$collections)
    {
        foreach ($collections as $key => $values) {
            if (!isset($values['class']) && isset($this->_data['folders'][$values['id']]['class'])) {
                $collections[$key]['class'] = $this->_data['folders'][$values['id']]['class'];
                $this->_markCollectionsDirty($key);
            }
            if (!isset($values['filtertype']) && isset($this->_data['collections'][$values['id']]['filtertype'])) {
                $collections[$key]['filtertype'] = $this->_data['collections'][$values['id']]['filtertype'];
                $this->_markCollectionsDirty($key);
            }
            if (!isset($values['mimesupport']) && isset($this->_data['collections'][$values['id']]['mimesupport'])) {
                $collections[$key]['mimesupport'] = $this->_data['collections'][$values['id']]['mimesupport'];
                $this->_markCollectionsDirty($key);
            }
            if (empty($values['bodyprefs']) && isset($this->_data['collections'][$values['id']]['bodyprefs'])) {
                $collections[$key]['bodyprefs'] = $this->_data['collections'][$values['id']]['bodyprefs'];
                $this->_markCollectionsDirty($key);
            }
            if (empty($values['truncation']) && isset($this->_data['collections'][$values['id']]['truncation'])) {
                $collections[$key]['truncation'] = $this->_data['collections'][$values['id']]['truncation'];
                $this->_markCollectionsDirty($key);
            }
            if (empty($values['mimetruncation']) && isset($this->_data['collections'][$values['id']]['mimetruncation'])) {
                $collections[$key]['mimetruncation'] = $this->_data['collections'][$values['id']]['mimetruncation'];
                $this->_markCollectionsDirty($key);
            }
            if (empty($values['serverid']) && isset($this->_data['collections'][$values['id']]['serverid'])) {
                $collections[$key]['serverid'] = $this->_data['collections'][$values['id']]['serverid'];
                $this->_markCollectionsDirty($key);
            }

            if (!isset($values['windowsize'])) {
                $collections[$key]['windowsize'] =
                    isset($this->_data['collections'][$values['id']]['windowsize'])
                        ? $this->_data['collections'][$values['id']]['windowsize']
                        : 100;
                $this->_markCollectionsDirty($key);
            }

            // in case the maxitems (windowsize) is above 512 or 0 it should be
            // interpreted as 512 according to specs.
            if ($collections[$key]['windowsize'] > Horde_ActiveSync_Request_Sync::MAX_WINDOW_SIZE ||
                $collections[$key]['windowsize'] == 0) {

                $collections[$key]['windowsize'] = self::MAX_WINDOW_SIZE;
                $this->_markCollectionsDirty($key);
            }

            if (isset($values['synckey']) &&
                $values['synckey'] == '0' &&
                isset($this->_data['collections'][$values['id']]['synckey']) &&
                $this->_data['collections'][$values['id']]['synckey'] != '0') {

                unset($this->_data['collections'][$values['id']]['synckey']);
                $this->_markCollectionsDirty($key);
            }

            if (!isset($values['pingable']) && isset($this->_data['collections'][$values['id']]['pingable'])) {
                $collections[$key]['pingable'] = $this->_data['collections'][$values['id']]['pingable'];
                $this->_markCollectionsDirty($key);
            }
        }
    }

    /**
     * Return the folders cache.
     *
     * @param array  The folders cache.
     */
    public function getFolders()
    {
        return count($this->_data['folders']) ? $this->_data['folders'] : array();
    }

    /**
     * Clear the folder cache
     *
     */
    public function clearFolders()
    {
        $this->_data['folders'] = array();
        $this->_dirty['folders'] = true;
    }

    /**
     * Refresh the folder cache from the backend.
     *
     * @since 2.18.0
     */
    public function refreshFolderCache()
    {
        $cache = $this->_state->getSyncCache($this->_devid, $this->_user);
        $this->_data['folders'] = $cache['folders'];
        $this->_dirty['folders'] = false;
    }

    /**
     * Update a folder entry in the cache.
     *
     * @param Horde_ActiveSync_Message_Folder $folder  The folder object.
     */
    public function updateFolder(Horde_ActiveSync_Message_Folder $folder)
    {
        switch ($folder->type) {
        case 7:
        case 15:
            $this->_data['folders'][$folder->serverid] = array('class' => 'Tasks');
            break;
        case 8:
        case 13:
            $this->_data['folders'][$folder->serverid] = array('class' => 'Calendar');
            break;
        case 9:
        case 14:
            $this->_data['folders'][$folder->serverid] = array('class' => 'Contacts');
            break;
        case 17:
        case 10:
            $this->_data['folders'][$folder->serverid] = array('class' => 'Notes');
            break;
        default:
            $this->_data['folders'][$folder->serverid] = array('class' => 'Email');
        }
        $this->_data['folders'][$folder->serverid]['serverid'] = $folder->_serverid;
        $this->_dirty['folders'] = true;
    }

    /**
     * Remove a folder from the cache
     *
     * @param string $folder  The folder id to remove.
     */
    public function deleteFolder($folder)
    {
        unset($this->_data['folders'][$folder]);
        unset($this->_data['collections'][$folder]);
        $this->_dirty['folders'] = true;
        $this->_markCollectionsDirty($folder);
    }

    /**
     * Return an entry from the folder cache.
     *
     * @param string $folder  The folder id to return.
     *
     * @return array|boolean  The folder cache array entry, false if not found.
     */
    public function getFolder($folder)
    {
        return !empty($this->_data['folders'][$folder]) ? $this->_data['folders'][$folder] : false;
    }

    /**
     * Delete the entire synccache from the backend.
     */
    public function delete()
    {
        $this->_state->deleteSyncCache($this->_devid, $this->_user);
        $this->_data = array();
        $this->_dirty = array();
    }

    /**
     * Mark specific collection as dirty, but only if the entire collection
     * data is not already marked dirty.
     *
     * @param boolean $id  The collection to mark dirty.
     */
    protected function _markCollectionsDirty($id)
    {
        if (isset($this->_dirty['collections']) && is_array($this->_dirty['collections'])) {
            $this->_dirty['collections'][$id] = true;
        } elseif (!isset($this->_dirty['collections']) || $this->_dirty['collections'] !== true) {
            $this->_dirty['collections'] = array();
            $this->_markCollectionsDirty($id);
        }
    }

}