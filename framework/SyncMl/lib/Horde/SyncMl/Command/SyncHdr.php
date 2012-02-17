<?php
/**
 * The Horde_SyncMl_Command_SyncHdr class provides a SyncML implementation of
 * the SyncHdr as defined in SyncML Representation Protocol, version 1.1,
 * section 5.2.2.
 *
 * SyncHdr is not really a sync command, but this class takes advantage of the
 * XML parser in Horde_SyncMl_Command.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Command_SyncHdr extends Horde_SyncMl_Command
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $_cmdName = 'SyncHdr';

    /**
     * Username as specified in the <LocName> element.
     *
     * @var string
     */
    public $user;

    /**
     * Id of this SyncML session as specified in the <SessionID> element.
     *
     * This is not to confuse with the PHP session id, though it is part of
     * the generated PHP session id.
     *
     * @var string
     */
    protected $_sessionID;

    /**
     * SyncML protocol version as specified in the <VerProto> element.
     *
     * 0 for SyncML 1.0, 1 for SyncML 1.1, etc.
     *
     * @var integer
     */
    protected $_version;

    /**
     * Id of the current message as specified in the <MsgID> element.
     *
     * @var integer
     */
    protected $_message;

    /**
     * The target URI as specified by the <Target><LocURI> element.
     *
     * This is normally the URL of the Horde RPC server. However the client is
     * free to send anything.
     *
     * @var string
     */
    protected $_targetURI;

    /**
     * The source URI as specified by the <Source><LocURI> element.
     *
     * @var string
     */
    protected $_sourceURI;

    /**
     * Authentication credential as specified by the <Cred><Data> element.
     *
     * @var string
     */
    public $credData;

    /**
     * Encoding format of $credData as specified in the <Cred><Meta><Format>
     * element like 'b64'.
     *
     * @var string
     */
    public $credFormat;

    /**
     * Media type of $credData as specified in the <Cred><Meta><Type> element
     * like 'auth-basic'.
     *
     * @var string
     */
    public $credType;

    /**
     * Maximum size of a SyncML message in bytes as specified by the
     * <Meta><MaxMsgSize> element.
     *
     * @var integer
     */
    protected $_maxMsgSize;

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
        case 2:
            if ($element == 'VerProto') {
                // </VerProto></SyncHdr></SyncML>
                if (trim($this->_chars) == 'SyncML/1.1') {
                    $this->_version = 1;
                } elseif (trim($this->_chars) == 'SyncML/1.2') {
                    $this->_version = 2;
                } else {
                    $this->_version = 0;
                }
            } elseif ($element == 'SessionID') {
                // </SessionID></SyncHdr></SyncML>
                $this->_sessionID = trim($this->_chars);
            } elseif ($element == 'MsgID') {
                // </MsgID></SyncHdr></SyncML>
                $this->_message = intval(trim($this->_chars));
            }
            break;

        case 3:
            if ($element == 'LocURI') {
                if ($this->_stack[1] == 'Source') {
                    // </LocURI></Source></SyncHdr></SyncML>
                    $this->_sourceURI = trim($this->_chars);
                } elseif ($this->_stack[1] == 'Target') {
                    // </LocURI></Target></SyncHdr></SyncML>
                    $this->_targetURI = trim($this->_chars);
                }
            } elseif ($element == 'LocName') {
                if ($this->_stack[1] == 'Source') {
                    // </LocName></Source></SyncHdr></SyncML>
                    $this->user = trim($this->_chars);
                }
            } elseif ($element == 'Data') {
                    // </Data></Cred></SyncHdr></SyncML>
                if ($this->_stack[1] == 'Cred') {
                    $this->credData = trim($this->_chars);
                }
            } elseif ($element == 'MaxMsgSize') {
                // </MaxMsgSize></Meta></SyncHdr></SyncML>
                $this->_maxMsgSize = intval($this->_chars);
            }
            break;

        case 4:
            if ($this->_stack[1] == 'Cred') {
                if ($element == 'Format') {
                    // </Format></Meta></Cred></SyncHdr></SyncML>
                    $this->credFormat = trim($this->_chars);
                } elseif ($element == 'Type') {
                    // </Type></Meta></Cred></SyncHdr></SyncML>
                    $this->credType = trim($this->_chars);
                }
            }
            break;
        }

        parent::endElement($uri, $element);
    }

    /**
     * Starts the PHP session and instantiates the global Horde_SyncMl_State object
     * if doesn't exist yet.
     */
    public function setupState()
    {
        global $backend;

        $backend->sessionStart($this->_sourceURI, $this->_sessionID);

        if (!$backend->state) {
            $backend->logMessage(
                'New session created: ' . session_id(), 'DEBUG');
            $backend->state = new Horde_SyncMl_State($this->_sourceURI,
                                               $this->user,
                                               $this->_sessionID);
        } else {
            $backend->logMessage('Existing session continued: ' . session_id(), 'DEBUG');
        }

        $backend->state->setVersion($this->_version);
        $backend->state->messageID = $this->_message;
        $backend->state->targetURI = $this->_targetURI;
        $backend->state->sourceURI = $this->_sourceURI;
        $backend->state->sessionID = $this->_sessionID;
        if (!empty($this->_maxMsgSize)) {
            $backend->state->maxMsgSize = $this->_maxMsgSize;
        }

        $backend->setupState();
    }
}
