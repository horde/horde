<?php
/**
 * Handler for Autoconfigure requests
 *
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * ActiveSync Handler for Autoconfiguration requests.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Request_Autoconfigure extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        header('HTTP/1.1 403');
        return false;
    }

}