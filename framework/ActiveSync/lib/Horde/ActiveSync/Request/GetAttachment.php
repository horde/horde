<?php
/**
 * Handle GetAttachment requests.
 *
 * Logic adapted from Z-Push, original copyright notices below.
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


class Horde_ActiveSync_Request_GetAttachment extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    public function handle()
    {
        parent::handle();
        $this->_logger->info('[' . $this->_device->id . '] Handling GETATTACHMENT command.');

        $get = $this->_request->getGetVars();
        $attname = $get['AttachmentName'];
        if (!isset($attname)) {
            return false;
        }

        $this->_logger->debug(sprintf(
            "[%s] Fetching attachement: %s",
            $this->_device->id,
            $attname));

        $att = $this->_driver->getAttachment($attname);

        // Output the attachment data to the stream.
        fwrite($this->_encoder->getStream(), $att[1]);

        // Indicate the content-type
        return $att[0];
    }
}