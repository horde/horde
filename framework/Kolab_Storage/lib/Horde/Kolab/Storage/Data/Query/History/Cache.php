<?php
/**
 * The cache based hook that updates the Horde history information once data
 * gets synchronized with the Kolab backend.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The cache based hook that updates the Horde history information once data
 * gets synchronized with the Kolab backend.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Query_History_Cache
extends Horde_Kolab_Storage_Data_Query_History_Base
{
    /**
     * Synchronize the preferences information with the information from the
     * backend.
     *
     * @param array $params Additional parameters:
     *   - current_sync: (integer) Timestamp of the current sync.
     *   - last_sync:    (integer) Timestamp containing the time of last sync.
     *   - changes:      (array)   An array of arrays keyed by backend id
     *                             containing information about each change.
     */
    public function synchronize($params = array())
    {
        $timestamp_key = 'Kolab_History_Sync:' . $this->_data->getId();

        /**
         * Check if we need to do a full synchronization. If our stored 'last_sync'
         * timestamp is newer than the logged 'sync' action in the history database,
         * the last history update aborted for some reason.
         *
         * If the 'sync' action from the history database is newer, it means
         * our in-memory version of the data_cache was outdated
         * and already updated by another process.
         */
        if (isset($params['last_sync']) &&
            ($params['last_sync'] === false ||
             $params['last_sync'] > $this->_history->getActionTimestamp($timestamp_key, 'sync')))
        {
            $folder_id = $this->_data->getIdParameters();
            unset($folder_id['type']);

            $this->_logger->debug(sprintf(
                'Resyncing Horde_History for Kolab: last_sync: %d, logged sync: %d, folder. %s',
                $params['last_sync'],
                $this->_history->getActionTimestamp($timestamp_key, 'sync'),
                print_r($folder_id, true))
            );

            unset($params['changes']);
        }
        // Sync. Base class takes care of UIDVALIDITY changes.
        parent::synchronize($params);
        if (isset($params['current_sync'])) {
            $this->_history->log(
                $timestamp_key,
                array('action' => 'sync', 'ts' => $params['current_sync']), true
            );
        }
    }

}
