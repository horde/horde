<?php
/**
 * Horde_ActiveSync_SyncCache::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
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
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property array folders              The folders cache.
 * @property integer hbinterval         The heartbeat interval (in seconds).
 * @property integer wait               The wait interval (in minutes).
 * @property integer pingheartbeat      The heartbeat used in PING requests.
 * @property string hierarchy           The hierarchy synckey.
 * @property array confirmed_synckeys   Array of synckeys being confirmed during
 *                                      a looping sync.
 * @property integer lastuntil          Timestamp representing the last planned
 *                                      looping sync end time.
 * @property integer lasthbsyncstarted  Timestamp of the start of the last
 *                                      looping sync.
 * @property integer lastsyncendnormal  Timestamp of the last looping sync that
 *                                      ended normally.
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
        $user)
    {
        $this->_state = $state;
        $this->_devid = $devid;
        $this->_user = $user;
        $this->_data = $state->getSyncCache($devid, $user);
    }

    public function __get($property)
    {
        if (!$this->_isValidProperty($property)) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        return $this->_data[$property];
    }

    public function __set($property, $value)
    {
        if (!$this->_isValidProperty($property)) {
            throw new InvalidArgumentException($property . ' is not a valid property');
        }
        $this->_data[$property] = $value;
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
            'hbinterval', 'wait', 'hierarchy', 'confirmed_synckeys', 'lastuntil',
            'lasthbsyncstarted', 'lastsyncendnormal', 'folders', 'pingheartbeat'));
    }
    /**
     * Validate the cache. Compares the cache timestamp with the current cache
     * timestamp in the state backend. If the timestamps are different, some
     * other request has modified the cache, so it should be invalidated.
     *
     * @return boolean
     */
    public function validateCache()
    {
        $cache = $this->_state->getSyncCache($this->_devid, $this->_user);
        if ($cache['timestamp'] > $this->_data['timestamp']) {
            return false;
        }

        return true;
    }

    /**
     * Perform some sanity checking on the various timestamps to ensure we
     * are in a valid state.
     *
     * @return boolean
     */
    public function validateTimestamps()
    {
        if ((!empty($this->_data['lasthbsyncstarted']) && empty($this->_data['lastsyncendnormal'])) ||
            (!empty($this->_data['lasthbsyncstarted']) && !empty($this->_data['lastsyncendnormal']) &&
            ($this->_data['lasthbsyncstarted'] > $this->_data['lastsyncendnormal']))) {

            return false;
        }

        if ($this->_data['lastuntil'] &&
            time() < $this->_data['lastuntil']) {

            return false;
        }

        return true;
    }

    /**
     * Update the cache timestamp to the current time.
     */
    public function updateTimestamp()
    {
        $this->_data['timestamp'] = time();
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
        foreach ($this->_data['collections'] as $key => $value) {
            $collection = $value;
            if (!$requireKey || ($requireKey && isset($collection['synckey']))) {
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
        $this->_data['collections'] = array();
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
     * @param string  $collectionid  The collection id.
     */
    public function setPingableCollection($collectionid)
    {
        if (empty($this->_data['collections'][$collectionid])) {
            throw new InvalidArgumentException('Collection does not exist');
        }
        $this->_data['collections'][$collectionid]['pingable'] = true;
    }

    /**
     * Set a collection as non-PINGable.
     *
     * @param string $collectionid  The collection id.
     */
    public function removePingableCollection($collectionid)
    {
         if (empty($this->_data['collections'][$collectionid])) {
            throw new InvalidArgumentException('Collection does not exist');
        }
        $this->_data['collections'][$collectionid]['pingable'] = false;
    }

    /**
     * Check if a specified collection is PINGable.
     *
     * @param string  The collection id.
     *
     * @return boolean
     */
    public function collectionIsPingable($collectionid)
    {
        return !empty($this->_data['collections'][$collectionid]) &&
               !empty($this->_data['collections'][$collectionid]['pingable']);
    }

    /**
     * Refresh the cached collections from the state backend.
     *
     */
    public function refreshCollections()
    {
        $syncCache = $this->_state->getSyncCache(
            $this->_devid, $this->_user);
        $this->_data['collections'] = array();
        $cache_collections = $syncCache['collections'];
        foreach ($cache_collections as $id => $cache_collection) {
            if (!isset($cache_collection['lastsynckey'])) {
                continue;
            }
            $cache_collection['id'] = $id;
            $cache_collection['synckey'] = $cache_collection['lastsynckey'];
            $this->_data['collections'][$id] = $cache_collection;
        }
    }

    /**
     * Save the synccache to storage.
     */
    public function save()
    {
        $this->_data['timestamp'] = time();
        $this->_state->saveSyncCache(
            $this->_data,
            $this->_devid,
            $this->_user);
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
            'bodyprefs' => isset($collection['bodyprefs']) ? $collection['bodyprefs'] : null
        );
    }

    /**
     * Remove a collection from the cache.
     *
     * @param string $id  The collection id.
     */
    public function removeCollection($id)
    {
        unset($this->_data['collections'][$id]);
    }

    /**
     * Update the windowsize for the specified collection.
     *
     * @param string $collection  The collection id.
     * @param integer $size       The updated windowsize.
     */
    public function updateWindowSize($collection, $windowsize)
    {
        $this->_data['collections'][$collection]['windowsize'] = $windowsize;
    }

    /**
     * Clear all synckeys from the known collections.
     *
     */
    public function clearCollectionKeys()
    {
        foreach ($this->_data['collections'] as &$c) {
            unset($c['synckey']);
        }
    }

    /**
     * Add a confirmed synckey to the cache.
     *
     * @param string $key  The synckey to add.
     */
    public function addConfirmedKey($key)
    {
        $this->_data['confirmed_synckeys'][$key] = true;
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
     *
     */
    public function updateCollection(array $collection, array $options = array())
    {
        $options = array_merge(
            array('newsynckey' => false, 'unsetChanges' => false),
            $options
        );
        if (!empty($collection['id'])) {
            if ($options['newsynckey']) {
                $this->_data['collections'][$collection['id']]['synckey'] = $collection['newsynckey'];
            } elseif (isset($collection['synckey'])) {
                $this->_data['collections'][$collection['id']]['synckey'] = $collection['synckey'];
            }
            if (isset($collection['class'])) {
                $this->_data['collections'][$collection['id']]['class'] = $collection['class'];
            }
            if (isset($collection['windowsize'])) {
                $this->_data['collections'][$collection['id']]['windowsize'] = $collection['windowsize'];
            }
            if (isset($collection['deletesasmoves'])) {
                $this->_data['collections'][$collection['id']]['deletesasmoves'] = $collection['deletesasmoves'];
            }
            if (isset($collection['filtertype'])) {
                $this->_data['collections'][$collection['id']]['filtertype'] = $collection['filtertype'];
            }
            if (isset($collection['truncation'])) {
                $this->_data['collections'][$collection['id']]['truncation'] = $collection['truncation'];
            }
            if (isset($collection['rtftruncation'])) {
                $this->_data['collections'][$collection['id']]['rtftruncation'] = $collection['rtftruncation'];
            }
            if (isset($collection['mimesupport'])) {
                $this->_data['collections'][$collection['id']]['mimesupport'] = $collection['mimesupport'];
            }
            if (isset($collection['mimetruncation'])) {
                $this->_data['collections'][$collection['id']]['mimetruncation'] = $collection['mimetruncation'];
            }
            if (isset($collection['conflict'])) {
                $this->_data['collections'][$collection['id']]['conflict'] = $collection['conflict'];
            }
            if (isset($collection['bodyprefs'])) {
                $this->_data['collections'][$collection['id']]['bodyprefs'] = $collection['bodyprefs'];
            }
            if (isset($collection['pingable'])) {
                $this->_data['collections'][$collection['id']]['pingable'] = $collection['pingable'];
            }
            if ($options['unsetChanges']) {
                unset($this->_data['collections'][$collection['id']]['getchanges']);
            }

        } else {
            $this->_logger->debug(sprintf(
                'Collection without id found: %s',
                print_r($collection, true))
            );
        }
    }

    /**
     * Validate the collections from the cache and fill in any missing values
     * from the cache.
     *
     * @param array $collections  A reference to an array of collections.
     */
    public function validateCollectionsFromCache(&$collections)
    {
        foreach ($collections as $key => $values) {
            if (!isset($values['class']) && isset($this->_data['folders'][$values['id']]['class'])) {
                $collections[$key]['class'] = $this->_data['folders'][$values['id']]['class'];
            }
            if (!isset($values['filtertype']) && isset($this->_data['collections'][$values['id']]['filtertype'])) {
                $collections[$key]['filtertype'] = $this->_data['collections'][$values['id']]['filtertype'];
            }
            if (!isset($values['mimesupport']) && isset($this->_data['collections'][$values['id']]['mimesupport'])) {
                $collections[$key]['mimesupport'] = $this->_data['collections'][$values['id']]['mimesupport'];
            }
            if (!isset($values['bodyprefs']) && isset($this->_data['collections'][$values['id']]['bodyprefs'])) {
                $collections[$key]['bodyprefs'] = $this->_data['collections'][$values['id']]['bodyprefs'];
            }

            if (!isset($values['windowsize'])) {
                $collections[$key]['windowsize'] =
                    isset($this->_data['collections'][$values['id']]['windowsize'])
                        ? $this->_data['collections'][$values['id']]['windowsize']
                        : 100;
            }

            // in case the maxitems (windowsize) is above 512 or 0 it should be
            // interpreted as 512 according to specs.
            if ($collections[$key]['windowsize'] > Horde_ActiveSync_Request_Sync::MAX_WINDOW_SIZE ||
                $collections[$key]['windowsize'] == 0) {

                $collections[$key]['windowsize'] = self::MAX_WINDOW_SIZE;
            }

            if (isset($values['synckey']) &&
                $values['synckey'] == '0' &&
                isset($this->_data['collections'][$values['id']]['synckey']) &&
                $this->_data['collections'][$values['id']]['synckey'] != '0') {

                unset($this->_data['collections'][$values['id']]['synckey']);
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
    }

    /**
     * Update a folder entry in the cache.
     *
     * @param Horde_ActiveSync_Message_Folder $folder  The folder object.
     */
    public function updateFolder(Horde_ActiveSync_Message_Folder $folder)
    {
        $this->_data['folders'][$folder->serverid]['parentid'] = $folder->parentid;
        $this->_data['folders'][$folder->serverid]['displayname'] = $folder->displayname;
        switch ($folder->type) {
        case 7:
        case 15:
            $this->_data['folders'][$folder->serverid]['class'] = 'Tasks';
            break;
        case 8:
        case 13:
            $this->_data['folders'][$folder->serverid]['class'] = 'Calendar';
            break;
        case 9:
        case 14:
            $this->_data['folders'][$folder->serverid]['class'] = 'Contacts';
            break;
        case 17:
        case 10:
            $this->_data['folders'][$folder->serverid]['class'] = 'Notes';
            break;
        default:
            $this->_data['folders'][$folder->serverid]['class'] = 'Email';
        }
        $this->_data['folders'][$folder->serverid]['type'] = $folder->type;
        $this->_data['folders'][$folder->serverid]['filtertype'] = '0';
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
    }

    /**
     * Return an entry from the folder cache.
     *
     * @param string $folder  The folder id to return.
     *
     * @return array  The folder cache array entry.
     */
    public function getFolder($folder)
    {
        return $this->_data['folders'][$folder];
    }

    /**
     * Delete the entire synccache from the backend.
     */
    public function delete()
    {
        $this->_state->deleteSyncCache($this->_devid, $this->_user);
        $this->_data = array();
    }

}