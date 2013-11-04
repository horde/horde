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
 * @copyright 2011-2013 Horde LLC (http://www.horde.org)
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
 * @copyright 2011-2013 Horde LLC (http://www.horde.org)
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
            '[%s] Handling GETATTACHMENT command.',
            $this->_procid)
        );
        $get = $this->_activeSync->getGetVars();
        if (empty($get['AttachmentName'])) {
            return false;
        }
        $attname = $get['AttachmentName'];

        $this->_logger->info(sprintf(
            '[%s] Fetching attachment: %s',
            $this->_device->id,
            $attname));
        $att = $this->_driver->getAttachment($attname);

        // Send the content-type header in case the attachment is large enough
        // to trigger the output buffer to be flushed.
        $this->_activeSync->contentTypeHeader($att['content-type']);

        // Output the attachment data to the stream.
        if (is_resource($att['data'])) {
            $this->_logger->info('Copying attachment data directly from stream to stream.');
            rewind($att['data']);
        } else {
            $this->_logger->info('Writing attachment data from string to stream.');
        }
        $this->_encoder->getStream()->add($att['data']);

        // Indicate the content-type
        // @TODO This is for BC only. Can remove for H6.
        return $att['content-type'];
    }

}