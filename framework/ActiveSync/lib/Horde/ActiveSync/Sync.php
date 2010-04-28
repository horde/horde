<?php
/**
 * Syncronizer object. Responsible for performing syncronization of the PIM
 * state with the server state. Sends each change to the exporter and updates
 * state accordingly.
 *
 * Some code adapted from the Z-Push project. Original file header below.
 *
 * Copyright 2010 The Horde Project (http://www/horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/***********************************************
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
* Created   :   01.10.2007
*
* � Zarafa Deutschland GmbH, www.zarafaserver.de
* This file is distributed under GPL v2.
* Consult LICENSE file for details
************************************************/
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
     * The statemachine
     *
     * @var Horde_ActiveSynce_StateMachine_Base
     */
    protected $_state;

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
     * Initialize the sync
     *
     * @param Horde_ActiveSync_State_Base $stateMachine      The state machine
     * @param Horde_ActiveSync_Connector_Exporter $exporter  The exporter object
     * @param array $collection                              Collection data
     *
     * @return void
     */
    public function init(Horde_ActiveSync_State_Base &$stateMachine,
                         $exporter,
                         $collection = array())
    {
        $this->_stateMachine = &$stateMachine;
        $this->_exporter = $exporter;
        $this->_folderId = !empty($collection['id']) ? $collection['id'] : false;
        $this->_changes = $stateMachine->getChanges();
        $this->_truncation = !empty($collection['truncation']) ? $collection['truncation'] : 0;
    }

    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Sends the next change in the set and updates the stateMachine if
     * successful
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
                case 'change':
                    $folder = $this->_backend->getFolder($change['id']);
                    $stat = $this->_backend->statFolder($change['id']);
                    if (!$folder) {
                        return;
                    }

                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->folderChange($folder)) {
                        $this->_stateMachine->updateState('change', $stat);
                    }
                    break;
                case 'delete':
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->folderDeletion($change['id'])) {
                        $this->_stateMachine->updateState('delete', $change);
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

                switch($change['type']) {
                case 'change':
                    $truncsize = self::_getTruncSize($this->_truncation);
                    if (!$message = $this->_backend->getMessage($this->_folderId, $change['id'], $truncsize)) {
                        return false;
                    }

                    // copy the flag to the message
                    $message->flags = (isset($change['flags'])) ? $change['flags'] : 0;
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageChange($change['id'], $message) == true) {
                        $this->_stateMachine->updateState('change', $change);
                    }
                    break;

                case 'delete':
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageDeletion($change['id']) == true) {
                        $this->_stateMachine->updateState('delete', $change);
                    }
                    break;

                case 'flags':
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageReadFlag($change['id'], $change['flags']) == true) {
                        $this->_stateMachine->updateState('flags', $change);
                    }
                    break;

                case 'move':
                    if ($flags & Horde_ActiveSync::BACKEND_DISCARD_DATA || $this->_exporter->messageMove($change['id'], $change['parent']) == true) {
                        $this->_stateMachine->updateState('move', $change);
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
        case Horde_ActiveSync::TRUNCATION_HEADERS:
            return 0;
        case Horde_ActiveSync::TRUNCATION_512B:
            return 512;
        case Horde_ActiveSync::TRUNCATION_1K:
            return 1024;
        case Horde_ActiveSync::TRUNCATION_5K:
            return 5 * 1024;
        case Horde_ActiveSync::TRUNCATION_SEVEN:
        case Horde_ActiveSync::TRUNCATION_ALL:
            return 1024 * 1024; // We'll limit to 1MB anyway
        default:
            return 1024; // Default to 1Kb
        }
    }

}
