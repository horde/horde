<?php
/**
 * The Horde_SyncMl_Command_Map class provides a SyncML implementation of the
 * Map command as defined in SyncML Representation Protocol, version 1.1,
 * section 5.5.8.
 *
 * The Map command is used to update identifier maps.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Command_Map extends Horde_SyncMl_Command
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $_cmdName = 'Map';

    /**
     * Source database of the Map command.
     *
     * @var string
     */
    protected $_sourceLocURI;

    /**
     * Target database of the Map command.
     *
     * @var string
     */
    protected $_targetLocURI;

    /**
     * Recipient map item specifiers.
     *
     * @var array
     */
    protected $_mapTargets = array();

    /**
     * Originator map item specifiers.
     *
     * @var array
     */
    protected $_mapSources = array();

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
            if ($element == 'LocURI') {
                if ($this->_stack[1] == 'Source') {
                    $this->_sourceLocURI = trim($this->_chars);
                } elseif ($this->_stack[1] == 'Target') {
                    $this->_targetLocURI = trim($this->_chars);
                }
            }
            break;

        case 4:
            if ($element == 'LocURI') {
                if ($this->_stack[2] == 'Source') {
                    $this->_mapSources[] = trim($this->_chars);
                } elseif ($this->_stack[2] == 'Target') {
                    $this->_mapTargets[] = trim($this->_chars);
                }
            }
            break;
        }

        parent::endElement($uri, $element);
    }

    /**
     * Implements the actual business logic of the Alert command.
     *
     * @todo No OK response on error.
     */
    public function handleCommand($debug = false)
    {
        if (!$debug && $this->_mapSources) {
            $state = $GLOBALS['backend']->state;
            $sync = &$state->getSync($this->_targetLocURI);
            if (!$state->authenticated) {
                $GLOBALS['backend']->logMessage(
                    'Not authenticated while processing <Map>', 'ERR');
            } else {
                foreach ($this->_mapSources as $key => $source) {
                    $sync->createUidMap($this->_targetLocURI,
                                        $source,
                                        $this->_mapTargets[$key]);
                }
            }
        }

        // Create status response.
        $this->_outputHandler->outputStatus($this->_cmdID, $this->_cmdName,
                                            Horde_SyncMl::RESPONSE_OK,
                                            $this->_targetLocURI,
                                            $this->_sourceLocURI);
    }
}
