<?php
/**
 * Synchronization strategy that synchronizes at certain intervals.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Synchronization strategy that synchronizes at certain intervals.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Synchronization_TimeBased
extends Horde_Kolab_Storage_Synchronization
{
    /**
     * Kolab object cache resync interval with the IMAP server (in seconds).
     * Default: Two minutes
     *
     * @var int
     */
    private $_interval;

    /**
     * Random offset added to each folder sync interval to prevent mass sync
     * every XX minutes. Otherwise UI latency might get too high.
     *
     * @var int
     */
    private $_random_offset;

    /**
     * Constructor.
     *
     * @param int $interval Kolab object cache resync interval with the IMAP server (in seconds).
     * @param int $random_offset Random offset added to each folder sync interval.
     */
    public function __construct($interval = 120, $random_offset = 90)
    {
        $this->_interval = $interval;
        $this->_random_offset = $random_offset;
    }

    /**
     * Synchronize the provided list in case the selected synchronization
     * strategy requires it.
     *
     * @param Horde_Kolab_Storage_List $list The list to synchronize.
     */
    public function synchronizeList(Horde_Kolab_Storage_List_Tools $list)
    {
        $list_id = $list->getId();
        if (empty($_SESSION['kolab_storage']['synchronization']['list'][$list_id])) {
            $list->getListSynchronization()->synchronize();
            $_SESSION['kolab_storage']['synchronization']['list'][$list_id] = true;
        }
    }

    /**
     * Synchronize the provided data in case the selected synchronization
     * strategy requires it.
     *
     * @param Horde_Kolab_Storage_Data $data The data to synchronize.
     */
    public function synchronizeData(Horde_Kolab_Storage_Data $data)
    {
        $data_id = $data->getId();

        if ($this->hasNotBeenSynchronizedYet($data_id) || $this->syncTimeHasElapsed($data_id)) {
            $data->synchronize();
            $_SESSION['kolab_storage']['synchronization']['data'][$data_id] = time() + $this->_interval + rand(0, $this->_random_offset);
        }
    }

    /**
     * Check if the data store with the given data ID has been synchronized already during this session.
     *
     * @param string $data_id The ID of the data store.
     *
     * @return boolean True, if the store has not been synchronized yet.
     */
    private function hasNotBeenSynchronizedYet($data_id)
    {
        return empty($_SESSION['kolab_storage']['synchronization']['data'][$data_id]);
    }

    /**
     * Check if the data store with the given data ID need to be resynchronized with the backend.
     *
     * @param string $data_id The ID of the data store.
     *
     * @return boolean True, if the store needs to be synchronized again.
     */
    private function syncTimeHasElapsed($data_id)
    {
        return $_SESSION['kolab_storage']['synchronization']['data'][$data_id] < time();
    }
}