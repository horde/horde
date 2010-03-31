<?php
define('BACKEND_DISCARD_DATA', 1);

/**
 * Horde_ActiveSync_DiffState classes provide a basic diff engine for comparing
 * PIM and backend state. This is a general diff engine, and can be used as-is
 * or subclassed/overridden by individual backend drivers if they backend can
 * provide the differential information more effeciently.
 *
 * Diff algorithms ported from Z-Push's diffbackend.php DiffState class, all
 * other code and modifications:
 *
 * Copyright 2010 The Horde Project (http://www/horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 *
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

/**
 * This class handles preparing the diff data for sending back to the PIM. Takes
 * the data from the Importer, syncronizes it and tracks the state.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
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
     * The statemachine
     *
     * @var Horde_ActiveSynce_StateMachine_Base
     */
    protected $_state;

    /**
     * The current syncKey for this request
     *
     * @var string
     */
    protected $_syncKey;

    /**
     * The change streamer
     *
     * @var Horde_ActiveSync_Streamer
     */
    protected $_streamer;

    protected $_logger;

    /**
     * Const'r
     *
     * @param <type> $backend
     */
    public function __construct(Horde_ActiveSync_Driver_Base $backend)
    {
        $this->_backend = $backend;
    }

    public function init(Horde_ActiveSync_State_Base &$stateMachine,
                         $streamer,
                         $collection = array())
    {
        $this->_stateMachine = &$stateMachine;
        $this->_streamer = $streamer;
        $this->_folderId = !empty($collection['id']) ? $collection['id'] : false;
        $this->_changes = $stateMachine->getChanges();
        $this->_syncKey = $collection['synckey'];
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
            //@TODO: Folder changes not implemented??
            if ($this->_step < count($this->_changes)) {
                $change = $this->_changes[$this->_step];

                switch($change['type']) {
                case 'change':
                    $folder = $this->_backend->getFolder($change['id']);
                    $stat = $this->_backend->StatFolder($change['id']);
                    if (!$folder) {
                        return;
                    }

                    if ($flags & BACKEND_DISCARD_DATA || $this->_streamer->FolderChange($folder)) {
                        $this->_stateMachine->updateState('change', $stat);
                    }
                    break;
                case 'delete':
                    if ($flags & BACKEND_DISCARD_DATA || $this->_streamer->FolderDeletion($change['id'])) {
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
                    // Note: because 'parseMessage' and 'statMessage' are two seperate
                    // calls, we have a chance that the message has changed between both
                    // calls. This may cause our algorithm to 'double see' changes.
                    $stat = $this->_backend->StatMessage($this->_folderId, $change['id']);
                    if (!$message = $this->_backend->GetMessage($this->_folderId, $change['id'], $truncsize)) {
                        return false;
                    }

                    // copy the flag to the message
                    $message->flags = (isset($change['flags'])) ? $change['flags'] : 0;

                    if ($stat && $message) {
                        if ($flags & BACKEND_DISCARD_DATA || $this->_streamer->messageChange($change['id'], $message) == true) {
                            $this->_stateMachine->updateState('change', $stat);
                        }
                    }
                    break;

                case 'delete':
                    if ($flags & BACKEND_DISCARD_DATA || $this->_streamer->messageDeletion($change['id']) == true) {
                        $this->_stateMachine->updateState('delete', $change);
                    }
                    break;

                case 'flags':
                    if ($flags & BACKEND_DISCARD_DATA || $this->_streamer->messageReadFlag($change['id'], $change['flags']) == true) {
                        $this->_stateMachine->updateState('flags', $change);
                    }
                    break;

                case 'move':
                    if ($flags & BACKEND_DISCARD_DATA || $this->_streamer->messageMove($change['id'], $change['parent']) == true) {
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
        return $this->_stateMachine->getChangeCount();
    }

    /**
     *
     * @param $truncation
     * @return unknown_type
     */
    private static function _getTruncSize($truncation)
    {
        switch($truncation) {
        case SYNC_TRUNCATION_HEADERS:
            return 0;
        case SYNC_TRUNCATION_512B:
            return 512;
        case SYNC_TRUNCATION_1K:
            return 1024;
        case SYNC_TRUNCATION_5K:
            return 5 * 1024;
        case SYNC_TRUNCATION_SEVEN:
        case SYNC_TRUNCATION_ALL:
            return 1024 * 1024; // We'll limit to 1MB anyway
        default:
            return 1024; // Default to 1Kb
        }
    }

}
