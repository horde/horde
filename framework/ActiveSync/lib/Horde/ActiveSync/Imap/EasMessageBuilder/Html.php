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
 * Handle building the AirSyncBaseBody property when sending HTML only.
 * I.e., BodyTypePreference == Horde_ActiveSync::BODYPREF_TYPE_HMTL.
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
class Horde_ActiveSync_Imap_EasMessageBuilder_Html extends Horde_ActiveSync_Imap_EasMessageBuilder
{
    /**
     *
     * @param Horde_ActiveSync_Imap_Message $imap_message  The IMAP message object.
     * @param array                         $options       Options array.
     * @param Horde_Log_Logger $logger                     The logger.
     */
    public function __construct(
        Horde_ActiveSync_Imap_Message $imap_message, array $options, $logger)
    {
        parent::__construct($imap_message, $options, $logger);

        if (!$this->_mbd->html) {
            $this->_airsyncBody->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
            $this->_mbd->html = array(
                'body' => $this->_mbd->plain['body'],
                'estimated_size' => $this->_mbd->plain['size'],
                'truncated' => $this->_mbd->plain['truncated']
            );
        } else {
            $this->_airsyncBody->type = Horde_ActiveSync::BODYPREF_TYPE_HTML;
        }
    }

    /**
     * Perform all tasks.
     */
    protected function _buildBody()
    {
        $this->_logger->meta('Building HTML Message.');
        $this->_buildHtmlPart();
        $this->_easMessage->airsyncbasebody = $this->_airsyncBody;
        $this->_easMessage->airsyncbaseattachments =
            $this->_imapMessage->getAttachments($this->_version);
    }

    /**
     * Build the HTML body and populate the appropriate message object.
     */
    protected function _buildHtmlPart()
    {
        if (!empty($this->_mbd->html['estimated_size'])) {
            $this->_airsyncBody->estimateddatasize = $this->_mbd->html['estimated_size'];
            $this->_airsyncBody->truncated = $this->_mbd->html['truncated'];
            $this->_airsyncBody->data = $this->_mbd->html['body']->stream;
        }
    }

}
