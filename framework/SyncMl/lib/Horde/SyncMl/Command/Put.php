<?php
/**
 * The Horde_SyncMl_Command_Put class provides a SyncML implementation of the
 * Put command as defined in SyncML Representation Protocol, version 1.1,
 * section 5.5.10.
 *
 * The Put command is used to transfer data items to a recipient network device
 * or database. The Horde_SyncMl_Command_Put class handles DevInf device
 * information sent by the client.
 *
 * The data is stored in a Horde_SyncMl_DeviceInfo object which is defined in
 * Device.php and then stored in Horde_SyncMl_Device as an attribute.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Command_Put extends Horde_SyncMl_Command
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $_cmdName = 'Put';

    /**
     * The Horde_SyncMl_DeviceInfo object where all parsed <DevInf> content is
     * saved.
     *
     * <DevInf> specifies the type of the source synchronization device.
     *
     * @var Horde_SyncMl_DeviceInfo
     */
    protected $_devinf;

    /**
     * A Horde_SyncMl_DataStore object where the information from the currently
     * parsed <DataStore> section is saved.
     *
     * <DataStore> specifies the properties of a given local datastore.
     *
     * @var Horde_SyncMl_DataStore
     */
    protected $_currentDS;

    /**
     * The MIME content type as specified by the last <CTType> element like
     * text/calendar or text/x-vcard.
     *
     * <CTType> specifies the type of a supported content type.
     *
     * @var string
     */
    protected $_currentCTType;

    /**
     * The version of the MIME content type in $_currentCTType as specified by
     * the last <VerCT> element like 1.0 for text/x-vcalendar or 2.1 for
     * text/x-vcard.
     *
     * <VerCT> specifies the version of a supported content type.
     *
     * @var string
     */
    protected $_VerCT;

    /**
     * A property name of the currently parsed content type (CTType), like
     * 'DTSTART' for text/calendar or 'BDAY' for text/x-vcard.
     *
     * <PropName> specifies a supported property of a given content type.
     *
     * @var string
     */
    protected $_currentPropName;

    /**
     * A property name of the currently parsed property name (PropName), like
     * 'ROLE' for 'ATTENDEE' or 'HOME' for 'ADR'.
     *
     * <ParamName> specifies supported parameters of a given content type
     * property.
     *
     * @var string
     */
    protected $_currentParamName;

    /**
     * The name of the currently parsed DevInf extension (<Ext>) as specified
     * by the XNam element.
     *
     * <XNam> specifies the name of one of the DevInf extension element types.
     *
     * @var string
     */
    protected $_currentXNam;

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

        switch (count($this->_stack)) {
        case 1:
            $this->_devinf = new Horde_SyncMl_DeviceInfo();
            break;

        case 5:
            if ($element == 'DataStore') {
                $this->_currentDS = new Horde_SyncMl_DataStore();
            }
            break;
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
        switch ($element) {
        case 'VerDTD':
        case 'Man':
        case 'Mod':
        case 'OEM':
        case 'FwV':
        case 'SwV':
        case 'HwV':
        case 'DevID':
        case 'DevTyp':
            $this->_devinf->$element = trim($this->_chars);
            break;

        case 'UTC':
        case 'SupportLargeObjs':
        case 'SupportNumberOfChanges':
            $this->_devinf->$element = true;
            break;

        case 'DataStore':
            $this->_devinf->DataStores[] = $this->_currentDS;
            break;

        case 'CTCap':
        case 'Ext':
            // Automatically handled by subelements.
            break;

        case 'SourceRef':
        case 'DisplayName':
        case 'MaxGUIDSize':
            $this->_currentDS->$element = trim($this->_chars);
            break;

        case 'Rx-Pref':
        case 'Rx':
        case 'Tx-Pref':
        case 'Tx':
            $property = str_replace('-', '_', $element);
            $this->_currentDS->{$property}[$this->_currentCTType] = $this->_VerCT;
            break;

        case 'DSMem':
            // Currently ignored, to be done.
            break;

        case 'SyncCap':
            // Automatically handled by SyncType subelement.
            break;

        case 'CTType':
            $this->_currentCTType = trim($this->_chars);
            break;

        case 'PropName':
            $this->_currentPropName = trim($this->_chars);
            // Reset param state.
            unset($this->_currentParamName);
            $this->_devinf->CTCaps[$this->_currentCTType][$this->_currentPropName] = new Horde_SyncMl_Property();
            break;

        case 'ParamName':
            $this->_currentParamName = trim($this->_chars);
            $this->_devinf->CTCaps[$this->_currentCTType][$this->_currentPropName]->Params[$this->_currentParamName] = new Horde_SyncMl_PropertyParameter();
            break;

        case 'ValEnum':
            if (!empty($this->_currentParamName)) {
                // We're in parameter mode.
                $this->_devinf->CTCaps[$this->_currentCTType][$this->_currentPropName]->Params[$this->_currentParamName]->ValEnum[trim($this->_chars)] = true;
            } else {
                $this->_devinf->CTCaps[$this->_currentCTType][$this->_currentPropName]->ValEnum[trim($this->_chars)] = true;
            }
            break;

        case 'DataType':
        case 'Size':
        case 'DisplayName':
            if (!empty($this->_currentParamName)) {
                // We're in parameter mode.
                $this->_devinf->CTCaps[$this->_currentCTType][$this->_currentPropName]->Params[$this->_currentParamName]->{'_' . $element} = trim($this->_chars);
            } else {
                $this->_devinf->CTCaps[$this->_currentCTType][$this->_currentPropName]->{'_' . $element} = trim($this->_chars);
            }
            break;

        case 'XNam':
            $this->_currentXNam = trim($this->_chars);
            break;
        case 'XVal':
            $this->_devinf->Exts[$this->_currentXNam][] = trim($this->_chars);
            break;

        case 'VerCT':
            $this->_VerCT = trim($this->_chars);
            break;
        case 'SyncType':
            $this->_currentDS->SyncCap[trim($this->_chars)] = true;
            break;
        }

        parent::endElement($uri, $element);
    }

    /**
     * Implements the actual business logic of the Alert command.
     */
    public function handleCommand($debug = false)
    {
        $state = $GLOBALS['backend']->state;

        // Store received data.
        $state->deviceInfo = $this->_devinf;

        // Log DevInf object.
        $GLOBALS['backend']->logFile(Horde_SyncMl_Backend::LOGFILE_DEVINF,
                                     var_export($this->_devinf, true));

        // Create status response.
        $this->_outputHandler->outputStatus($this->_cmdID, $this->_cmdName,
                                            Horde_SyncMl::RESPONSE_OK, '',
                                            $state->getDevInfURI());
    }
}
