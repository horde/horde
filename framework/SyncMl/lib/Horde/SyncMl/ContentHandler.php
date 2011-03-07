<?php
/**
 * There is one global object used by SyncML:
 * 1) $GLOBALS['backend']
 *    Backend to handle the communication with the datastore.
 *
 * @todo: Main Todos:
 * - ensure that no server data is written for Horde_SycnMl::ALERT_ONE_WAY_FROM_SERVER
 *   even when client sends data (security!)
 * - consinstant naming of clientSyncDB (currently called targetLocURI, db
 *   or synctype)
 * - tackle the AddReplace issue: when a Replace is issued (i.e. during
 *   SlowSync) the server should first check if the entry already exists.
 *   Like: does a calendar entry with the same timeframe, same subject and
 *   location exist. If so, the replace should replace this value rather than
 *   create a new one as a duplicate.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package SyncMl
 */
class Horde_SyncMl_ContentHandler
{
    /**
     * Stack for holding the xml elements during creation of the object from
     * the xml event flow.
     *
     * @var array
     */
    protected $_Stack = array();

    /**
     * @var string
     */
    protected $_chars;

    /**
     * Instance of Horde_SyncMl_Command. Events are passed through to this
     * ContentHandler.
     *
     * @var Horde_SyncMl_Command
     */
    protected $_currentCommand;

    /**
     * Whether we received a final element in this message.
     */
    protected $_gotFinal = false;

    protected $_xmlWriter;

    protected $_wbxmlparser = null;

    /**
     * The response URI as sent by the server.
     *
     * This is the endpoint URL of the RPC server.
     *
     * @var string
     */
    protected $_respURI;

    public $debug = false;

    public function __construct()
    {
        /* Set to true to indicate that we expect another message from the
         * client. If this is still false at the end of processing, the sync
         * session is finished and we can close the session. */
        $GLOBALS['message_expectresponse'] = false;
    }

    /**
     * Here's were all the processing takes place: gets the SyncML request
     * data and returns a SyncML response. The only thing that needs to be in
     * place before invoking this public function is a working backend.
     *
     * @param string $request      The raw request string.
     * @param string $contentType  The MIME content type of the request. Should
     *                             be either application/vnd.syncml or
     *                             application/vnd.syncml+wbxml.
     * @param string $respURI      The url of the server endpoint. Will be
     *                             returned in the RespURI element.
     */
    public function process($request, $contentType, $respURI = null)
    {
        $isWBXML = $contentType =='application/vnd.syncml+wbxml';
        $this->_respURI = $respURI;

        /* Catch any errors/warnings/notices that may get thrown while
         * processing. Don't want to let anything go to the client that's not
         * part of the valid response. */
        ob_start();

        $GLOBALS['backend']->logFile(Horde_SyncMl_Backend::LOGFILE_CLIENTMESSAGE, $request, $isWBXML);

        if (!$isWBXML) {
            /* XML code. */

            /* try to extract charset from XML text */
            if (preg_match('/^\s*<\?xml[^>]*encoding\s*=\s*"([^"]*)"/i',
                           $request, $m)) {
                $charset = $m[1];
            } else {
                $charset = 'UTF-8';
            }

            $GLOBALS['backend']->setCharset($charset);

            /* Init output handler. */
            $this->_xmlWriter = &Horde_SyncMl_XmlOutput::singleton();
            /* Horde_Xml_Wbxml_ContentHandler Is a class that produces plain XML
             * output. */
            $this->_xmlWriter->init(new Horde_Xml_Wbxml_ContentHandler());

            /* Create the XML parser and set method references. */
            $parser = xml_parser_create_ns($charset);
            xml_set_object($parser, $this);
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
            xml_set_element_handler($parser, '_startElement', '_endElement');
            xml_set_character_data_handler($parser, '_characters');
            xml_set_processing_instruction_handler($parser, '');
            xml_set_external_entity_ref_handler($parser, '');

            /* Here we go: fire off events: */
            if (!xml_parse($parser, $request)) {
                $s = sprintf('XML error: %s at line %d',
                             xml_error_string(xml_get_error_code($parser)),
                             xml_get_current_line_number($parser));
                $GLOBALS['backend']->logMessage($s, 'ERR');
                xml_parser_free($parser);
                return new PEAR_Error($s);
            }

            xml_parser_free($parser);

        } else {
            /* The decoder works like the parser in the XML code above: It
             * parses the input and calls the callback functions of $this. */
            $this->_wbxmlparser = new Horde_Xml_Wbxml_Decoder();
            $this->_wbxmlparser->setContentHandler($this);

            /* Init output handler. */
            $this->_xmlWriter = &Horde_SyncMl_XmlOutput::singleton();
            $this->_xmlWriter->init(new Horde_Xml_Wbxml_Encoder());

            /* Here we go: fire off events: */
            $r = $this->_wbxmlparser->decode($request);
        }

        $id = @session_id();
        $sessionclose = empty($id);

        $output = $this->getOutput();
        if (!$isWBXML) {
            $output = '<?xml version="1.0" encoding="' . $charset . '"?>' . $output;
        }
        $GLOBALS['backend']->logFile(Horde_SyncMl_Backend::LOGFILE_SERVERMESSAGE, $output, $isWBXML, $sessionclose);

        /* Clear the output buffer that we started above, and log anything
         * that came up for later debugging. */
        $errorLogging = ob_get_clean();

        if (!empty($errorLogging)) {
            $GLOBALS['backend']->logMessage('Caught output: ' . $errorLogging, 'WARN');
        }

        return $output;
    }

