<?php
/**
 * Horde_ActiveSync_Sync::
 *
 * Some code adapted from the Z-Push project:
 *   File      :   diffbackend.php
 *   Project   :   Z-Push
 *   Descr     :   We do a standard differential
 *                 change detection by sorting both
 *                 lists of items by their unique id,
 *                 and then traversing both arrays
 *                 of items at once. Changes can be
 *                 detected by comparing items at
 *                 the same position in both arrays.
 *
 *    Created   :   01.10.2007
 *
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
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
 * Syncronizer object. Responsible for performing syncronization of the PIM
 * state with the server state. Sends each change to the exporter and updates
 * state accordingly.
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
    protected $_step;

    /**
     * Server specific folder id
     *
     * @var string
     */
    protected $_folderId;

    /**
     * The collection data
     *
     * @var array
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
        Horde_ActiveSync_State_Base $stateDriver, $exporter, array $collection, $isPing = false)
    {
        $this->_collection = $collection;
        $this->_stateDriver = $stateDriver;
        $this->_exporter = $exporter;
        $this->_folderId = !empty($collection['id']) ? $collection['id'] : false;
        $this->_changes = $stateDriver->getChanges(array('ping' => $isPing));
        $this->_step = 0;
    }

    /**
     * Set a logger.
     *
     * @param Horde_Log_Logger $logger  The logger
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Sends the next change in the set and updates the device state.
     *
     * @param integer $flags  A Horde_ActiveSync:: flag constant
     *
     * @return array|boolean  A progress array or false if no more changes
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
                    if ($folder = $this->_backend->getFolder($change['id'])) {
                        $stat = $this->_backend->statFolder(
                            $change['id'],
                            $folder->parentid,
                            $folder->displayname);
                        $this->_exporter->folderChange($folder);
                    } else {
                        $this->_logger->err(sprintf(
                            'Error stating %s : ignoring.',
                            $change['id']));
                        $stat = array('id' => $change['id'], 'mod' => $change['id'], 0);
                    }
                    $this->_stateDriver->updateState(
                        Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC, $stat);
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                    $this->_stateDriver->updateState(
                        Horde_ActiveSync::CHANGE_TYPE_DELETE, $change);
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
                while (empty($change['id']) && $this->_step < count($this->_changes) - 1) {
                    $this->_logger->err('Missing UID value for an entry in: ' . $this->_folderId);
                    $this->_step++;
                    $change = $this->_changes[$this->_step];
                }

                switch($change['type']) {
                case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                    try {
                        $message = $this->_backend->getMessage(
                            $this->_folderId, $change['id'], $this->_collection);
                        // copy the flag to the message
                        // @TODO: Rename this to ->new or ->status or *anything* other than flags!!
                        $message->flags = (isset($change['flags'])) ? $change['flags'] : 0;
                        $this->_exporter->messageChange($change['id'], $message);
                    } catch (Horde_Exception_NotFound $e) {
                        $this->_logger->err('Message gone or error reading message from server: ' . $e->getMessage());
                    } catch (Horde_ActiveSync_Exception $e) {
                        $this->_logger->err('Unknown backend error skipping message: ' . $e->getMessage());
                    }
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                    $this->_exporter->messageDeletion($change['id']);
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
                    if (isset($change['flags']['read'])) {
                        $this->_exporter->messageReadFlag($change['id'], $change['flags']['read']);
                    }
                    if (isset($change['flags']['flagged'])) {
                        $this->_exporter->messageFlag($change['id'], $change['flags']['flagged']);
                    }
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_MOVE:
                    $this->_exporter->messageMove($change['id'], $change['parent']);
                    break;
                }

                $this->_stateDriver->updateState($change['type'], $change);
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

}
