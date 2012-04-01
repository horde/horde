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
     * @return string  The content-type of the attachment
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            "[%s] Handling GETATTACHMENT command.",
            $this->_device->id)
        );
        $get = $this->_request->getGetVars();
        if (empty($get['AttachmentName'])) {
            return false;
        }
        $attname = $get['AttachmentName'];

        $this->_logger->debug(sprintf(
            "[%s] Fetching attachement: %s",
            $this->_device->id,
            $attname));

        $att = $this->_driver->getAttachment($attname);

        // Output the attachment data to the stream.
        if (is_resource($att['data'])) {
            $this->_logger->debug('Copying attachment data directly from stream to stream.');
            rewind($att['data']);
            stream_copy_to_stream($att['data'], $this->_encoder->getStream());
        } else {
            $this->_logger->debug('Writing attachment data from string to stream.');
            fwrite($this->_encoder->getStream(), $att['data']);
        }

        // Indicate the content-type
        return $att['content-type'];
    }

}