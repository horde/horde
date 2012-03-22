<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package SyncMl
 */
class Horde_SyncMl_Sync
{
    /**
     * @see Horde_SyncMl_Sync::_state
     */
    const STATE_INIT =      0;
    const STATE_SYNC =      1;
    const STATE_MAP =       2;
    const STATE_COMPLETED = 3;

    /**
     * Target (client) URI (database).
     *
     * @var string
     */
    protected $_targetLocURI;

    /**
     * Source (server) URI (database).
     *
     * @var string
     */
    protected $_sourceLocURI;

    /**
     * The synchronization method, one of the Horde_SyncMl::ALERT_* constants.
     *
     * @var integer
     */
    protected $_syncType;

    /**
     * Counts the <Sync>s sent by the server.
     *
     * @var integer
     */
    protected $_syncsSent = 0;

    /**
     * Counts the <Sync>s received by the server. Currently unused.
     *
     * @var integer
     */
    protected $_syncsReceived = 0;

    /**
     * Map data is expected whenever an add is sent to the client.
     *
     * @var boolean
     */
    protected $_expectingMapData = false;

    /**
     * State of the current sync.
     *
     * A sync starts in Horde_SyncMl_Sync::STATE_INIT and moves on to the next state with every
     * <Final> received from the client: Horde_SyncMl_Sync::STATE_INIT, Horde_SyncMl_Sync::STATE_SYNC, Horde_SyncMl_Sync::STATE_MAP,
     * Horde_SyncMl_Sync::STATE_COMPLETED.  Horde_SyncMl_Sync::STATE_MAP doesn't occur for _FROM_CLIENT syncs.
     *
     * @var constant
     */
    protected $_state = Horde_SyncMl_Sync::STATE_INIT;

    /**
     * Sync Anchors determine the interval from which changes are retrieved.
     *
     * @var integer
     */
    protected $_clientAnchorNext;

    protected $_serverAnchorLast;
    protected $_serverAnchorNext;

    /**
     * Number of objects that have been sent to the server for adding.
     *
     * @var integer
     */
    protected $_client_add_count = 0;

    /**
     * Number of objects that have been sent to the server for replacement.
     *
     * @var integer
     */
    protected $_client_replace_count = 0;

    /**
     * Number of objects that have been sent to the server for deletion.
     *
     * @var integer
     */
    protected $_client_delete_count = 0;

    /**
     * Add due to client replace request when map entry is not found. Happens
     * during SlowSync.
     *
     * @var integer
     */
    protected $_client_addreplaces = 0;

    /**
     * Number of objects that have been sent to the client for adding.
     *
     * @var integer
     */
    protected $_server_add_count = 0;

    /**
     * Number of objects that have been sent to the client for replacement.
     *
     * @var integer
     */
    protected $_server_replace_count = 0;

    /**
     * Number of objects that have been sent to the client for deletion.
     *
     * @var integer
     */
    protected $_server_delete_count = 0;

    /**
     * Number of failed actions, for logging purposes only.
     *
     * @var integer
     */
    protected $_errors = 0;

    /**
     * List of object UIDs (in the keys) that have been added on the server
     * since the last synchronization and are supposed to be sent to the
     * client.
     *
     * @var array
     */
    protected $_server_adds;

    /**
     * List of object UIDs (in the keys) that have been changed on the server
     * since the last synchronization and are supposed to be sent to the
     * client.
     *
     * @var array
     */
    protected $_server_replaces;

    /**
     * List of object UIDs (in the keys) that have been deleted on the server
     * since the last synchronization and are supposed to be sent to the
     * client.
     *
     * @var array
     */
    protected $_server_deletes;

    /**
     * List of task UIDs (in the keys) that have been added on the server
     * since the last synchronization and are supposed to be sent to the
     * client.
     *
     * This is only used for clients handling tasks and events in one
     * database. We need to seperately store the server tasks adds, so when we
     * get a Map command from the client, we know whether to put this in tasks
     * or calendar.
     *
     * @var array
     */
    protected $_server_task_adds;

