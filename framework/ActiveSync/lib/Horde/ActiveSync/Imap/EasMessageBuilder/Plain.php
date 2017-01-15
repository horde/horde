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
 * Handle building the AirSyncBaseBody property when sending plain text only.
 * I.e., BodyTypePreference == Horde_ActiveSync::BODYPREF_TYPE_PLAIN.
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
class Horde_ActiveSync_Imap_EasMessageBuilder_Plain extends Horde_ActiveSync_Imap_EasMessageBuilder
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
        $this->_airsyncBody->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
    }

    /**
     * Perform all tasks.
     */
    protected function _buildBody()
    {
        $this->_logger->info(sprintf(
            '[%s] Building PLAINTEXT Message.',
            $this->_procid)
        );

        if (!empty($this->_mbd->plain['size'])) {
            $this->_buildPlainPart();
        }
        $this->_easMessage->airsyncbasebody = $this->_airsyncBody;
        $this->_easMessage->airsyncbaseattachments =
            $this->_imapMessage->getAttachments($this->_version);
    }

    /**
     * Build the plain body and populate the appropriate message object.
     */
    protected function _buildPlainPart()
    {
        $this->_airsyncBody->estimateddatasize = $this->_mbd->plain['size'];
        $this->_airsyncBody->truncated = $this->_mbd->plain['truncated'];
        $this->_airsyncBody->data = $this->_mbd->plain['body']->stream;
    }

}