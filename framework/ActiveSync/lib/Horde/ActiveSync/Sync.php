<?php
/**
 * Syncronizer object. Responsible for performing syncronization of the PIM
 * state with the server state. Sends each change to the exporter and updates
 * state accordingly.
 *
 * Some code adapted from the Z-Push project. Original file header below.
 * File      :   diffbackend.php
 * Project   :   Z-Push
 * Descr     :   We do a standard differential
 *               change detection by sorting both
 *               lists of items by their unique id,
 *               and then traversing both arrays
 *               of items at once. Changes can be
 *               detected by comparing items at
 *               the same position in both arrays.
 *
 *  Created   :   01.10.2007
 *
 * © Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL-2.0.
 * Consult COPYING file for details
 *
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Horde_ActiveSync_Sync
 *
 * Handles the actual synchronization with the device.
 *
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Sync
{
    /**
     * Local copy of changes to push to PIM
     *
     * @var array
     */
    protected $_changes;

    /**
     * Tracks the number of changes that have been sent.
     *
     * @var int
     */
    protected $_step = 0;

    /**
     * Server specific folder id
     *
     * @var string
     */
    protected $_folderId;

    /**
     * The collection type for this folder
     *
     * @var string
     */
    protected $_collection;

    /**
     * The backend driver
     *
     * @var Horde_ActiveSync_Driver_Base
     */
    protected $_backend;

    /**
     * Any flags
     * ???
     * @var <type>
     */
    protected $_flags;

    /**
     * The stateDriver
     *
     * @var Horde_ActiveSynce_State_Base
     */
    protected $_stateDriver;

    /**
     * The change streamer
     *
     * @var Horde_ActiveSync_Connector_Exporter
     */
    protected $_exporter;

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Driver_Base $backend  The backend driver
     */
    public function __construct(Horde_ActiveSync_Driver_Base $backend)
    {
        $this->_backend = $backend;
    }

    /**
     * Initialize the sync. Causes the backend to be polled for changes, and
     * the changes to be populated in the local cache.
     *
     * @param Horde_ActiveSync_State_Base $stateDriver       The state driver
     * @param Horde_ActiveSync_Connector_Exporter $exporter  The exporter object
     * @param array $collection                              Collection data
     * @param boolean $isPing                                This is a PING request.
     *
     */
    public function init(
        Horde_ActiveSync_State_Base &$stateDriver, $exporter, array $collection, $isPing = false)
    {
        $this->_stateDriver = &$stateDriver;
        $this->_exporter = $exporter;
        $this->_folderId = !empty($collection['id']) ? $collection['id'] : false;
        $this->_changes = $stateDriver->getChanges(array('ping' => $isPing));
        $this->_truncation = !empty($collection['truncation']) ? $collection['truncation'] : 0;
    }

    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Sends the next change in the set and updates the stateDriver if
     * successful
     *
     * @param integer $flags  A Horde_ActiveSync:: flag constant
     *
     * @return mixed  A progress array or false if no more changes
     */
    public function syncronize($flags = 0)
    {
        $progress = array();

        if ($this->_folderId == false) {
            if ($this->_step < count($this->_changes)) {
                $change = $this->_changes[$this->_step];
                switch($change['type']) {
                case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                    // Get the new folder information
                    $folder = $this->_backend->getFolder($change['id']);
                    $stat = $this->_backend->statFolder(
                        $change['id'],
                        $folder->parentid,
                        $folder->displayname);
                    if (!$folder) {
                        return;
                    }
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA ||
                        $this->_exporter->folderChange($folder)) {

                        $this->_stateDriver->updateState(
                            Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC, $stat);
                    }
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA ||
                        $this->_exporter->folderDeletion($change['id'])) {

                        $this->_stateDriver->updateState(
                            Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC, $change);
                    }
                    break;
                }
                $this->_step++;
                $progress = array();
                $progress['steps'] = count($this->_changes);
                $progress['progress'] = $this->_step;
                return $progress;
            } else {
                return false;
            }
        } else {
            if ($this->_step < count($this->_changes)) {
                $change = $this->_changes[$this->_step];

                // Prevent corrupt server entries from causing infinite sync
                // attempts.
                while (empty($change['id']) && $this->_step < count($this->_changes) - 1) {
                    $this->_logger->err('Missing UID value for an entry in: ' . $this->_folderId);
                    $this->_step++;
                    $change = $this->_changes[$this->_step];
                }

                switch($change['type']) {
                case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                    $truncsize = self::_getTruncSize($this->_truncation);
                    if (!$message = $this->_backend->getMessage($this->_folderId, $change['id'], $truncsize)) {
                        return false;
                    }

                    // copy the flag to the message
                    // @TODO: Rename this to ->new or ->status or *anything* other than flags!!
                    $message->flags = (isset($change['flags'])) ? $change['flags'] : 0;
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageChange($change['id'], $message) == true) {
                        $this->_stateDriver->updateState(
                            Horde_ActiveSync::CHANGE_TYPE_CHANGE, $change);
                    }
                    break;

                case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageDeletion($change['id']) == true) {
                        $this->_stateDriver->updateState(
                            Horde_ActiveSync::CHANGE_TYPE_DELETE, $change);
                    }
                    break;

                case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageReadFlag($change['id'], $change['flags']) == true) {
                        $this->_stateDriver->updateState(
                            Horde_ActiveSync::CHANGE_TYPE_FLAGS, $change);
                    }
                    break;

                case Horde_ActiveSync::CHANGE_TYPE_MOVE:
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageMove($change['id'], $change['parent']) == true) {
                        $this->_stateDriver->updateState(
                            Horde_ActiveSync::CHANGE_TYPE_MOVE, $change);
                    }
                    break;
                }

                $this->_step++;

                $progress = array();
                $progress['steps'] = count($this->_changes);
                $progress['progress'] = $this->_step;

                return $progress;
            } else {
                return false;
            }
        }
    }

    public function getChangeCount()
    {
        return count($this->_changes);
    }

    /**
     *
     * @param $truncation
     * @return unknown_type
     */
    private static function _getTruncSize($truncation)
    {
        switch($truncation) {
        case Horde_ActiveSync::TRUNCATION_ALL:
            return 0;
        case Horde_ActiveSync::TRUNCATION_1:
            return 512;
        case Horde_ActiveSync::TRUNCATION_2:
            return 1024;
        case Horde_ActiveSync::TRUNCATION_3:
            return 2048;
        case Horde_ActiveSync::TRUNCATION_4:
            return 5120;
        // case Horde_ActiveSync::TRUNCATION_5:
        //     return 20480;
        // case Horde_ActiveSync::TRUNCATION_6:
        //     return 51200;
        case Horde_ActiveSync::TRUNCATION_7:
            //return 102400;
        //case Horde_ActiveSync::TRUNCATION_8:
        case Horde_ActiveSync::TRUNCATION_NONE:
            return 1048576; // We'll limit to 1MB anyway
        default:
            return 1024; // Default to 1Kb
        }
    }

}