    /**
     *
     * @param string $syncType
     * @param string $serverURI
     * @param string $clientURI
     * @param integer $serverAnchorLast
     * @param integer $serverAnchorNext
     * @param string $clientAnchorNext
     */
    public function __construct($syncType, $serverURI, $clientURI, $serverAnchorLast,
                         $serverAnchorNext, $clientAnchorNext)
    {
        $this->_syncType = $syncType;
        $this->_targetLocURI = $serverURI;
        $this->_sourceLocURI = $clientURI;
        $this->_clientAnchorNext = $clientAnchorNext;
        $this->_serverAnchorLast = $serverAnchorLast;
        $this->_serverAnchorNext = $serverAnchorNext;
    }

    /**
     * Here's where the actual processing of a client-sent Sync Item takes
     * place. Entries are added, deleted or replaced from the server database
     * by using backend API calls.
     *
     * @todo maybe this should be moved to SyncItem
     *
     * @param $output
     * @param Horde_SyncMl_SyncElement $item
     */
    public function handleClientSyncItem(&$output, &$item)
    {
        global $backend;

        $backend->logMessage(
            'Handling <' . $item->elementType . '> sent from client', 'DEBUG');

        /* if size of item is set: check it first */
        if ($item->size > 0) {
            if (strlen($item->content) != $item->size &&
                /* For some strange reason the SyncML conformance test suite
                 * sends an item with length n and size tag=n+1 and expects us
                 * the accept it. Happens in test 1301.  So ignore this to be
                 * conformant (and wrong). */
                strlen($item->content) + 1 != $item->size) {
                $item->responseCode = Horde_SyncMl::RESPONSE_SIZE_MISMATCH;
                $backend->logMessage(
                    'Item size mismatch. Size reported as ' . $item->size
                    . ' but actual size is ' . strlen($item->content), 'ERR');
                $this->_errors++;
                return false;
            }
        }

        $device = $GLOBALS['backend']->state->getDevice();
        $hordedatabase = $database = $this->_targetLocURI;
        $content = $item->content;
        if ($item->contentFormat == 'b64') {
            $content = base64_decode($content);
        }

        if (($item->contentType == 'text/calendar' ||
             $item->contentType == 'text/x-vcalendar') &&
            $backend->normalize($database) == 'calendar' &&
            $device->handleTasksInCalendar()) {
            $tasksincalendar = true;
            /* Check if the client sends us a vtodo in a calendar sync. */
            if (preg_match('/(\r\n|\r|\n)BEGIN[^:]*:VTODO/',
                           "\n" . $content)) {
                $hordedatabase = $this->_taskToCalendar($backend->normalize($database));
             }
        } else {
            $tasksincalendar = false;
        }

        /* Use contentType explicitly specified in this sync command. */
        $contentType = $item->contentType;

        /* If not provided, use default from device info. */
        if (!$contentType) {
            $contentType = $device->getPreferredContentType($hordedatabase);
        }

        if ($item->elementType != 'Delete') {
            list($content, $contentType) = $device->convertClient2Server($content, $contentType);
        }

        $cuid = $item->cuid;
        $suid = false;

        if ($item->elementType == 'Add') {
            /* Handle client add requests.
             *
             * @todo: check if this $cuid is already present and then maybe do
             * an replace instead? */
            $suid = $backend->addEntry($hordedatabase, $content, $contentType, $cuid);
            if (!is_a($suid, 'PEAR_Error')) {
                $this->_client_add_count++;
                $item->responseCode = Horde_SyncMl::RESPONSE_ITEM_ADDED;
                $backend->logMessage('Added client entry as ' . $suid, 'DEBUG');
            } else {
                $this->_errors++;
                /* @todo: better response code. */
                $item->responseCode = Horde_SyncMl::RESPONSE_NO_EXECUTED;
                $backend->logMessage('Error in adding client entry: ' . $suid->message, 'ERR');
            }
        } elseif ($item->elementType == 'Delete') {
            /* Handle client delete requests. */
            $ok = $backend->deleteEntry($database, $cuid);
            if (!$ok && $tasksincalendar) {
                $backend->logMessage(
                    'Task ' . $cuid . ' deletion sent with calendar request', 'DEBUG');
                $ok = $backend->deleteEntry($this->_taskToCalendar($backend->normalize($database)), $cuid);
            }

            if ($ok) {
                $this->_client_delete_count++;
                $item->responseCode = Horde_SyncMl::RESPONSE_OK;
                $backend->logMessage('Deleted entry ' . $suid . ' due to client request', 'DEBUG');
            } else {
                $this->_errors++;
                $item->responseCode = Horde_SyncMl::RESPONSE_ITEM_NO_DELETED;
                $backend->logMessage('Failure deleting client entry, maybe already disappeared from server', 'DEBUG');
            }

        } elseif ($item->elementType == 'Replace') {
            /* Handle client replace requests. */
            $suid = $backend->replaceEntry($hordedatabase, $content,
                                           $contentType, $cuid);

            if (!is_a($suid, 'PEAR_Error')) {
                $this->_client_replace_count++;
                $item->responseCode = Horde_SyncMl::RESPONSE_OK;
                $backend->logMessage('Replaced entry ' . $suid . ' due to client request', 'DEBUG');
            } else {
                $backend->logMessage($suid->message, 'DEBUG');

                /* Entry may have been deleted; try adding it. */
                $suid = $backend->addEntry($hordedatabase, $content,
                                           $contentType, $cuid);
                if (!is_a($suid, 'PEAR_Error')) {
                    $this->_client_addreplaces++;
                    $item->responseCode = Horde_SyncMl::RESPONSE_ITEM_ADDED;
                    $backend->logMessage(
                        'Added instead of replaced entry ' . $suid, 'DEBUG');
                } else {
                    $this->_errors++;
                    /* @todo: better response code. */
                    $item->responseCode = Horde_SyncMl::RESPONSE_NO_EXECUTED;
                    $backend->logMessage(
                        'Error in adding client entry due to replace request: '
                        . $suid->message, 'ERR');
                }
            }
        } else {
            $backend->logMessage(
                'Unexpected elementType: ' . $item->elementType, 'ERR');
        }

        return $suid;
    }

