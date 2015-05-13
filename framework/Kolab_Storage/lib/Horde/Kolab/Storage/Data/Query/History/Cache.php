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
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $timestamp_key = 'Kolab_History_Sync:'.$this->data->getId();

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
             $params['last_sync'] > $this->history->getActionTimestamp($timestamp_key, 'sync')))
        {
            $folder_id = $this->data->getIdParameters();
            unset($folder_id['type']);

            Horde::log(sprintf('Resyncing Horde_History for Kolab: last_sync: %d, logged sync: %d, folder. %s',
                           $params['last_sync'],
                           $this->history->getActionTimestamp($timestamp_key, 'sync'),
                           print_r($folder_id, true)), 'WARN');

            unset($params['changes']);
        }
        // Sync. Base class takes care of UIDVALIDITY changes.
        parent::synchronize($params);
        if (isset($params['current_sync'])) {
            $this->history->log(
                $timestamp_key,
                array('action' => 'sync', 'ts' => $params['current_sync']), true
            );
        }
    }
}