    /*
     * CONTENTHANDLER CALLBACK FUNCTIONS
     * The following functions are callback functions that are called by the
     * XML parser. The XML and WBXML parsers use slightly different functions,
     * so the methods are duplicated.
     */

    /**
     * Returns the XML|WBXML output once processing is finished.
     *
     * @return string  The XML or WBXML output data.
     */
    public function getOutput()
    {
        return $this->_xmlWriter->getOutput();
    }

    /**
     * Callback public function called by XML parser.
     */
    protected function _startElement($parser, $tag, $attributes)
    {
        list($uri, $name) = $this->_splitURI($tag);
        $this->startElement($uri, $name, $attributes);
    }

    /**
     * Callback public function called by XML parser.
     */
    protected function _characters($parser, $chars)
    {
        $this->characters($chars);
    }

    /**
     * Callback public function called by XML parser.
     */
    protected function _endElement($parser, $tag)
    {
        list($uri, $name) = $this->_splitURI($tag);
        $this->endElement($uri, $name);
    }

    /**
     * Splits an URI as provided by the XML parser.
     */
    protected function _splitURI($tag)
    {
        $parts = explode(':', $tag);
        $name = array_pop($parts);
        $uri = implode(':', $parts);
        return array($uri, $name);
    }

    /**
     * Callback public function called by WBXML parser.
     */
    public function startElement($uri, $element, $attrs)
    {
        $this->_Stack[] = $element;

        // <SyncML>: don't do anyhting yet
        if (count($this->_Stack) == 1) {
            return;
        }

        // header or body?
        if ($this->_Stack[1] == 'SyncHdr') {
            if (count($this->_Stack) == 2) {
                $this->_currentCommand = new Horde_SyncMl_Command_SyncHdr($this->_xmlWriter);
            }
            $this->_currentCommand->startElement($uri, $element, $attrs);
        } else {
            switch (count($this->_Stack)) {
            case 2:
                 // <SyncBody>: do nothing yet
                 break;
            case 3:
                // new Command:
                // <SyncML><SyncBody><[Command]>
                $this->_currentCommand = &Horde_SyncMl_Command::factory($element,$this->_xmlWriter);
                $this->_currentCommand->startElement($uri, $element, $attrs);
                break;
            default:
                // pass on to current command handler:
                // <SyncML><SyncBody><Command><...>
                $this->_currentCommand->startElement($uri, $element, $attrs);
                break;
            }
        }
    }