    /**
     * Creates a <Sync> output containing the server changes.
     *
     * @todo Check for Mem/FreeMem and Mem/FreeID when checking MaxObjSize
     */
    public function createSyncOutput(&$output)
    {
        global $backend, $messageFull;

        $backend->logMessage(
            'Creating <Sync> output for server changes in database '
            . $this->_targetLocURI, 'DEBUG');

        /* If sync data from client only, nothing to be done here. */
        if($this->_syncType == Horde_SyncMl::ALERT_ONE_WAY_FROM_CLIENT ||
           $this->_syncType == Horde_SyncMl::ALERT_REFRESH_FROM_CLIENT) {
            return;
        }

        /* If one sync has been sent an no pending data: bail out. */
        if ($this->_syncsSent > 0 && !$this->hasPendingElements()) {
            return;
        }

        /* $messageFull will be set to true to indicate that there's no room
         * for other data in this message. If it's false (empty) and there are
         * pending Sync data, the final command will sent the pending data. */
        $messageFull = false;

        $state = $GLOBALS['backend']->state;
        $device = $state->getDevice();
        $contentType = $device->getPreferredContentTypeClient(
            $this->_targetLocURI, $this->_sourceLocURI);
        $contentTypeTasks = $device->getPreferredContentTypeClient(
            'tasks', $this->_sourceLocURI);
        if ($state->deviceInfo && $state->deviceInfo->CTCaps) {
            $fields = array($contentType => isset($state->deviceInfo->CTCaps[$contentType]) ? $state->deviceInfo->CTCaps[$contentType] : null,
                            $contentTypeTasks => isset($state->deviceInfo->CTCaps[$contentTypeTasks]) ? $state->deviceInfo->CTCaps[$contentTypeTasks] : null);
        } else {
            $fields = array($contentType => null, $contentTypeTasks => null);
        }

        /* If server modifications are not retrieved yet (first Sync element),
         * do it now. */
        if (!is_array($this->_server_adds)) {
            $backend->logMessage(
                'Compiling server changes from '
                . date('Y-m-d H:i:s', $this->_serverAnchorLast)
                . ' to ' . date('Y-m-d H:i:s', $this->_serverAnchorNext), 'DEBUG');

            $result = $this->_retrieveChanges($this->_targetLocURI,
                                              $this->_server_adds,
                                              $this->_server_replaces,
                                              $this->_server_deletes);
            if (is_a($result, 'PEAR_Error')) {
                return;
            }

            /* If tasks are handled inside calendar, do the same again for
             * tasks. Merge resulting arrays. */
            if ($backend->normalize($this->_targetLocURI) == 'calendar' &&
                $device->handleTasksInCalendar()) {
                $this->_server_task_adds = $deletes2 = $replaces2 = array();
                $result = $this->_retrieveChanges('tasks',
                                                  $this->_server_task_adds,
                                                  $replaces2,
                                                  $deletes2);
                if (is_a($result, 'PEAR_Error')) {
                    return;
                }
                $this->_server_adds = array_merge($this->_server_adds,
                                                  $this->_server_task_adds);
                $this->_server_replaces = array_merge($this->_server_replaces,
                                                      $replaces2);
                $this->_server_deletes = array_merge($this->_server_deletes,
                                                     $deletes2);
            }

            $numChanges = count($this->_server_adds)
                + count($this->_server_replaces)
                + count($this->_server_deletes);
            $backend->logMessage(
                'Sending ' . $numChanges . ' server changes ' . 'for client URI '
                . $this->_targetLocURI, 'DEBUG');

            /* Now we know the number of Changes and can send them to the
             * client. */
            $di = $state->deviceInfo;
            if ($di->SupportNumberOfChanges) {
                $output->outputSyncStart($this->_sourceLocURI,
                                         $this->_targetLocURI,
                                         $numChanges);
            } else {
                $output->outputSyncStart($this->_sourceLocURI,
                                         $this->_targetLocURI);
            }
        } else {
            /* Package continued. Sync in subsequent message. */
            $output->outputSyncStart($this->_sourceLocURI,
                                     $this->_targetLocURI);
        }

        /* We sent a Sync. So at least we espect a status response and thus
         * another message from the client. */
        $GLOBALS['message_expectresponse'] = true;

        /* Handle deletions. */
        $deletes = $this->_server_deletes;
        foreach ($deletes as $suid => $cuid) {
            /* Check if we have space left in the message. */
            if ($state->maxMsgSize - $output->getOutputSize() < Horde_SyncMl::MSG_TRAILER_LEN) {
                $backend->logMessage(
                    'Maximum message size ' . $state->maxMsgSize
                    . ' approached during delete; current size: '
                    . $output->getOutputSize(), 'DEBUG');
                $messageFull = true;
                $output->outputSyncEnd();
                $this->_syncsSent += 1;
                return;
            }
            $backend->logMessage(
                "Sending delete from server: client id $cuid, server id $suid", 'DEBUG');
            /* Create a Delete request for client. */
            $cmdId = $output->outputSyncCommand('Delete', null, null, null, $cuid, null);
            unset($this->_server_deletes[$suid]);
            $state->serverChanges[$state->messageID][$this->_targetLocURI][$cmdId] = array($suid, $cuid);
            $this->_server_delete_count++;
        }

        /* Handle additions. */
        $adds = $this->_server_adds;
        foreach ($adds as $suid => $cuid) {
            $backend->logMessage("Sending add from server: $suid", 'DEBUG');

            $syncDB = isset($this->_server_task_adds[$suid]) ? 'tasks' : $this->_targetLocURI;
            $ct = isset($this->_server_task_adds[$suid]) ? $contentTypeTasks : $contentType;

            $c = $backend->retrieveEntry($syncDB, $suid, $ct, $fields[$ct]);
            /* Item in history but not in database. Strange, but can
             * happen. */
            if (is_a($c, 'PEAR_Error')) {
                $backend->logMessage(
                    'API export call for ' . $suid . ' failed: '
                    . $c->getMessage(), 'ERR');
            } else {
                list($clientContent, $clientContentType, $clientEncodingType) =
                    $device->convertServer2Client($c, $contentType, $syncDB);
                /* Check if we have space left in the message. */
                if (($state->maxMsgSize - $output->getOutputSize() - strlen($clientContent)) < Horde_SyncMl::MSG_TRAILER_LEN) {
                    $backend->logMessage(
                        'Maximum message size ' . $state->maxMsgSize
                        . ' approached during add; current size: '
                        . $output->getOutputSize(), 'DEBUG');
                    if (strlen($clientContent) + Horde_SyncMl::MSG_DEFAULT_LEN > $state->maxMsgSize) {
                        $backend->logMessage(
                            'Data item won\'t fit into a single message. Partial sending not implemented yet. Item will not be sent!', 'WARN');
                        /* @todo: implement partial sending instead of
                         * dropping item! */
                        unset($this->_server_adds[$suid]);
                        continue;
                    }
                    $messageFull = true;
                    $output->outputSyncEnd();
                    $this->_syncsSent += 1;
                    return;
                }

                /* @todo: on SlowSync send Replace instead! */
                // $output->outputSyncCommand($refts == 0 ? 'Replace' : 'Add',
                $cmdId = $output->outputSyncCommand('Add', $clientContent,
                                                    $clientContentType,
                                                    $clientEncodingType,
                                                    null, $suid);
                $this->_server_add_count++;
                $state->serverChanges[$state->messageID][$this->_targetLocURI][$cmdId] = array($suid, 0);
            }
            unset($this->_server_adds[$suid]);
        }

        if ($this->_server_add_count) {
            $this->_expectingMapData = true;
        }

        /* Handle Replaces. */
        $replaces = $this->_server_replaces;
        foreach ($replaces as $suid => $cuid) {
            $syncDB = isset($replaces2[$suid]) ? 'tasks' : $this->_targetLocURI;
            $ct = isset($replaces2[$suid]) ? $contentTypeTasks : $contentType;
            $c = $backend->retrieveEntry($syncDB, $suid, $ct, $fields[$ct]);
            if (is_a($c, 'PEAR_Error')) {
                /* Item in history but not in database. */
                unset($this->_server_replaces[$suid]);
                continue;
            }

            $backend->logMessage(
                "Sending replace from server: $suid", 'DEBUG');
            list($clientContent, $clientContentType, $clientEncodingType) =
                $device->convertServer2Client($c, $contentType, $syncDB);
            /* Check if we have space left in the message. */
            if (($state->maxMsgSize - $output->getOutputSize() - strlen($clientContent)) < Horde_SyncMl::MSG_TRAILER_LEN) {
                $backend->logMessage(
                    'Maximum message size ' . $state->maxMsgSize
                    . ' approached during replace; current size: '
                    . $output->getOutputSize(), 'DEBUG');
                if (strlen($clientContent) + Horde_SyncMl::MSG_DEFAULT_LEN > $state->maxMsgSize) {
                    $backend->logMessage(
                        'Data item won\'t fit into a single message. Partial sending not implemented yet. Item will not be sent!', 'WARNING');
                    /* @todo: implement partial sending instead of
                     * dropping item! */
                    unset($this->_server_replaces[$suid]);
                    continue;
                }
                $messageFull = true;
                $output->outputSyncEnd();
                $this->_syncsSent += 1;
                return;
            }
            $cmdId = $output->outputSyncCommand('Replace', $clientContent,
                                                $clientContentType,
                                                $clientEncodingType,
                                                $cuid, null);
            $this->_server_replace_count++;
            unset($this->_server_replaces[$suid]);
            $state->serverChanges[$state->messageID][$this->_targetLocURI][$cmdId] = array($suid, $cuid);
        }

        /* Finished! Send closing </Sync>. */
        $output->outputSyncEnd();
        $this->_syncsSent += 1;
    }

