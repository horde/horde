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
     * The queriable data.
     *
     * @var Horde_Kolab_Storage_Data
     */
    protected $_data;

    /**
     * The history handler.
     *
     * @var Horde_History
     */
    protected $_history;

    /**
     * The logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * The Kolab factory.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    protected $_factory;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data $data   The queriable data.
     * @param array                    $params Additional parameters.
     *   - factory:  (Horde_Kolab_Storage_Factory)  The factory object.
     *
     */
    public function __construct(Horde_Kolab_Storage_Data $data, $params)
    {
        $this->_data = $data;
        $this->_history = $params['factory']->createHistory($data->getAuth());
        $this->_logger = new Horde_Support_Stub();
        $this->_factory = $params['factory'];
    }

    /**
     * Set the logger
     *
     * @param Horde_Log_Logger $logger  The logger instance.
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Synchronize any changes with the History driver.
     *
     * @param array $params Additional parameters:
     *   - changes: (array)  An array of arrays keyed by backend id containing
     *                       information about each change. If not present,
     *                       triggers a full history sync.
     *   - is_reset: (boolean)  If true, indicates that UIDVALIDITY changed.
     */
    public function synchronize($params = array())
    {
        $user = $this->_data->getAuth();
        $folder = $this->_data->getPath();

        // check if IMAP uidvalidity changed
        $is_reset = !empty($params['is_reset']);

        if (isset($params['changes']) && !$is_reset) {
            $added = $params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED];
            $deleted = $params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED];

            if (!empty($added) || !empty($deleted)) {
                if (!$prefix = $this->_factory->getHistoryPrefixGenerator()->getPrefix($this->_data)) {
                    // Abort history update if we can't determine the prefix.
                    return;
                }
                $this->_logger->debug(sprintf(
                    'History: Incremental update for user: %s, folder: %s, prefix: %s',
                    $user, $folder, $prefix)
                );
            }

            foreach ($added as $bid => $object) {
                $this->_updateLog($prefix . $object['uid'], $bid);
            }

            foreach ($deleted as $bid => $object_uid) {
                // Check if the object is really gone from the folder.
                // Otherwise we just deleted a duplicated object or updated the original one.
                // (An update results in an ADDED + DELETED folder action)
                if ($this->_data->objectIdExists($object_uid) == true) {
                    $this->_logger->debug(sprintf(
                        'History: Object still existing: object: %s, vanished IMAP uid: %d. Skipping delete.',
                        $object_uid, $bid)
                    );
                    continue;
                }
                $this->_logger->debug(
                    sprintf('History: Object deleted: uid: %d -> %s',
                    $bid, $object_uid)
                );
                $this->_history->log(
                    $prefix . $object_uid, array('action' => 'delete', 'bid' => $bid), true
                );
            }
        } else {
            // Full sync. Either our timestamp is too old or the IMAP
            // uidvalidity changed.
            if (!$prefix = $this->_factory->getHistoryPrefixGenerator()->getPrefix($this->_data)) {
                return;
            }
            $this->_logger->debug(sprintf(
                'History: Full history sync for user: %s, folder: %s, is_reset: %d, prefix: %s',
                $user, $folder, $is_reset, $prefix)
            );
            $this->_completeSynchronization($prefix, $is_reset);
        }
    }

    /**
     * Perform a complete synchronization.
     * Also marks stale history entries as 'deleted'.
     *
     * @param string $prefix     Horde_History prefix
     * @param boolean $is_reset  Flag to indicate if the UIDVALIDITY changed
     */
    protected function _completeSynchronization($prefix, $is_reset)
    {
        $seen_objects = array();
        foreach ($this->_data->getObjectToBackend() as $object => $bid) {
            $full_id = $prefix . $object;
            $this->_updateLog($full_id, $bid, $is_reset);
            $seen_objects[$full_id] = true;
        }

        // cut off last ':'
        $search_prefix = substr($prefix, 0, -1);

        // clean up history database: Mark stale entries as deleted
        $all_entries = $this->_history->getByTimestamp('>', 0, array(), $search_prefix);

        foreach ($all_entries as $full_id => $db_id) {
            if (isset($seen_objects[$full_id])) {
                continue;
            }

            $last = $this->_history->getLatestEntry($full_id);
            if ($last === false || $last['action'] != 'delete') {
                $this->_logger->debug(sprintf(
                    'History: Cleaning up already removed object: %s', $full_id)
                );
                $this->_history->log(
                    $full_id, array('action' => 'delete'), true
                );
            }
        }
    }

    /**
     * Update the history log for an object.
     *
     * @param string $object  The object ID.
     * @param string $bid     The backend ID of the object.
     * @param boolean $force  Force update
     */
    protected function _updateLog($object, $bid, $force = false)
    {
        $last = $this->_history->getLatestEntry($object);
        if ($last === false) {
            // New, unknown object
            $this->_logger->debug(sprintf(
                'History: New object: %s, uid: %d',
                $object, $bid)
            );
            $this->_history->log(
                $object, array('action' => 'add', 'bid' => $bid), true
            );
        } else {
            // If the last action for this object was 'delete', we issue an 'add'.
            // Objects can vanish and re-appear using the same object uid.
            // (a foreign client is sending an update over a slow link)
            if ($last['action'] == 'delete') {
                $this->_logger->debug(sprintf(
                    'History: Re-adding previously deleted object: %s, uid: %d',
                    $object, $bid)
                );
                $this->_history->log(
                    $object, array('action' => 'add', 'bid' => $bid), true
                );
            }

            if (!isset($last['bid']) || $last['bid'] != $bid || $force) {
                $this->_logger->debug(sprintf(
                    'History: Modifying object: %s, uid: %d, force: %d',
                    $object, $bid, $force)
                );
                $this->_history->log(
                    $object, array('action' => 'modify', 'bid' => $bid), true
                );
            }
        }
    }

}
