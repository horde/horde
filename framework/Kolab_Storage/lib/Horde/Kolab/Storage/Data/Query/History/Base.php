<?php
/**
 * The hook that updates the Horde history information once data gets
 * synchronized with the Kolab backend.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The hook that updates the Horde history information once data gets
 * synchronized with the Kolab backend.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Query_History_Base
implements Horde_Kolab_Storage_Data_Query_History
{
    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    protected $factory;

    /**
     * The queriable data.
     *
     * @var Horde_Kolab_Storage_Data
     */
    protected $data;

    /**
     * The history handler.
     *
     * @var Horde_History
     */
    protected $history;

    /**
     * The precomputed history prefix
     *
     * @var string Cached history prefix string
     */
    private $_prefix;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data $data   The queriable data.
     * @param array                    $params Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_Data $data,
                                $params)
    {
        $this->data = $data;
        $this->factory = $params['factory'];
        $this->history = $this->factory->createHistory($data->getAuth());
    }

    /**
     * Synchronize the preferences information with the information from the
     * backend.
     *
     * @param array $params Additional parameters:
     *   - changes: (array)  An array of arrays keyed by backend id containing
     *                       information about each change.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $user = $this->data->getAuth();
        $folder = $this->data->getPath();

        // check if IMAP uidvalidity changed
        $is_reset = !empty($params['is_reset']);

        if (isset($params['changes']) && !$is_reset) {
            $added = $params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED];
            $deleted = $params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED];

            if (!empty($added) || !empty($deleted)) {
                $prefix = $this->_constructHistoryPrefix();
                // Abort history update if we can't determine the prefix.
                // Otherwise we pollute the database with useless entries.
                if (empty($prefix)) {
                    return;
                }

                Horde::log(sprintf('History: Incremental update for user: %s, folder: %s, prefix: %s', $user, $folder, $prefix), 'NOTICE');
            }

            foreach ($added as $bid => $object) {
                $this->_updateLog($prefix.$object['uid'], $bid);
            }
            foreach ($deleted as $bid => $object_uid) {
                // Check if the object is really gone from the folder.
                // Otherwise we just deleted a duplicated object or updated the original one.
                // (An update results in an ADDED + DELETED folder action)
                if ($this->data->objectIdExists($object_uid) == true) {
                    Horde::log(sprintf('History: Object still existing: object: %s, vanished IMAP uid: %d. Skipping delete.', $object_uid, $bid), 'NOTICE');
                    continue;
                }

                Horde::log(sprintf('History: Object deleted: uid: %d -> %s', $bid, $object_uid), 'NOTICE');
                $this->history->log(
                    $prefix.$object_uid, array('action' => 'delete', 'bid' => $bid), true
                );
            }
        } else {
            $prefix = $this->_constructHistoryPrefix();
            if (empty($prefix)) {
                return;
            }

            Horde::log(sprintf('History: Full history sync for user: %s, folder: %s, is_reset: %d, prefix: %s', $user, $folder, $is_reset, $prefix), 'NOTICE');

            // Full sync. Either our timestamp is too old
            // or the IMAP uidvalidity changed
            $this->_completeSynchronization($prefix, $is_reset);
        }
    }

    /**
     * Perform a complete synchronization.
     * Also markes stale history entries as 'deleted'.
     *
     * @param string $prefix Horde_History prefix
     * @param boolean $is_reset Flag to indicate if the UIDVALIDITY changed
     *
     * @return NULL
     */
    private function _completeSynchronization($prefix, $is_reset)
    {
        $seen_objects = array();
        foreach ($this->data->getObjectToBackend() as $object => $bid) {
            $full_id = $prefix.$object;
            $this->_updateLog($full_id, $bid, $is_reset);
            $seen_objects[$full_id] = true;
        }

        // cut of last ':'
        $search_prefix = substr($prefix, 0, -1);

        // clean up history database: Mark stale entries as deleted
        $all_entries = $this->history->getByTimestamp('>', 0, array(), $search_prefix);

        foreach ($all_entries as $full_id => $db_id) {
            if (isset($seen_objects[$full_id])) {
                continue;
            }

            $last = $this->history->getLatestEntry($full_id);
            if ($last === false || $last['action'] != 'delete') {
                Horde::log(sprintf('History: Cleaning up already removed object: %s', $full_id), 'NOTICE');
                $this->history->log(
                    $full_id, array('action' => 'delete'), true
                );
            }
        }
    }

    /**
     * Construct prefix needed for Horde_History entries.
     *
     * Horde history entries are prefixed and filtered
     * by application name and base64 encoded folder name.
     *
     * @return string Constructed prefix. Can be empty.
     */
    private function _constructHistoryPrefix()
    {
        // Check if we already know the full prefix
        if (!empty($this->_prefix))
            return $this->_prefix;

        $app = $this->_type2app($this->data->getType());
        if (empty($app)) {
            Horde::log('Unsupported app type: ' . $this->data->getType(), 'WARN');
            return '';
        }

        // Determine share id
        $user = $this->data->getAuth();
        $folder = $this->data->getPath();
        $share_id = '';

        // Create a share instance. The performance impact is minimal
        // since the "real" app will create a share instance anyway.
        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($app);
        $all_shares = $shares->listAllShares();
        foreach($all_shares as $id => $share) {
            if ($share->get('folder') == $folder) {
                $share_id = $id;
                break;
            }
        }

        // bail out if we are unable to determine the share id
        if (empty($share_id)) {
            Horde::log(sprintf("share_id not found. Can't compute history prefix for user: %s, folder: %s", $user, $folder), 'ERR');
            return '';
        }

        $this->_prefix = $app.':'.$share_id.':';

        return $this->_prefix;
    }

    /**
     * Map Kolab object type to horde application name.
     *
     * @param string $type Kolab object type
     *
     * @return string The horde application name. Empty string if unknown
     */
    private function _type2app($type)
    {
        $mapping = array(
            'contact' => 'turba',
            'event' => 'kronolith',
            'note' => 'mnemo',
            'task' => 'nag',
        );

        if (isset($mapping[$type]))
            return $mapping[$type];

        return '';
    }

    /**
     * Update the history log for an object.
     *
     * @param string                           $object The object ID.
     * @param string                           $bid    The backend ID of
     *                                                 the object.
     * @param bool                             $force  Force update
     *
     * @return NULL
     */
    private function _updateLog($object, $bid, $force=false)
    {
        $last = $this->history->getLatestEntry($object);
        if ($last === false) {
            // New, unknown object
            Horde::log(sprintf('History: New object: %s, uid: %d', $object, $bid), 'NOTICE');
            $this->history->log(
                $object, array('action' => 'add', 'bid' => $bid), true
            );
        } else {
            // If the last action for this object was 'delete', we issue an 'add'.
            // Objects can vanish and re-appear using the same object uid.
            // (a foreign client is sending an update over a slow link)
            if ($last['action'] == 'delete') {
                Horde::log(sprintf('History: Re-adding previously deleted object: %s, uid: %d', $object, $bid), 'NOTICE');
                $this->history->log(
                    $object, array('action' => 'add', 'bid' => $bid), true
                );
            }

            if (!isset($last['bid']) || $last['bid'] != $bid || $force) {
                Horde::log(sprintf('History: Modifying object: %s, uid: %d, force: %d', $object, $bid, $force), 'NOTICE');
                $this->history->log(
                    $object, array('action' => 'modify', 'bid' => $bid), true
                );
            }
        }
    }
}