    /**
     * Retrieves and condenses the changes on the server side since the last
     * synchronization.
     *
     * @param string $syncDB   The database being synchronized.
     * @param array $adds      Will be set with the server-client-uid mappings
     *                         of added objects.
     * @param array $replaces  Will be set with the server-client-uid mappings
     *                         of changed objects.
     * @param array $deletes   Will be set with the server-client-uid mappings
     *                         of deleted objects.
     */
    protected function _retrieveChanges($syncDB, &$adds, &$replaces, &$deletes)
    {
        $adds = $replaces = $deletes = array();
        if ($syncDB == 'configuration') {
            return;
        }
        $result = $GLOBALS['backend']->getServerChanges($syncDB,
                                                        $this->_serverAnchorLast,
                                                        $this->_serverAnchorNext,
                                                        $adds, $replaces, $deletes);
        if (is_a($result, 'PEAR_Error')) {
            $GLOBALS['backend']->logMessage($result, 'ERR');
            return $result;
        }
    }

    /**
     * Notifies the sync that a final has been received by the client.
     *
     * Depending on the current state of the sync this can mean various
     * things:
     * a) Init phase (Alerts) done. Next package contaings actual syncs.
     * b) Sync sending from client done. Next package are maps (or finish
     *    or finish if ONE_WAY_FROM_CLIENT sync
     * c) Maps finished, completly done.
     */
    public function handleFinal(&$output, $debug = false)
    {
        switch ($this->_state) {
        case Horde_SyncMl_Sync::STATE_INIT:
            $state = 'Init';
            break;
        case Horde_SyncMl_Sync::STATE_SYNC:
            $state = 'Sync';
            break;
        case Horde_SyncMl_Sync::STATE_MAP:
            $state = 'Map';
            break;
        case Horde_SyncMl_Sync::STATE_COMPLETED:
            $state = 'Completed';
            break;
        }

        $GLOBALS['backend']->logMessage('Handle <Final> for state ' . $state, 'DEBUG');

        switch ($this->_state) {
        case Horde_SyncMl_Sync::STATE_INIT:
            $this->_state = Horde_SyncMl_Sync::STATE_SYNC;
            break;
        case Horde_SyncMl_Sync::STATE_SYNC:
            /* Received all client Sync data, now we are allowed to send
             * server sync data. */
            if (!$debug) {
                $this->createSyncOutput($output);
            }

            // FROM_CLIENT_SYNC doeesn't require a MAP package:
            if ($this->_syncType == Horde_SyncMl::ALERT_ONE_WAY_FROM_CLIENT ||
                $this->_syncType == Horde_SyncMl::ALERT_REFRESH_FROM_CLIENT ||
                !$this->_expectingMapData) {
                $this->_state = Horde_SyncMl_Sync::STATE_COMPLETED;
            } else {
                $this->_state = Horde_SyncMl_Sync::STATE_MAP;
            }
            break;
        case Horde_SyncMl_Sync::STATE_MAP:
            $this->_state = Horde_SyncMl_Sync::STATE_COMPLETED;
            break;
        }
    }

