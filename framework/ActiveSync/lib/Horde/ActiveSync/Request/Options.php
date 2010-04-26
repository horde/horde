<?php
/**
 * ActiveSync Handler for OPTIONS requests
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Request_Options extends Horde_ActiveSync_Request_Base
{
    public function handle()
    {
        Horde_ActiveSync::activeSyncHeader();
        Horde_ActiveSync::versionHeader();
        Horde_ActiveSync::commandsHeader();

        return true;
    }

}