    /**
     * Callback public function called by WBXML parser.
     */
    public function endElement($uri, $element)
    {
        // </SyncML>: everything done already by end of SyncBody
        if (count($this->_Stack) == 1) {
            return;
        }
        // header or body?
        if ($this->_Stack[1] == 'SyncHdr') {
            switch (count($this->_Stack)) {
            case 2:
                // </SyncHdr> end of header
                $this->handleHeader($this->_currentCommand);
                if ($this->debug) {
                    var_dump($this->_currentCommand);
                }
                unset($this->_currentCommand);
                break;
            default:
                // pass on to command handler:
                $this->_currentCommand->endElement($uri, $element);
                break;
            }
        } else {
            switch (count($this->_Stack)) {
            case 2:
                // </SyncBody> end of SyncBody. Finish everything:
                $this->handleEnd();
                break;
            case 3:
                // </[Command]></SyncBody></SyncML>
                // Command finished. Complete parsing and pass on to Handler
                $this->_currentCommand->endElement($uri, $element);
                $this->handleCommand($this->_currentCommand);
                if ($this->debug) {
                    var_dump($this->_currentCommand);
                }
                unset($this->_currentCommand);
                break;
            default:
                // </...></[Command]></SyncBody></SyncML>
                // pass on to command handler:
                $this->_currentCommand->endElement($uri, $element);
                break;
            }
        }

        if (isset($this->_chars)) {
            unset($this->_chars);
        }

        array_pop($this->_Stack);
    }

    /**
     * Callback public function called by WBXML parser.
     */
    public function characters($str)
    {
        if (isset($this->_currentCommand)) {
            $this->_currentCommand->characters($str);
        } else {
            if (isset($this->_chars)) {
                $this->_chars = $this->_chars . $str;
            } else {
                $this->_chars = $str;
            }
        }
    }

    /*
     * PROCESSING FUNCTIONS
     *
     * The following functions are called by the callback functions
     * and do the actual processing.
     */

    /**
     * Handles the header logic.
     *
     * Invoked after header is parsed.
     */
    public function handleHeader(&$hdr)
    {
        if (is_object($this->_wbxmlparser)) {
            /* The WBXML parser only knows about the charset once parsing is
             * started. So setup charset now. */
            $this->_xmlWriter->_output->setVersion($this->_wbxmlparser->getVersion());
            $this->_xmlWriter->_output->setCharset($this->_wbxmlparser->getCharsetStr());
            $GLOBALS['backend']->setCharset($this->_wbxmlparser->getCharsetStr());
        }

        /* Start the session. */
        $hdr->setupState();
        $state = $GLOBALS['backend']->state;
        $state->wbxml = $this->_xmlWriter->isWBXML();

        /* Check auth. */
        if (!$state->authenticated) {
            $auth = $GLOBALS['backend']->checkAuthentication(
                $hdr->user, $hdr->credData, $hdr->credFormat, $hdr->credType);
            if ($auth !== false) {
                $state->authenticated = true;
                $statuscode = Horde_SycnMl::RESPONSE_AUTHENTICATION_ACCEPTED;
                $state->user = $auth;
                $GLOBALS['backend']->setUser($auth);
            } else {
                if (!$hdr->credData) {
                    $statuscode = Horde_SycnMl::RESPONSE_CREDENTIALS_MISSING;
                } else {
                    $statuscode = Horde_SycnMl::RESPONSE_INVALID_CREDENTIALS;
                }
                $GLOBALS['backend']->logMessage('Invalid authentication', 'DEBUG');
            }
        } else {
            $statuscode = Horde_SycnMl::RESPONSE_OK;
            $GLOBALS['backend']->setUser($state->user);
        }

        /* Create <SyncML>. */
        $this->_xmlWriter->outputInit();

        /* Got the state; now write our SyncHdr header. */
        $this->_xmlWriter->outputHeader($this->_respURI);

        /* Creates <SyncBody>. */
        $this->_xmlWriter->outputBodyStart();

        /* Output status for SyncHdr. */
        $this->_xmlWriter->outputStatus('0', 'SyncHdr', $statuscode,
                                        $state->targetURI,
                                        $state->sourceURI);

        /* Debug logging string. */
        $str = 'Authenticated: ' . ($state->authenticated ? 'yes' : 'no')
            . '; version: ' . $state->getVerDTD()
            . '; message ID: ' . $state->messageID
            . '; source URI: ' . $state->sourceURI
            . '; target URI: ' . $state->targetURI
            . '; user: ' . $state->user
            . '; charset: ' . $GLOBALS['backend']->getCharset()
            . '; wbxml: ' . ($state->wbxml ? 'yes' : 'no');

        $GLOBALS['backend']->logMessage($str, 'DEBUG');
    }