    /**
     * Returns true if there are still outstanding server sync items to
     * be sent to the client.
     *
     * This is the case if the MaxMsgSize has been reached and the pending
     * elements are to be sent in another message.
     */
    public function hasPendingElements()
    {
        if (!is_array($this->_server_adds)) {
            /* Changes not compiled yet: not pending: */
            return false;
        }

        return (count($this->_server_adds) + count($this->_server_replaces) + count($this->_server_deletes)) > 0;
    }

    public function addSyncReceived()
    {
        $this->_syncsReceived++;
    }

    /* Currently unused */
    public function getSyncsReceived()
    {
        return $this->_syncsReceived;
    }

    public function isComplete()
    {
        return $this->_state == Horde_SyncMl_Sync::STATE_COMPLETED;
    }

    /**
     * Completes a sync once everything is done: store the sync anchors so the
     * next sync can be a delta sync and produce some debug info.
     */
    public function closeSync()
    {
        $GLOBALS['backend']->writeSyncAnchors($this->_targetLocURI,
                                              $this->_clientAnchorNext,
                                              $this->_serverAnchorNext);

        $s = sprintf(
            'Finished sync of database %s. Failures: %d; '
            . 'changes from client (Add, Replace, Delete, AddReplaces): %d, %d, %d, %d; '
            . 'changes from server (Add, Replace, Delete): %d, %d, %d',
            $this->_targetLocURI,
            $this->_errors,
            $this->_client_add_count,
            $this->_client_replace_count,
            $this->_client_delete_count,
            $this->_client_addreplaces,
            $this->_server_add_count,
            $this->_server_replace_count,
            $this->_server_delete_count);
        $GLOBALS['backend']->logMessage($s, 'INFO');
    }

