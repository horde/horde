<?php
/**
 * The Horde_SyncMl_Command_Sync class provides a SyncML implementation of the
 * Sync command as defined in SyncML Representation Protocol, version 1.1,
 * section 5.5.15.
 *
 * The Sync command is used to indicate a data synchronization operation. The
 * command handler for the Sync command is the central class to dispatch sync
 * messages.
 *
 * During parsing of the received XML, the actual sync commands (Add, Replace,
 * Delete) from the client are stored in the $_syncElements attribute.  When
 * the output method of Horde_SyncMl_Command_Sync is called, these elements are
 * processed and the resulting status messages created.  Then the server
 * modifications are sent back to the client by the handleSync() method which
 * is called from within the output method.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Command_Sync extends Horde_SyncMl_Command
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $_cmdName = 'Sync';

    /**
     * Source database of the <Sync> command.
     *
     * @var string
     */
    protected $_sourceURI;

    /**
     * Target database of the <Sync> command.
     *
     * @var string
     */
    protected $_targetURI;

    /**
     * Horde_SyncMl_SyncElement object for the currently parsed sync command.
     *
     * @var Horde_SyncMl_SyncElement
     */
    protected $_curItem;

    /**
     * List of all Horde_SyncMl_SyncElement objects that have parsed.
     *
     * @var array
     */
    protected $_syncElements = array();

    /**
     * The MIME content type of the currently parsed sync command as specified
     * by the <Type> element inside a <Meta> section.
     *
     * @var string
     */
    protected $_contentType = 'text/plain';

    /**
     * Encoding format of the content as specified in the <Meta><Format>
     * element, like 'b64'.
     *
     * @var string
     */
    protected $_contentFormat = 'chr';

    /**
     * The command ID (<CmdID>) of the currently parsed sync command.
     *
     * This is different from the command ID of the <Sync> command itself.
     *
     * @var integer
     */
    protected $_itemCmdID;

    /**
     * Name of the currently parsed sync command, like 'Add'.
     *
     * @var string
     */
    protected $_elementType;

    /**
     * Whether a <MoreData> element has indicated that the sync command is
     * split into several SyncML message chunks.
     *
     * @var boolean
     */
    protected $_itemMoreData;

    /**
     * The size of the data item of the currently parsed sync command in bytes
     * as specified by a <Size> element.
     *
     * @var integer
     */
    protected $_itemSize;

    /**
     * Start element handler for the XML parser, delegated from
     * Horde_SyncMl_ContentHandler::startElement().
     *
     * @param string $uri      The namespace URI of the element.
     * @param string $element  The element tag name.
     * @param array $attrs     A hash with the element's attributes.
     */
    public function startElement($uri, $element, $attrs)
    {
        parent::startElement($uri, $element, $attrs);
        $state = $GLOBALS['backend']->state;

        switch (count($this->_stack)) {
        case 2:
            if ($element == 'Replace' ||
                $element == 'Add' ||
                $element == 'Delete') {
                $this->_contentType = 'text/plain';
                $this->_elementType = $element;
                $this->_itemSize = null;
            }
            break;

        case 3:
            if ($element == 'Item') {
                if (isset($state->curSyncItem)) {
                    // Copy from state in case of <MoreData>.
                    $this->_curItem = $state->curSyncItem;
                    // Set CmdID to the current CmdId, not the initial one
                    // from the first message.
                    $this->_curItem->cmdID = $this->_itemCmdID;
                    unset($state->curSyncItem);
                } else {
                    $this->_curItem = new Horde_SyncMl_SyncElement(
                        $state->getSync($this->_targetURI),
                        $this->_elementType,
                        $this->_itemCmdID,
                        $this->_itemSize);
                }
                $this->_itemMoreData = false;
            }
        }
    }

    /**
     * End element handler for the XML parser, delegated from
     * Horde_SyncMl_ContentHandler::endElement().
     *
     * @param string $uri      The namespace URI of the element.
     * @param string $element  The element tag name.
     */
    public function endElement($uri, $element)
    {
        switch (count($this->_stack)) {
        case 3:
            switch ($element) {
            case 'LocURI':
                if (!isset($this->_currentSyncElement)) {
                    if ($this->_stack[1] == 'Source') {
                        $this->_sourceURI = trim($this->_chars);
                    } elseif ($this->_stack[1] == 'Target') {
                        $this->_targetURI = trim($this->_chars);
                    }
                }
                break;

            case 'Item':
                if ($this->_itemMoreData) {
                    // Store to continue in next session.
                    $GLOBALS['backend']->state->curSyncItem = $this->_curItem;
                } else {
                    // Finished. Store to syncElements[].
                    if (empty($this->_curItem->contentType)) {
                        $this->_curItem->contentType = $this->_contentType;
                    }
                    if (empty($this->_curItem->contentFormat)) {
                        $this->_curItem->contentFormat = $this->_contentFormat;
                    }

                    $this->_syncElements[] = $this->_curItem;
                    // @todo: check if size matches strlen(content) when
                    // size>0, esp. in case of <MoreData>.
                    unset($this->_curItem);
                }
                break;

            case 'CmdID':
                $this->_itemCmdID = trim($this->_chars);
                break;
            }
            break;

        case 4:
            switch ($element) {
            case 'Format':
                if ($this->_stack[2] == 'Meta') {
                    $this->_contentFormat = trim($this->_chars);
                }
                break;
            case 'Type':
                if ($this->_stack[2] == 'Meta') {
                    $this->_contentType = trim($this->_chars);
                }
                break;
            case 'Data':
                // Don't trim, because we have to check the raw content's size.
                $this->_curItem->content .= $this->_chars;
                break;
            case 'MoreData':
                $this->_itemMoreData = true;
                break;
            case 'Size':
                $this->_itemSize = $this->_chars;
                break;
            }
            break;

        case 5:
            switch ($element) {
            case 'LocURI':
                if ($this->_stack[3] == 'Source') {
                    $this->_curItem->cuid = trim($this->_chars);
                } elseif ($this->_stack[3] == 'Target') {
                    // Not used: we ignore "suid proposals" from client.
                }
                break;

            case 'Format':
                if ($this->_stack[3] == 'Meta') {
                    $this->_curItem->contentFormat = trim($this->_chars);
                }
                break;

            case 'Type':
                $this->_curItem->contentType = trim($this->_chars);
                break;
            }
            break;

        case 6:
            if ($element == 'Type') {
                $this->_curItem->contentType = trim($this->_chars);
            }
            break;
        }

        parent::endElement($uri, $element);
    }

    /**
     * Implements the actual business logic of the Sync command.
     */
    public function handleCommand($debug = false)
    {
        $state = $GLOBALS['backend']->state;

        // Handle unauthenticated first.
        if (!$state->authenticated) {
            $this->_outputHandler->outputStatus($this->_cmdID, $this->_cmdName,
                                                Horde_SyncMl::RESPONSE_INVALID_CREDENTIALS);
            return;
        }

        if ($debug) {
            $sync = &$state->getSync($this->_targetURI);
            $sync = new Horde_SyncMl_Sync(Horde_SyncMl::ALERT_TWO_WAY,
                                    $this->_targetURI,
                                    $this->_sourceURI,
                                    0, 0, 0);
        } else {
            $sync = &$state->getSync($this->_targetURI);
            $sync->addSyncReceived();

            if (!is_object($sync)) {
                $GLOBALS['backend']->logMessage(
                    'No sync object found for URI ' . $this->_targetURI, 'ERR');
                // @todo: create meaningful status code here.
            }
        }

        /* @todo: Check: do we send a status for every sync or only once after
         * one sync is completed?
         * SE K750 expects Status response to be sent before Sync output
         * by server is produced. */
        $this->_outputHandler->outputStatus($this->_cmdID, $this->_cmdName,
                                            Horde_SyncMl::RESPONSE_OK,
                                            $this->_targetURI,
                                            $this->_sourceURI);

        // Here's where client modifications are processed.
        $device = $state->getDevice();
        $omit = $device->omitIndividualSyncStatus();
        foreach ($this->_syncElements as $item) {
            $result = $sync->handleClientSyncItem($this->_outputHandler, $item);
            if (!$omit) {
                $this->_outputStatus($item);
            }
        }

        if ($this->_itemMoreData) {
            // Last item had <MoreData> element, produce appropriate response.
            $this->_outputHandler->outputStatus(
                $state->curSyncItem->cmdID,
                $state->curSyncItem->elementType,
                Horde_SyncMl::RESPONSE_CHUNKED_ITEM_ACCEPTED_AND_BUFFERED,
                '',
                $state->curSyncItem->cuid);
            // @todo: check if we have to send Alert NEXT_MESSAGE here!
        }
    }

    /**
     * Creates the <Status> response for one Add|Replace|Delete SyncElement.
     *
     * @param Horde_SyncMl_SyncElement $element  The element for which the status is
     *                                     to be created.
     */
    protected function _outputStatus($element)
    {
        // @todo: produce valid status
        $this->_outputHandler->outputStatus($element->cmdID,
                                            $element->elementType,
                                            $element->responseCode,
                                            '',
                                            $element->cuid);
    }
}