    /**
     * Processes one command after it has been completely parsed.
     *
     * Invoked after a command is parsed.
     */
    public function handleCommand(&$cmd)
    {
        $name = $cmd->getCommandName();
        if ($name != 'Status' && $name != 'Map' && $name != 'Final' &&
            $name != 'Sync' && $name != 'Results') {
            /* We've got to do something! This can't be the last packet. */
            $GLOBALS['message_expectresponse'] = true;
        }
        if ($name == 'Final') {
            $this->_gotFinal = true;
        }
        /* Actual processing takes place here. */
        $cmd->handleCommand($this->debug);
    }

    /**
     * Finishes the response.
     *
     * Invoked after complete message is parsed.
     */
    public function handleEnd()
    {
        global $messageFull;

        $state = $GLOBALS['backend']->state;

        /* If there's pending sync data and space left in the message, send
         * data now. */
        if ($messageFull || $state->hasPendingSyncs()) {
            /* still something to do: don't close session. */
            $GLOBALS['message_expectresponse'] = true;
        }

        if (!$messageFull &&
            count($p = $state->getPendingSyncs()) > 0) {
            foreach ($p as $pendingSync) {
                if (!$messageFull) {
                   $GLOBALS['backend']->logMessage(
                       'Continuing sync for syncType ' . $pendingSync, 'DEBUG');
                    $sync = &$state->getSync($pendingSync);
                    $sync->createSyncOutput($this->_xmlWriter);
                }
            }
        }

        if (isset($state->curSyncItem)) {
            $this->_xmlWriter->outputAlert(
                Horde_SycnMl::ALERT_NO_END_OF_DATA,
                $state->curSyncItem->sync->getClientLocURI(),
                $state->curSyncItem->sync->getServerLocURI(),
                $state->curSyncItem->sync->getServerAnchorLast(),
                $state->curSyncItem->sync->getServerAnchorNext());
        }

        /* Don't send the final tag if we haven't sent all sync data yet. */
        if ($this->_gotFinal) {
            if (!$messageFull &&
                !$state->hasPendingSyncs()) {
                /* Create <Final></Final>. */
                $this->_xmlWriter->outputFinal();
                $GLOBALS['backend']->logMessage('Sending <Final> to client', 'DEBUG');
                $state->delayedFinal = false;
            } else {
                $GLOBALS['message_expectresponse'] = true;
                /* Remember to send a Final. */
                $state->delayedFinal = true;
            }
        } elseif ($state->delayedFinal) {
            if (!$messageFull &&
                !$state->hasPendingSyncs()) {
                /* Create <Final></Final>. */
                $this->_xmlWriter->outputFinal();
                $GLOBALS['backend']->logMessage(
                    'Sending delayed <Final> to client', 'DEBUG');
                $state->delayedFinal = false;
            } else {
                $GLOBALS['message_expectresponse'] = true;
            }
        }

        /* Create </SyncML>. Message is finished now! */
        $this->_xmlWriter->outputEnd();

        if ($this->_gotFinal &&
            !$GLOBALS['message_expectresponse'] &&
            $state->isAllSyncsComplete()) {
            /* This packet did not contain any real actions, just status and
             * map. This means we're done. The session can be closed and the
             * anchors saved for the next sync. */
            foreach ($state->getSyncs() as $sync) {
                $sync->closeSync();
            }
            $GLOBALS['backend']->logMessage('Session completed and closed', 'DEBUG');

            /* Session can be closed here. */
            $GLOBALS['backend']->sessionClose();
        } else {
            $GLOBALS['backend']->logMessage('Return message completed', 'DEBUG');
        }
    }
}
