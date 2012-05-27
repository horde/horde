<?php
/**
 * Handle GetAttachment requests.
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle GetAttachment requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
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
        $get = $this->_activeSync->getGetVars();
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