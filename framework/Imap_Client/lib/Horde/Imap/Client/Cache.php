<?php

require_once 'Horde/Cache.php';
require_once 'Horde/Serialize.php';

/**
 * Horde_Imap_Client_Cache:: provides an interface to cache various data
 * retrieved from the IMAP server.
 *
 * Requires Horde_Cache and Horde_Serialize packages.
 *
 * REQUIRED Parameters:
 * ====================
 * 'driver' - (string) The Horde_Cache driver to use.
 * 'driver_params' - (string) The params to pass to the Horde_Cache driver.
 * 'hostspec' - (string) The IMAP hostspec.
 * 'username' - (string) The IMAP username.
 *
 * Optional Parameters:
 * ====================
 * 'compress' - (string) Compression to use on the cached data.
 *              Either false, 'gzip' or 'lzf'.
 *              DEFAULT: No compression
 * 'debug' - (resource) If set, will output debug information to the stream
 *           identified.
 *           DEFAULT: No debug output
 * 'lifetime' - (integer) The lifetime of the cache data (in seconds).
 *              DEFAULT: 1 week (604800 secs)
 * 'slicesize' - (integer) The slicesize to use.
 *               DEFAULT: 50
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Imap_Client
 */
class Horde_Imap_Client_Cache
{
    /**
     * The configuration params.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The Horde_Cache object.
     *
     * @var Horde_Cache
     */
    protected $_cacheOb;

    /**
     * The list of items to save on shutdown.
     *
     * @var array
     */
    protected $_save = array();

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
     * The mapping of UIDs to slices.
     *
     * @var array
     */
    protected $_slicemap = array();

    /**
     * Return a reference to a concrete Horde_Imap_Client_Cache instance.
     *
     * This method must be invoked as:
     *   $var = &IMP_MessageCache::singleton();
     *
     * @param array $params  The configuration parameters.
     *
     * @return Horde_Imap_Client_Cache  The global instance.
     */
    static public function &singleton($params = array())
    {
        static $instance = array();

        $sig = md5(serialize($params));

        if (!isset($instance[$sig])) {
            $instance[$sig] = new Horde_Imap_Client_Cache($params);
        }

        return $instance[$sig];
    }

    /**
     * Constructor.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $params  The configuration parameters.
     */
    function __construct($params = array())
    {
        if (empty($params['driver']) ||
            empty($params['driver_params']) ||
            empty($params['username']) ||
            empty($params['hostspec'])) {
            throw new Horde_Imap_Client_Exception('Missing required parameters to Horde_Imap_Client_Cache.');
        }

        /* Initialize the Cache object. */
        $this->_cacheOb = &Horde_Cache::singleton($params['driver'], $params['driver_params']);
        if (is_a($this->_cacheOb, 'PEAR_Error')) {
            throw new Horde_Imap_Client_Exception($this->_cacheOb->getMessage());
        }

        $compress = null;
        if (!empty($params['compress'])) {
            switch ($params['compress']) {
            case 'gzip':
                if (Horde_Serialize::hasCapability(SERIALIZE_GZ_COMPRESS)) {
                    $compress = SERIALIZE_GZ_COMPRESS;
                }
                break;

            case 'lzf':
                if (Horde_Serialize::hasCapability(SERIALIZE_LZF)) {
                    $compress = SERIALIZE_LZF;
                }
                break;
            }

            if (is_null($compress)) {
                throw new Horde_Imap_Client_Exception('Horde_Cache does not support the compression type given.');
            }
        }

        $this->_params = array(
            'compress' => $compress,
            'debug' => empty($params['debug']) ? false : $params['debug'],
            'hostspec' => $params['hostspec'],
            'lifetime' => empty($params['lifetime']) ? 604800 : intval($params['lifetime']),
            'slicesize' => empty($params['slicesize']) ? 50 : intval($params['slicesize']),
            'username' => $params['username']
        );
    }

