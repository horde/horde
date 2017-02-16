<?php
/**
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */

/**
 * Handle building the body properties when using EAS version 2.5.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Imap_EasMessageBuilder_TwoFive extends Horde_ActiveSync_Imap_EasMessageBuilder
{
    /**
     * Perform all tasks.
     */
    protected function _buildBody()
    {
        $this->_logger->meta('Building EAS 2.5 style Message.');
        $this->_easMessage->body = $this->_mbd->plain['body']->stream;
        $this->_easMessage->bodysize = $this->_mbd->plain['body']->length(true);
        $this->_easMessage->bodytruncated = $this->_mbd->plain['truncated'];
        $this->_easMessage->attachments = $this->_imapMessage->getAttachments($this->_version);
    }

}
