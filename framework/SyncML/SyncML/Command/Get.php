<?php

require_once 'SyncML/Command.php';

/**
 * The SyncML_Command_Get class provides a SyncML implementation of the Get
 * command as defined in SyncML Representation Protocol, version 1.1, section
 * 5.5.7.
 *
 * The Get command is used to retrieve data from the recipient.  The
 * SyncML_Command_Get class responds to a client Get request and returns the
 * DevInf information for the SyncML server.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <fourmont@gmx.de>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncML
 */
class SyncML_Command_Get extends SyncML_Command {

    /**
     * Name of the command.
     *
     * @var string
     */
    var $_cmdName = 'Get';

    /**
     * Implements the actual business logic of the Alert command.
     */
    function handleCommand($debug = false)
    {
        $state = $GLOBALS['backend']->state;

        // Create status response.
        $this->_outputHandler->outputStatus($this->_cmdID, $this->_cmdName,
                                            RESPONSE_OK,
                                            $state->getDevInfURI());
        if (!$state->authenticated) {
            return;
        }

        $this->_outputHandler->outputDevInf($this->_cmdID);
    }

}
