<?php
/**
 * ActiveSync Handler for SendMail requests
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Request_SendMail extends Horde_ActiveSync_Request_Base
{

    /**
     *
     * @param $protocolversion
     * @return unknown_type
     */
    public function handle()
    {
        $this->_logger->info('[' . $this->_device->id . '] Handling SendMail command.');

        // All that happens here is that we receive an rfc822 message on stdin
        // and just forward it to the backend. We provide no output except for
        // an OK http reply
        $rfc822 = $this->_request->getBody();

        return $this->_driver->sendMail($rfc822);
    }
}