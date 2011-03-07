<?php
/**
 * This class creates the actual XML data and passes it on to a ContentHandler
 * for optional WBXML encoding.
 *
 * Each member public function creates one type of SyncML artefact (like a
 * Status response).  Currently some of the information is retrieved from
 * state. Maybe remove these dependencies (by providing the data as parameter)
 * for an even cleaner implementation.
 *
 * The Horde_SyncMl_XmlOutput class takes automatically care of creating a
 * unique CmdID for each command created.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_XmlOutput
{
    /**
     * The CmdID provides a unique ID for each command in a syncml packet.
     */
    protected $_msg_CmdID;

    /**
     *  The outputhandler to whom the XML is passed: like
     *  Horde_Xml_Wbxml_Encoder
     */
    public $output;

    protected $_uri;

    /**
     * The final output as procuded by the _output Encoder. Either an
     * XML string or a WBXML string.
     */
    public function getOutput()
    {
        return $this->output->getOutput();
    }

    /**
     * The length of the output as produced by the Encoder. To limit the
     * size of individual messages.
     */
    public function getOutputSize()
    {
        return $this->output->getOutputSize();
    }

    /**
     * To we create wbxml or not?
     */
    public function isWBXML()
    {
        return is_a($this->output, 'Horde_Xml_Wbxml_Encoder');
    }

    public function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new Horde_SyncMl_XmlOutput();
        }
        return $instance;
    }

    public function init(&$theoutputhandler)
    {
        $this->output = $theoutputhandler;
        $this->_msg_CmdID = 1;

    }

    /**
     * Creates a SyncHdr output.
     *
     * Required data is retrieved from state.
     *
     * @param string $respURI  The url of the server endpoint.
     */
    public function outputHeader($respURI)
    {
        $state = $GLOBALS['backend']->state;

        $this->_uriMeta = $state->uriMeta;

        $this->output->startElement($this->_uri, 'SyncHdr');

        $this->output->startElement($this->_uri, 'VerDTD');
        $chars = $state->getVerDTD();
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'VerDTD');

        $this->output->startElement($this->_uri, 'VerProto');
        $chars = $state->getProtocolName();
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'VerProto');

        $this->output->startElement($this->_uri, 'SessionID');
        $this->output->characters($state->sessionID);
        $this->output->endElement($this->_uri, 'SessionID');

        $this->output->startElement($this->_uri, 'MsgID');
        $this->output->characters($state->messageID);
        $this->output->endElement($this->_uri, 'MsgID');

        $this->output->startElement($this->_uri, 'Target');
        $this->output->startElement($this->_uri, 'LocURI');
        // Source URI sent from client is Target for the server
        $this->output->characters($state->sourceURI);
        $this->output->endElement($this->_uri, 'LocURI');
        if ($state->user) {
            $this->output->startElement($this->_uri, 'LocName');
            $this->output->characters($state->user);
            $this->output->endElement($this->_uri, 'LocName');
        }
        $this->output->endElement($this->_uri, 'Target');

        $this->output->startElement($this->_uri, 'Source');
        $this->output->startElement($this->_uri, 'LocURI');
        // Target URI sent from client is Source for the server
        $this->output->characters($state->targetURI);
        $this->output->endElement($this->_uri, 'LocURI');
        $this->output->endElement($this->_uri, 'Source');

        if ($respURI) {
            $this->output->startElement($this->_uri, 'RespURI');
            $this->output->characters($respURI);
            $this->output->endElement($this->_uri, 'RespURI');
        }

        // @Todo: omit this in SyncML1.0?
        $this->output->startElement($this->_uri, 'Meta');

        // Dummy Max MsqSize, this is just put in to make the packet
        // work, it is not a real value.
        $this->output->startElement($this->_uriMeta, 'MaxMsgSize');
        $chars = Horde_SyncMl::SERVER_MAXMSGSIZE; // 1Meg
        $this->output->characters($chars);
        $this->output->endElement($this->_uriMeta, 'MaxMsgSize');


        // MaxObjSize, required by protocol for SyncML1.1 and higher.
        if ($state->version > 0) {
            $this->output->startElement($this->_uriMeta, 'MaxObjSize');
            $this->output->characters(Horde_SyncMl::SERVER_MAXOBJSIZE);
            $this->output->endElement($this->_uriMeta, 'MaxObjSize');
        }
        $this->output->endElement($this->_uri, 'Meta');

        $this->output->endElement($this->_uri, 'SyncHdr');
    }

    public function outputInit()
    {
        $this->_uri = $GLOBALS['backend']->state->getURI();

        $this->output->startElement($this->_uri, 'SyncML', array());
    }

    public function outputBodyStart()
    {
        $this->output->startElement($this->_uri, 'SyncBody', array());
    }

    public function outputFinal()
    {
        $this->output->startElement($this->_uri, 'Final', array());
        $this->output->endElement($this->_uri, 'Final');
    }

    public function outputEnd()
    {
        $this->output->endElement($this->_uri, 'SyncBody', array());
        $this->output->endElement($this->_uri, 'SyncML', array());
    }


    public function outputStatus($cmdRef, $cmd, $data,
                         $targetRef = '', $sourceRef = '',
                         $syncAnchorNext = '',
                         $syncAnchorLast = '')
    {
        $state = $GLOBALS['backend']->state;
        $uriMeta = $state->uriMeta;

        $this->output->startElement($this->_uri, 'Status');
        $this->outputCmdID();

        $this->output->startElement($this->_uri, 'MsgRef');
        $chars = $state->messageID;
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'MsgRef');

        $this->output->startElement($this->_uri, 'CmdRef');
        $chars = $cmdRef;
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'CmdRef');

        $this->output->startElement($this->_uri, 'Cmd');
        $chars = $cmd;
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'Cmd');

        if (!empty($targetRef)) {
            $this->output->startElement($this->_uri, 'TargetRef');
            $this->output->characters($targetRef);
            $this->output->endElement($this->_uri, 'TargetRef');
        }

        if (!empty($sourceRef)) {
            $this->output->startElement($this->_uri, 'SourceRef');
            $this->output->characters($sourceRef);
            $this->output->endElement($this->_uri, 'SourceRef');
        }

        // If we are responding to the SyncHdr and we are not
        // authenticated then request basic authorization.
        if ($cmd == 'SyncHdr' && !$state->authenticated) {
            // Keep Horde_SyncMl::RESPONSE_CREDENTIALS_MISSING, otherwise set to
            // Horde_SyncMl::RESPONSE_INVALID_CREDENTIALS.
            $data = $data == Horde_SyncMl::RESPONSE_CREDENTIALS_MISSING
                ? Horde_SyncMl::RESPONSE_CREDENTIALS_MISSING
                : Horde_SyncMl::RESPONSE_INVALID_CREDENTIALS;

            $this->output->startElement($this->_uri, 'Chal');
            $this->output->startElement($this->_uri, 'Meta');

            $this->output->startElement($uriMeta, 'Type');
            $this->output->characters('syncml:auth-basic');
            $this->output->endElement($uriMeta, 'Type');

            $this->output->startElement($uriMeta, 'Format');
            $this->output->characters('b64');
            $this->output->endElement($uriMeta, 'Format');

            $this->output->endElement($this->_uri, 'Meta');
            $this->output->endElement($this->_uri, 'Chal');

        }

        $this->output->startElement($this->_uri, 'Data');
        $this->output->characters($data);
        $this->output->endElement($this->_uri, 'Data');

        if (!empty($syncAnchorNext) || !empty($syncAnchorNLast)) {
            $this->output->startElement($this->_uri, 'Item');
            $this->output->startElement($this->_uri, 'Data');

            $this->output->startElement($uriMeta, 'Anchor');

            if (!empty($syncAnchorLast)) {
              $this->output->startElement($uriMeta, 'Last');
              $this->output->characters($syncAnchorLast);
              $this->output->endElement($uriMeta, 'Last');
            }

            if (!empty($syncAnchorNext)) {
              $this->output->startElement($uriMeta, 'Next');
              $this->output->characters($syncAnchorNext);
              $this->output->endElement($uriMeta, 'Next');
            }

            $this->output->endElement($uriMeta, 'Anchor');

            $this->output->endElement($this->_uri, 'Data');
            $this->output->endElement($this->_uri, 'Item');
        }

        $this->output->endElement($this->_uri, 'Status');

    }

    public function outputDevInf($cmdRef)
    {
        $state = $GLOBALS['backend']->state;
        $uriMeta = $state->uriMeta;
        $uriDevInf = $state->uriDevInf;

        $this->output->startElement($this->_uri, 'Results');
        $this->outputCmdID();

        $this->output->startElement($this->_uri, 'MsgRef');
        $chars = $state->messageID;
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'MsgRef');

        $this->output->startElement($this->_uri, 'CmdRef');
        $chars = $cmdRef;
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'CmdRef');

        $this->output->startElement($this->_uri, 'Meta');
        $this->output->startElement($uriMeta, 'Type');
        if ($state->wbxml) {
            $this->output->characters(Horde_SyncMl::MIME_SYNCML_DEVICE_INFO_WBXML);
        } else {
            $this->output->characters(Horde_SyncMl::MIME_SYNCML_DEVICE_INFO_XML);
        }

        $this->output->endElement($uriMeta, 'Type');
        $this->output->endElement($this->_uri, 'Meta');

        $this->output->startElement($this->_uri, 'Item');
        $this->output->startElement($this->_uri, 'Source');
        $this->output->startElement($this->_uri, 'LocURI');
        $this->output->characters($state->getDevInfURI());
        $this->output->endElement($this->_uri, 'LocURI');
        $this->output->endElement($this->_uri, 'Source');

        $this->output->startElement($this->_uri, 'Data');

        /* DevInf data is stored in wbxml not as a seperate codepage but
         * rather as a complete wbxml stream as opaque data.  So we need a
         * new Handler. */
        $devinfoutput = $this->output->createSubHandler();

        $devinfoutput->startElement($uriDevInf , 'DevInf');
        $devinfoutput->startElement($uriDevInf , 'VerDTD');
        $devinfoutput->characters($state->getVerDTD());
        $devinfoutput->endElement($uriDevInf , 'VerDTD');
        $devinfoutput->startElement($uriDevInf , 'Man');
        $devinfoutput->characters('The Horde Project (http://www.horde.org/)');
        $devinfoutput->endElement($uriDevInf , 'Man');
        $devinfoutput->startElement($uriDevInf , 'DevID');
        $devinfoutput->characters(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
        $devinfoutput->endElement($uriDevInf , 'DevID');
        $devinfoutput->startElement($uriDevInf , 'DevTyp');
        $devinfoutput->characters('server');
        $devinfoutput->endElement($uriDevInf , 'DevTyp');

        if ($state->version > 0) {
            $devinfoutput->startElement($uriDevInf , 'SupportLargeObjs');
            $devinfoutput->endElement($uriDevInf , 'SupportLargeObjs');

            $devinfoutput->startElement($uriDevInf , 'SupportNumberOfChanges');
            $devinfoutput->endElement($uriDevInf , 'SupportNumberOfChanges');
        }
        $this->_writeDataStore('notes', 'text/plain', '1.0', $devinfoutput);
        $this->_writeDataStore('contacts', 'text/directory', '3.0',
                               $devinfoutput, array('text/x-vcard' => '2.1'));
        $this->_writeDataStore('tasks', 'text/calendar', '2.0', $devinfoutput,
                               array('text/x-vcalendar' => '1.0'));
        $this->_writeDataStore('calendar', 'text/calendar', '2.0',
                               $devinfoutput,
                               array('text/x-vcalendar' => '1.0'));
        $devinfoutput->endElement($uriDevInf , 'DevInf');

        $this->output->opaque($devinfoutput->getOutput());
        $this->output->endElement($this->_uri, 'Data');
        $this->output->endElement($this->_uri, 'Item');
        $this->output->endElement($this->_uri, 'Results');
    }

    /**
     * Writes DevInf data for one DataStore.
     *
     * @param string $sourceref                 Data for <SourceRef> element.
     * @param string $mimetype                  Data for <Rx-Pref><CTType> and
     *                                          <Tx-Pref><CTType>.
     * @param string $version                   Data for <Rx-Pref><VerCT> and
     *                                          <Tx-Pref><VerCT>.
     * @param Horde_Xml_Wbxml_ContentHandler $output  Content handler that will
     *                                          received the output.
     * @param array $additionaltypes            Array of additional types for
     *                                          <Tx> and <Rx>; format
     *                                          array('text/directory' => '3.0')
     */
    protected function _writeDataStore($sourceref, $mimetype, $version, &$output,
                             $additionaltypes = array())
    {
        $uriDevInf = $GLOBALS['backend']->state->uriDevInf;

        $output->startElement($uriDevInf , 'DataStore');
        $output->startElement($uriDevInf , 'SourceRef');
        $output->characters($sourceref);
        $output->endElement($uriDevInf , 'SourceRef');

        $output->startElement($uriDevInf , 'Rx-Pref');
        $output->startElement($uriDevInf , 'CTType');
        $output->characters($mimetype);
        $output->endElement($uriDevInf , 'CTType');
        $output->startElement($uriDevInf , 'VerCT');
        $output->characters($version);
        $output->endElement($uriDevInf , 'VerCT');
        $output->endElement($uriDevInf , 'Rx-Pref');

        foreach ($additionaltypes as $ct => $ctver){
            $output->startElement($uriDevInf , 'Rx');
            $output->startElement($uriDevInf , 'CTType');
            $output->characters($ct);
            $output->endElement($uriDevInf , 'CTType');
            $output->startElement($uriDevInf , 'VerCT');
            $output->characters($ctver);
            $output->endElement($uriDevInf , 'VerCT');
            $output->endElement($uriDevInf , 'Rx');
        }

        $output->startElement($uriDevInf , 'Tx-Pref');
        $output->startElement($uriDevInf , 'CTType');
        $output->characters($mimetype);
        $output->endElement($uriDevInf , 'CTType');
        $output->startElement($uriDevInf , 'VerCT');
        $output->characters($version);
        $output->endElement($uriDevInf , 'VerCT');
        $output->endElement($uriDevInf , 'Tx-Pref');

        foreach ($additionaltypes as $ct => $ctver){
            $output->startElement($uriDevInf , 'Tx');
            $output->startElement($uriDevInf , 'CTType');
            $output->characters($ct);
            $output->endElement($uriDevInf , 'CTType');
            $output->startElement($uriDevInf , 'VerCT');
            $output->characters($ctver);
            $output->endElement($uriDevInf , 'VerCT');
            $output->endElement($uriDevInf , 'Tx');
        }

        $output->startElement($uriDevInf , 'SyncCap');
        // We support all sync Types from 1-6: two way, slow, refresh|update
        // from client|server
        for ($i = 1; $i <= 6; ++$i) {
            $output->startElement($uriDevInf , 'SyncType');
            $output->characters($i);
            $output->endElement($uriDevInf , 'SyncType');
        }
        $output->endElement($uriDevInf , 'SyncCap');
        $output->endElement($uriDevInf , 'DataStore');
    }

    public function outputAlert($alertCode, $clientDB = '', $serverDB = '', $lastAnchor = '', $nextAnchor = '')
    {
        $uriMeta = $GLOBALS['backend']->state->uriMeta;

        $this->output->startElement($this->_uri, 'Alert');
        $this->outputCmdID();

        $this->output->startElement($this->_uri, 'Data');
        $chars = $alertCode;
        $this->output->characters($chars);
        $this->output->endElement($this->_uri, 'Data');

        $this->output->startElement($this->_uri, 'Item');

        if (!empty($clientDB)) {
            $this->output->startElement($this->_uri, 'Target');
            $this->output->startElement($this->_uri, 'LocURI');
            $this->output->characters($clientDB);
            $this->output->endElement($this->_uri, 'LocURI');
            $this->output->endElement($this->_uri, 'Target');
        }

        if (!empty($serverDB)) {
            $this->output->startElement($this->_uri, 'Source');
            $this->output->startElement($this->_uri, 'LocURI');
            $this->output->characters($serverDB);
            $this->output->endElement($this->_uri, 'LocURI');
            $this->output->endElement($this->_uri, 'Source');
        }

        $this->output->startElement($this->_uri, 'Meta');

        $this->output->startElement($uriMeta, 'Anchor');

        $this->output->startElement($uriMeta, 'Last');
        $this->output->characters($lastAnchor);
        $this->output->endElement($uriMeta, 'Last');

        $this->output->startElement($uriMeta, 'Next');
        $this->output->characters($nextAnchor);
        $this->output->endElement($uriMeta, 'Next');

        $this->output->endElement($uriMeta, 'Anchor');


        // MaxObjSize, required by protocol for SyncML1.1 and higher.
        if ($GLOBALS['backend']->state->version > 0) {
            $this->output->startElement($uriMeta, 'MaxObjSize');
            $this->output->characters(Horde_SyncMl::SERVER_MAXOBJSIZE);
            $this->output->endElement($uriMeta, 'MaxObjSize');
        }
        $this->output->endElement($this->_uri, 'Meta');

                $this->output->endElement($this->_uri, 'Item');
        $this->output->endElement($this->_uri, 'Alert');

    }


    public function outputGetDevInf()
    {
        $state = $GLOBALS['backend']->state;
        $uriMeta = $state->uriMeta;

        $this->output->startElement($this->_uri, 'Get');
        $this->outputCmdID();

        $this->output->startElement($this->_uri, 'Meta');
        $this->output->startElement($uriMeta, 'Type');
        if ($state->wbxml) {
            $chars = Horde_SyncMl::MIME_SYNCML_DEVICE_INFO_WBXML;
        } else {
            $chars = Horde_SyncMl::MIME_SYNCML_DEVICE_INFO_XML;
        }
        $this->output->characters($chars);
        $this->output->endElement($uriMeta, 'Type');
        $this->output->endElement($this->_uri, 'Meta');

        $this->output->startElement($this->_uri, 'Item');
        $this->output->startElement($this->_uri, 'Target');
        $this->output->startElement($this->_uri, 'LocURI');
        $this->output->characters($state->getDevInfURI());
        $this->output->endElement($this->_uri, 'LocURI');
        $this->output->endElement($this->_uri, 'Target');
        $this->output->endElement($this->_uri, 'Item');

        $this->output->endElement($this->_uri, 'Get');
    }

    /**
     * Creates a single Sync command
     *
     * @param string $command       The Sync command (Add, Delete, Replace).
     * @param string $content       The actual object content.
     * @param string $contentType   The content's MIME type.
     * @param string $encodingType  The content encoding of the object.
     * @param string $cuid          The client's object UID.
     * @param string $suid          The server's object UID.
     *
     * @return integer  The CmdID used for this command.
     */
    public function outputSyncCommand($command, $content = null, $contentType = null,
                               $encodingType = null, $cuid = null, $suid = null)
    {
        $uriMeta = $GLOBALS['backend']->state->uriMeta;

        $this->output->startElement($this->_uri, $command);
        $this->outputCmdID();

        if (isset($contentType)) {
            $this->output->startElement($this->_uri, 'Meta');
            $this->output->startElement($uriMeta, 'Type');
            $this->output->characters($contentType);
            $this->output->endElement($uriMeta, 'Type');
            $this->output->endElement($this->_uri, 'Meta');
        }

        if (isset($content) || isset($cuid) || isset($suid)) {
            $this->output->startElement($this->_uri, 'Item');
            if ($suid != null) {
                $this->output->startElement($this->_uri, 'Source');
                $this->output->startElement($this->_uri, 'LocURI');
                $this->output->characters($suid);
                $this->output->endElement($this->_uri, 'LocURI');
                $this->output->endElement($this->_uri, 'Source');
            }

            if ($cuid != null) {
                $this->output->startElement($this->_uri, 'Target');
                $this->output->startElement($this->_uri, 'LocURI');
                $this->output->characters($cuid);
                $this->output->endElement($this->_uri, 'LocURI');
                $this->output->endElement($this->_uri, 'Target');
            }

            if (!empty($encodingType)) {
                $this->output->startElement($this->_uri, 'Meta');
                $this->output->startElement($uriMeta, 'Format');
                $this->output->characters($encodingType);
                $this->output->endElement($uriMeta, 'Format');
                $this->output->endElement($this->_uri, 'Meta');
            }
            if (isset($content)) {
                $this->output->startElement($this->_uri, 'Data');
                if($this->isWBXML()) {
                    $this->output->characters($content);
                } else {
                    $device = $GLOBALS['backend']->state->getDevice();
                    if ($device->useCdataTag()) {
                        /* Enclose data in CDATA if possible to avoid */
                        /* problems with &,< and >. */
                        $this->output->characters('<![CDATA[' . $content . ']]>');
                    } else {
                        $this->output->characters($content);
                    }
                }
                $this->output->endElement($this->_uri, 'Data');
            }
            $this->output->endElement($this->_uri, 'Item');
        }

        $this->output->endElement($this->_uri, $command);

        return $this->_msg_CmdID - 1;
    }

    public function outputSyncStart($clientLocURI, $serverLocURI, $numberOfChanges = null)
    {
        $this->output->startElement($this->_uri, 'Sync');
        $this->outputCmdID();

        $this->output->startElement($this->_uri, 'Target');
        $this->output->startElement($this->_uri, 'LocURI');
        $this->output->characters($clientLocURI);
        $this->output->endElement($this->_uri, 'LocURI');
        $this->output->endElement($this->_uri, 'Target');

        $this->output->startElement($this->_uri, 'Source');
        $this->output->startElement($this->_uri, 'LocURI');
        $this->output->characters($serverLocURI);
        $this->output->endElement($this->_uri, 'LocURI');
        $this->output->endElement($this->_uri, 'Source');

        if (is_int($numberOfChanges)) {
            $this->output->startElement($this->_uri, 'NumberOfChanges');
            $this->output->characters($numberOfChanges);
            $this->output->endElement($this->_uri, 'NumberOfChanges');
        }

    }

    public function outputSyncEnd()
    {
        $this->output->endElement($this->_uri, 'Sync');
    }


    //  internal helper functions:

    protected function _outputCmdID()
    {
        $this->output->startElement($this->_uri, 'CmdID');
        $this->output->characters($this->_msg_CmdID);
        $this->_msg_CmdID++;
        $this->output->endElement($this->_uri, 'CmdID');
    }

    /**
     * Output a single <ele>$str</ele> element.
     */
    protected function _singleEle($tag, $str, $uri = null)
    {
        if (empty($uri)) {
            $uri = $this->_uri;
        }
        $this->output->startElement($uri, $tag);
        $this->output->characters($str);
        $this->output->endElement($uri, $tag);
    }
}
