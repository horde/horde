<?php
/**
 * An interface to cache various data retrieved from the IMAP server.
 *
 * Requires the horde/Cache package.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Cache
{
    /** Cache structure version. */
    const VERSION = 1;

    /**
     * The base driver object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_base;

    /**
     * The cache object.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * The working data for the current pageload.  All changes take place to
     * this data.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * The list of cache slices loaded.
     *
     * @var array
     */
    protected $_loaded = array();

    /**
     * The configuration params.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The mapping of UIDs to slices.
     *
     * @var array
     */
    protected $_slicemap = array();

    /**
     * The list of items to save on shutdown.
     *
     * @var array
     */
    protected $_save = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <ul>
     *  <li>
     *   REQUIRED Parameters:
     *   <ul>
     *    <li>
     *     baseob: (Horde_Imap_Client_Base) The driver object.
     *    </li>
     *    <li>
     *     cacheob: (Horde_Cache) The cache object to use.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Optional Parameters:
     *   <ul>
     *    <li>
     *     debug: (boolean) If true, will output debug information.
     *            DEFAULT: No debug output
     *    </li>
     *    <li>
     *     lifetime: (integer) The lifetime of the cache data (in seconds).
     *               DEFAULT: 1 week (604800 seconds)
     *    </li>
     *    <li>
     *     slicesize: (integer) The slicesize to use.
     *                DEFAULT: 50
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     */
    public function __construct(array $params = array())
    {
        $required = array('baseob', 'cacheob');
        foreach ($required as $val) {
            if (empty($params[$val])) {
                throw new InvalidArgumentException('Missing required parameter for ' . __CLASS__ . ': ' . $val);
            }
        }

        // Default parameters.
        $params = array_merge(array(
            'debug' => false,
            'lifetime' => 604800,
            'slicesize' => 50
        ), array_filter($params));

        $this->_base = $params['baseob'];
        $this->_params = array(
            'hostspec' => $this->_base->getParam('hostspec'),
            'port' => $this->_base->getParam('port'),
            'username' => $this->_base->getParam('username')
        );

        $this->_cache = $params['cacheob'];

        $this->_params['debug'] = (bool)$params['debug'];
        $this->_params['lifetime'] = intval($params['lifetime']);
        $this->_params['slicesize'] = intval($params['slicesize']);
    }

    /**
     * Saves items to the cache at shutdown.
     */
    public function __destruct()
    {
        $lifetime = $this->_params['lifetime'];

        foreach ($this->_save as $mbox => $uids) {
            $dptr = &$this->_data[$mbox];
            $sptr = &$this->_slicemap[$mbox];

            /* Get the list of slices to save. */
            foreach (array_intersect_key($sptr['slice'], array_flip($uids)) as $slice) {
                $data = array();

                /* Get the list of IDs to save. */
                foreach (array_keys($sptr['slice'], $slice) as $uid) {
                    if (isset($dptr[$uid])) {
                        $data[$uid] = $dptr[$uid];
                    }
                }

                $cid = $this->_getCID($mbox, $slice);
                if (empty($data)) {
                    // If empty, we can expire the cache.
                    $this->_cache->expire($cid);
                } else {
                    $this->_cache->set($cid, serialize($data), $lifetime);
                }
            }

            // Save the slicemap
            $this->_cache->set($this->_getCID($mbox, 'slicemap'), serialize($sptr), $lifetime);
        }
    }

    /**
     * Create the unique ID used to store the data in the cache.
     *
     * @param string $mailbox  The mailbox to cache.
     * @param string $slice    The cache slice.
     *
     * @return string  The cache ID (CID).
     */
    protected function _getCID($mailbox, $slice)
    {
        return implode('|', array(
            'horde_imap_client',
            $this->_params['username'],
            $mailbox,
            $this->_params['hostspec'],
            $this->_params['port'],
            $slice,
            self::VERSION
        ));
    }

    /**
     * Get information from the cache.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param array $uids        The list of message UIDs to retrieve
     *                           information for. If empty, returns the list
     *                           of cached UIDs.
     * @param array $fields      An array of fields to retrieve. If null,
     *                           returns all cached fields.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     *
     * @return array  An array of arrays with the UID of the message as the
     *                key (if found) and the fields as values (will be
     *                undefined if not found). If $uids is empty, returns the
     *                full list of cached UIDs.
     */
    public function get($mailbox, $uids = array(), $fields = array(),
                        $uidvalid = null)
    {
        $mailbox = strval($mailbox);

        if (empty($uids)) {
            $this->_loadSliceMap($mailbox, $uidvalid);
            return array_keys($this->_slicemap[$mailbox]['slice']);
        }

        $ret_array = array();

        $this->_loadUIDs($mailbox, $uids, $uidvalid);
        if (!empty($this->_data[$mailbox])) {
            if (!is_null($fields)) {
                $fields = array_flip($fields);
            }
            $ptr = &$this->_data[$mailbox];

            foreach ($uids as $val) {
                if (isset($ptr[$val])) {
                    $ret_array[$val] = is_null($fields)
                        ? $ptr[$val]
                        : array_intersect_key($ptr[$val], $fields);
                }
            }

            if ($this->_params['debug'] && !empty($ret_array)) {
                $ids = $this->_base->getIdsOb(array_keys($ret_array));
                $this->_base->writeDebug('CACHE: Retrieved messages (mailbox: ' . $mailbox . '; UIDs: ' . $ids->tostring_sort . ")\n", Horde_Imap_Client::DEBUG_INFO);
            }
        }

        return $ret_array;
    }

    /**
     * Store information in cache.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param array $data        The list of data to save. The keys are the
     *                           UIDs, the values are an array of information
     *                           to save. If empty, do a check to make sure
     *                           the uidvalidity is still valid.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    public function set($mailbox, $data, $uidvalid)
    {
        $save = array_keys($data);
        if (empty($save)) {
            $this->_loadSliceMap($mailbox, $uidvalid);
        } else {
            try {
                $this->_loadUIDs($mailbox, $save, $uidvalid);
            } catch (Horde_Imap_Client_Exception $e) {
                // Ignore invalidity - just start building the new cache
            }

            $mailbox = strval($mailbox);
            $d = &$this->_data[$mailbox];

            reset($data);
            while (list($k, $v) = each($data)) {
                reset($v);
                while (list($k2, $v2) = each($v)) {
                    $d[$k][$k2] = $v2;
                }
            }

            $this->_save[$mailbox] = isset($this->_save[$mailbox])
                ? array_merge($this->_save[$mailbox], $save)
                : $save;

            /* Need to select slices now because we may need list of cached
             * UIDs before we save. */
            $slices = $this->_getCacheSlices($mailbox, $save, true);

            if ($this->_params['debug']) {
                $ids = $this->_base->getIdsOb($save);
                $this->_base->writeDebug('CACHE: Stored messages (mailbox: ' . $mailbox . '; UIDs: ' . $ids->tostring_sort . ")\n", Horde_Imap_Client::DEBUG_INFO);
            }
        }
    }

    /**
     * Get metadata information for a mailbox.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     * @param array $entries     An array of entries to return. If empty,
     *                           returns all metadata.
     *
     * @return array  The requested metadata. Requested entries that do not
     *                exist will be undefined. The following entries are
     *                defaults and always present:
     *   - uidvalid: (integer) The UIDVALIDITY of the mailbox.
     */
    public function getMetaData($mailbox, $uidvalid = null, $entries = array())
    {
        $this->_loadSliceMap($mailbox, $uidvalid);
        return empty($entries)
            ? $this->_slicemap[strval($mailbox)]['data']
            : array_intersect_key($this->_slicemap[strval($mailbox)]['data'], array_flip($entries));
    }

    /**
     * Set metadata information for a mailbox.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     * @param array $data        The list of data to save. The keys are the
     *                           metadata IDs, the values are the associated
     *                           data. The following labels are reserved:
     *                           'uidvalid'.
     */
    public function setMetaData($mailbox, $uidvalid, array $data = array())
    {
        if (!empty($data)) {
            $mailbox = strval($mailbox);
            unset($data['uidvalid']);
            $this->_loadSliceMap($mailbox, $uidvalid);
            $this->_slicemap[$mailbox]['data'] = array_merge($this->_slicemap[$mailbox]['data'], $data);
            if (!isset($this->_save[$mailbox])) {
                $this->_save[$mailbox] = array();
            }
        }
    }

    /**
     * Delete messages in the cache.
     *
     * @param string $mailbox  An IMAP mailbox string.
     * @param array $uids      The list of message UIDs to delete.
     */
    public function deleteMsgs($mailbox, $uids)
    {
        if (empty($uids)) {
            return;
        }

        $this->_loadSliceMap($mailbox);

        $mailbox = strval($mailbox);
        $save = array();
        $slicemap = &$this->_slicemap[$mailbox];
        $todelete = &$slicemap['delete'];

        foreach ($uids as $id) {
            if (isset($slicemap['slice'][$id])) {
                if (isset($this->_data[$mailbox][$id])) {
                    $save[] = $id;
                    unset($this->_data[$mailbox][$id]);
                } else {
                    $slice = $slicemap['slice'][$id];
                    if (!isset($todelete[$slice])) {
                        $todelete[$slice] = array();
                    }
                    $todelete[$slice][] = $id;
                }
                unset($this->_save[$mailbox][$id], $slicemap['slice'][$id]);
            }
        }

        if (!empty($save)) {
            if ($this->_params['debug']) {
                $ids = $this->_base->getIdsOb($save);
                $this->_base->writeDebug('CACHE: Deleted messages (mailbox: ' . $mailbox . '; UIDs: ' . $ids->tostring_sort . ")\n", Horde_Imap_Client::DEBUG_INFO);
            }

            $this->_save[$mailbox] = isset($this->_save[$mailbox])
                ? array_merge($this->_save[$mailbox], $save)
                : $save;
        } elseif (!isset($this->_save[$mailbox])) {
            $this->_save[$mailbox] = array();
        }
    }

    /**
     * Delete a mailbox from the cache.
     *
     * @param string $mbox  The mailbox to delete.
     */
    public function deleteMailbox($mbox)
    {
        $mbox = strval($mbox);
        $this->_loadSliceMap($mbox);
        $this->_deleteMailbox($mbox);
    }

    /**
     * Delete a mailbox from the cache.
     *
     * @param string $mbox  The mailbox to delete.
     */
    protected function _deleteMailbox($mbox)
    {
        foreach (array_keys(array_flip($this->_slicemap[$mbox]['slice'])) as $slice) {
            $this->_cache->expire($this->_getCID($mbox, $slice));
        }
        $this->_cache->expire($this->_getCID($mbox, 'slicemap'));
        unset($this->_data[$mbox], $this->_loaded[$mbox], $this->_save[$mbox], $this->_slicemap[$mbox]);

        if ($this->_params['debug']) {
            $this->_base->writeDebug('CACHE: Deleted mailbox (mailbox: ' . $mbox . ")\n", Horde_Imap_Client::DEBUG_INFO);
        }
    }

    /**
     * Load the given mailbox by regenerating from the cache.
     * Throws an exception on error ONLY if $uidvalid is set.
     *
     * @param string $mailbox    The mailbox to load.
     * @param array $uids        The UIDs to load.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    protected function _loadMailbox($mailbox, $uids, $uidvalid = null)
    {
        $mailbox = strval($mailbox);

        if (!isset($this->_data[$mailbox])) {
            $this->_data[$mailbox] = array();
        }

        $this->_loadSliceMap($mailbox, $uidvalid);

        foreach (array_keys(array_flip($this->_getCacheSlices($mailbox, $uids))) as $val) {
            $this->_loadSlice($mailbox, $val);
        }
    }

    /**
     * Load a cache slice into memory.
     *
     * @param string $mailbox  The mailbox to load.
     * @param integer $slice   The slice to load.
     */
    protected function _loadSlice($mailbox, $slice)
    {
        /* Get the unique cache identifier for this mailbox. */
        $cache_id = $this->_getCID($mailbox, $slice);

        if (!empty($this->_loaded[$cache_id])) {
            return;
        }
        $this->_loaded[$cache_id] = true;

        /* Attempt to grab data from the cache. */
        if (($data = $this->_cache->get($cache_id, $this->_params['lifetime'])) === false) {
            return;
        }

        $data = @unserialize($data);
        if (!is_array($data)) {
            return;
        }

        /* Remove old entries. */
        $mailbox = strval($mailbox);
        $ptr = &$this->_slicemap[$mailbox];
        if (isset($ptr['delete'][$slice])) {
            $data = array_diff_key($data, $ptr['delete'][$slice]);
            if ($this->_params['debug']) {
                $ids = $this->_base->getIdsOb($ptr['delete'][$slice]);
                $this->_base->writeDebug('CACHE: Deleted messages (mailbox: ' . $mailbox . '; UIDs: ' . $ids->tostring_sort . ")\n", Horde_Imap_Client::DEBUG_INFO);
            }
            unset($ptr['delete'][$slice]);

            /* Check if slice has less than 5 entries. */
            $save = array();
            if ((count($data) < 5) &&
                ($slice != intval($ptr['count'] / $this->_params['slicesize']))) {
                $save = array_keys($data);
                $ptr['slice'] = array_diff_key($ptr['slice'], $save);
            }

            if (!isset($this->_save[$mailbox])) {
                $this->_save[$mailbox] = array();
            }
            if (!empty($save)) {
                $this->_save[$mailbox] = array_merge($this->_save[$mailbox], $save);
            }
        }

        $this->_data[$mailbox] += $data;
    }

    /**
     * Given a list of UIDs, determine the slices that need to be loaded.
     *
     * @param string $mailbox  The mailbox.
     * @param array $uids      A list of UIDs.
     * @param boolean $set     Set the slice information in $_slicemap?
     *
     * @return array  UIDs as the keys, the slice number as the value.
     */
    protected function _getCacheSlices($mailbox, $uids, $set = false)
    {
        $this->_loadSliceMap($mailbox);

        $lookup = array();
        $ptr = &$this->_slicemap[strval($mailbox)];
        $slicesize = $this->_params['slicesize'];

        if (!empty($uids)) {
            if ($set) {
                $pcount = &$ptr['count'];
            } else {
                $pcount = $ptr['count'];
            }

            foreach ($uids as $val) {
                if (isset($ptr['slice'][$val])) {
                    $slice = $ptr['slice'][$val];
                } else {
                    $slice = intval($pcount++ / $slicesize);
                    if ($set) {
                        $ptr['slice'][$val] = $slice;
                    }
                }
                $lookup[$val] = $slice;
            }
        }

        return $lookup;
    }

    /**
     * Given a list of UIDs, unpacks the messages from stored cache data and
     * returns the list of UIDs that exist in the cache.
     *
     * @param string $mailbox    The mailbox.
     * @param array $uids        The list of UIDs to load.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    protected function _loadUIDs($mailbox, $uids, $uidvalid)
    {
        $this->_loadMailbox($mailbox, $uids, $uidvalid);
        if (empty($this->_data[strval($mailbox)])) {
            return;
        }

        $ptr = &$this->_data[strval($mailbox)];
        $todelete = array();

        foreach ($uids as $val) {
            if (!isset($ptr[$val])) {
                $todelete[] = $val;
            }
        }

        if (!empty($todelete)) {
            $this->deleteMsgs($mailbox, $todelete);
        }
    }

    /**
     * Load the slicemap for a given mailbox.  The slicemap contains
     * the uidvalidity information, the UIDs->slice lookup table, and any
     * metadata that needs to be saved for the mailbox.
     *
     * @param string $mailbox    The mailbox.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    protected function _loadSliceMap($mailbox, $uidvalid = null)
    {
        $mailbox = strval($mailbox);

        if (!isset($this->_slicemap[$mailbox])) {
            if (($data = $this->_cache->get($this->_getCID($mailbox, 'slicemap'), $this->_params['lifetime'])) !== false) {
                $slice = @unserialize($data);
                if (is_array($slice)) {
                    $this->_slicemap[$mailbox] = $slice;
                }
            }
        }

        if (isset($this->_slicemap[$mailbox])) {
            $ptr = &$this->_slicemap[$mailbox];
            if (is_null($ptr['data']['uidvalid'])) {
                $ptr['data']['uidvalid'] = $uidvalid;
                return;
            } elseif (!is_null($uidvalid) &&
                ($ptr['data']['uidvalid'] != $uidvalid)) {
                $this->_deleteMailbox($mailbox);
            } else {
                return;
            }
        }

        $this->_slicemap[$mailbox] = array(
            // Tracking count for purposes of determining slices
            'count' => 0,
            // Metadata storage
            // By default includes UIDVALIDITY of mailbox.
            'data' => array('uidvalid' => $uidvalid),
            // UIDs to delete
            'delete' => array(),
            // The slice list.
            'slice' => array()
        );
    }

}