    /**
     * Saves items to the cache at shutdown.
     */
    function __destruct()
    {
        $compress = $this->_params['compress'];
        $lifetime = $this->_params['lifetime'];

        foreach ($this->_save as $mbox => $uids) {
            $dptr = &$this->_data[$mbox];
            $sptr = &$this->_slicemap[$mbox];

            /* Get the list of slices to save. */
            foreach (array_intersect_key($sptr['slice'], array_flip($uids)) as $slice) {
                $data = array();

                /* Get the list of IDs to save. */
                foreach (array_keys($sptr['slice'], $slice) as $uid) {
                    /* Compress individual UID entries. We will worry about
                     * error checking when decompressing (cache data will
                     * automatically be invalidated then). */
                    if (isset($dptr[$uid])) {
                        $data[$uid] = ($compress && is_array($dptr[$uid])) ? Horde_Serialize::serialize($dptr[$uid], array(SERIALIZE_BASIC, $compress)) : $dptr[$uid];
                    }
                }

                $cid = $this->_getCID($mbox, $slice);
                if (empty($data)) {
                    // If empty, we can expire the cache.
                    $this->_cacheOb->expire($cid);
                } else {
                    $this->_cacheOb->set($cid, Horde_Serialize::serialize($data, SERIALIZE_BASIC), $lifetime);
                }
            }

            // Save the slicemap
            $this->_cacheOb->set($this->_getCID($mbox, 'slicemap'), Horde_Serialize::serialize($sptr, SERIALIZE_BASIC), $lifetime);
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
        /* Cache ID = "prefix | username | mailbox | hostspec | slice" */
        return 'horde_imap_client|' . $this->_params['username'] . '|' . $mailbox . '|' . $this->_params['hostspec'] . '|' . $slice;
    }

    /**
     * Get information from the cache.
     * Throws a Horde_Imap_Cache_Exception on error.
     *
     * @param string $mailbox    An IMAP mailbox string.
     * @param array $uids        The list of message UIDs to retrieve
     *                           information for. If empty, returns the list
     *                           of cached UIDs.
     * @param array $fields      An array of fields to retrieve.
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
        if (empty($uids)) {
            $this->_loadSliceMap($mailbox, $uidvalid);
            return array_keys($this->_slicemap[$mailbox]['slice']);
        }

        $ret_array = array();

        $this->_loadUIDs($mailbox, $uids, $uidvalid);
        if (!empty($this->_data[$mailbox])) {
            $fields = array_flip($fields);
            $ptr = &$this->_data[$mailbox];

            foreach ($uids as $val) {
                if (isset($ptr[$val])) {
                    $ret_array[$val] = array_intersect_key($ptr[$val], $fields);
                }
            }

            if ($this->_params['debug']) {
                fwrite($this->_params['debug'], 'Horde_Imap_Client_Cache: Retrieved from cache (mailbox: ' . $mailbox . '; UIDs: ' . implode(',', array_keys($ret_array)) . ")\n");
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
    public function set($mailbox, $data, $uidvalid = null)
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

            $d = &$this->_data[$mailbox];

            reset($data);
            while (list($k, $v) = each($data)) {
                reset($v);
                while (list($k2, $v2) = each($v)) {
                    $d[$k][$k2] = $v2;
                }
            }

            $this->_save[$mailbox] = isset($this->_save[$mailbox]) ? array_merge($this->_save[$mailbox], $save) : $save;

            /* Need to select slices now because we may need list of cached
             * UIDs before we save. */
            $slices = $this->_getCacheSlices($mailbox, $save, true);

            if ($this->_params['debug']) {
                fwrite($this->_params['debug'], 'Horde_Imap_Client_Cache: Stored in cache (mailbox: ' . $mailbox . '; UIDs: ' . implode(',', $save) . ")\n");
            }
        }
    }

    /**
     * Get metadata information for a mailbox.
     *
     * @param string $mailbox  An IMAP mailbox string.
     * @param array $entries   An array of entries to return. If empty,
     *                         returns all metadata.
     *
     * @return array  The requested metadata. Requested entries that do not
     *                exist will be undefined. The following entries are
     *                defaults and always present:
     * <pre>
     * 'uidvalid' - (integer) The UIDVALIDITY of the mailbox.
     * </pre>
     */
    public function getMetaData($mailbox, $entries = array())
    {
        $this->_loadSliceMap($mailbox);
        return empty($entries)
            ? $this->_slicemap[$mailbox]['data']
            : array_intersect_key($this->_slicemap[$mailbox]['data'], array_flip($entries));
    }

    /**
     * Set metadata information for a mailbox.
     *
     * @param string $mailbox  An IMAP mailbox string.
     * @param array $data      The list of data to save. The keys are the
     *                         metadata IDs, the values are the associated
     *                         data. The following labels are reserved:
     *                         'uidvalid'.
     */
    public function setMetaData($mailbox, $data = array())
    {
        if (!empty($data)) {
            unset($data['uidvalid']);
            $this->_loadSliceMap($mailbox);
            $this->_slicemap[$mailbox]['data'] = array_merge($this->_slicemap[$mailbox]['data'], $data);
            if (!isset($this->_save[$mailbox])) {
                $this->_save[$mailbox] = array();
            }
        }
    }

    /**
     * Delete messages in the cache.
     * Throws a Horde_Imap_Client_Exception on error.
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
                fwrite($this->_params['debug'], 'Horde_Imap_Client_Cache: Deleted messages from cache (mailbox: ' . $mailbox . '; UIDs: ' . implode(',', $save) . ")\n");
            }

            $this->_save[$mailbox] = isset($this->_save[$mailbox]) ? array_merge($this->_save[$mailbox], $save) : $save;
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
        $this->_loadSliceMap($mbox);
        foreach (array_keys(array_flip($this->_slicemap[$mbox]['slice'])) as $slice) {
            $this->_cacheOb->expire($this->_getCID($mbox, $slice));
        }
        $this->_cacheOb->expire($this->_getCID($mbox, 'slicemap'));
        unset($this->_data[$mbox], $this->_loaded[$mbox], $this->_save[$mbox], $this->_slicemap[$mbox]);

        if ($this->_params['debug']) {
            fwrite($this->_params['debug'], 'Horde_Imap_Client_Cache: Deleted mailbox from cache (mailbox: ' . $mbox . ")\n");
        }
    }

    /**
     * Load the given mailbox by regenerating from the cache.
     * Throws a Horde_Imap_Client_Exception on error (only if $uidvalid is
     * set).
     *
     * @param string $mailbox    The mailbox to load.
     * @param array $uids        The UIDs to load.
     * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
     */
    protected function _loadMailbox($mailbox, $uids, $uidvalid = null)
    {
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
        if (($data = $this->_cacheOb->get($cache_id, $this->_params['lifetime'])) === false) {
            return;
        }

        $data = Horde_Serialize::unserialize($data, SERIALIZE_BASIC);
        if (!is_array($data)) {
            return;
        }

        /* Remove old entries. */
        $ptr = &$this->_slicemap[$mailbox];
        if (isset($ptr['delete'][$slice])) {
            $data = array_diff_key($data, $ptr['delete'][$slice]);
            if ($this->_params['debug']) {
                fwrite($this->_params['debug'], 'Horde_Imap_Client_Cache: Deleted messages from cache (mailbox: ' . $mailbox . '; UIDs: ' . implode(',', $ptr['delete'][$slice]) . ")\n");
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
     * Throws a Horde_Imap_Client_Exception on error.
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
        $ptr = &$this->_slicemap[$mailbox];
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
        if (empty($this->_data[$mailbox])) {
            return;
        }

        $compress = $this->_params['compress'];
        $ptr = &$this->_data[$mailbox]['data'];
        $todelete = array();

        foreach ($uids as $val) {
            if (isset($ptr[$val]) && !is_array($ptr[$val])) {
                $success = false;
                if (!is_null($compress)) {
                    $res = Horde_Serialize::unserialize($ptr[$val], array($compress, SERIALIZE_BASIC));
                    if (!is_a($res, 'PEAR_Error')) {
                        $ptr[$val] = $res;
                        $success = true;
                    }
                }
                if (!$success) {
                    $todelete[] = $val;
                }
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
        if (!isset($this->_slicemap[$mailbox])) {
            if (($data = $this->_cacheOb->get($this->_getCID($mailbox, 'slicemap'), $this->_params['lifetime'])) !== false) {
                $slice = Horde_Serialize::unserialize($data, SERIALIZE_BASIC);
                if (is_array($slice)) {
                    $this->_slicemap[$mailbox] = $slice;
                }
            }
        }

        if (isset($this->_slicemap[$mailbox])) {
            $ptr = &$this->_slicemap[$mailbox]['data']['uidvalid'];
            if (is_null($ptr)) {
                $ptr = $uidvalid;
            } elseif (!is_null($uidvalid) && ($ptr != $uidvalid)) {
                $this->deleteMailbox($mailbox);
                throw new Horde_Imap_Client_Exception('UIDs have been invalidated', Horde_Imap_Client_Exception::CACHEUIDINVALID);
            }
        } else {
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
}