    public function getServerLocURI()
    {
        return $this->_targetLocURI;
    }

    public function getClientLocURI()
    {
        return $this->_sourceLocURI;
    }

    public function getClientAnchorNext()
    {
        return $this->_clientAnchorNext;
    }

    public function getServerAnchorNext()
    {
        return $this->_serverAnchorNext;
    }

    public function getServerAnchorLast()
    {
        return $this->_serverAnchorLast;
    }

    public function createUidMap($databaseURI, $cuid, $suid)
    {
        $device = $GLOBALS['backend']->state->getDevice();

        if ($GLOBALS['backend']->normalize($databaseURI) == 'calendar' &&
            $device->handleTasksInCalendar() &&
            isset($this->_server_task_adds[$suid])) {
            $db = $this->_taskToCalendar($GLOBALS['backend']->normalize($databaseURI));
        } else {
            $db = $databaseURI;
        }

        $GLOBALS['backend']->createUidMap($db, $cuid, $suid);
        $GLOBALS['backend']->logMessage(
            'Created map for client id ' . $cuid . ' and server id ' . $suid
            . ' in database ' . $db, 'DEBUG');
    }

    /**
     * Returns the client ID of server change identified by the change type
     * and server ID.
     *
     * @param string $change  The change type (add, replace, delete).
     * @param string $id      The object's server UID.
     *
     * @return string  The matching client ID or null if none found.
     */
    public function getServerChange($change, $id)
    {
        $property = '_server_' . $change . 's';
        return isset($this->$property[$id]) ? $this->$property[$id] : null;
    }

    /**
     * Sets the client ID of server change identified by the change type and
     * server ID.
     *
     * @param string $change  The change type (add, replace, delete).
     * @param string $sid     The object's server UID.
     * @param string $cid     The object's client UID.
     */
    public function setServerChange($change, $sid, $cid)
    {
        $property = '_server_' . $change . 's';
        $this->$property[$sid] = $cid;
    }

    /**
     * Unsets the server-client-map of server change identified by the change
     * type and server ID.
     *
     * @param string $change  The change type (add, replace, delete).
     * @param string $id      The object's server UID.
     */
    public function unsetServerChange($change, $id)
    {
        $property = '_server_' . $change . 's';
        unset($this->$property[$id]);
    }

    /**
     * Converts a calendar databaseURI to a tasks databaseURI for devices with
     * handleTasksInCalendar.
     */
    protected function _taskToCalendar($databaseURI)
    {
        return str_replace('calendar', 'tasks', $databaseURI);
    }
}
