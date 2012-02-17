<?php
/**
 * ActiveSync Handler for SendMail requests
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL-2.0.
 * Consult COPYING file for details
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
        $rfc822 = file_get_contents('php://input');//$this->_request->getBody();

        return $this->_driver->sendMail($rfc822);
    }
}