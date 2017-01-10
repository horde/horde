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
 * Handle building the AirSyncBaseBody property when sending a full MIME mail
 * structure. I.e., BodyTypePreference == Horde_ActiveSync::BODYPREF_TYPE_MIME.
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
class Horde_ActiveSync_Imap_EasMessageBuilder_Mime extends Horde_ActiveSync_Imap_EasMessageBuilder
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
        $this->_easMessage->airsyncbasenativebodytype = $this->_mbd->html
            ? Horde_ActiveSync::BODYPREF_TYPE_HTML
            : Horde_ActiveSync::BODYPREF_TYPE_PLAIN;

        $this->_airsyncBody->type = Horde_ActiveSync::BODYPREF_TYPE_MIME;
    }

    /**
     * Perform all tasks.
     */
    protected function _buildBody()
    {
        $this->_buildMessage();
        $this->_doTruncation();
        $this->_easMessage->airsyncbasebody = $this->_airsyncBody;
    }

    /**
     * Determine if we need plain and/or html parts and if we have attachments.
     * Builds appropriate MIME parts and populates $this->_easMessage properties
     * accordingly.
     */
    protected function _buildMessage()
    {
        $this->_logger->info(sprintf(
            '[%s] Sending MIME Message.',
            $this->_procid)
        );

        if (!$this->_canModify()) {
            $this->_buildEncrypted();
        }

        // The base MIME part.
        $mime = new Horde_Mime_Part();

        // Get a text/plain part if needed.
        if ($this->_mbd->plain) {
            $plain_mime = $this->_buildPlainMime();
        }

        // Get a text/html part if needed.
        if ($this->_mbd->html) {
            $html_mime = $this->_buildHtmlMime();
        }

        // Sanity check the base MIME type
        if (!$this->_mbd->html && !empty($plain_mime)) {
            $mime = $plain_mime;
        } elseif (!$this->_mbd->plain && !empty($html_mime)) {
            $mime = $html_mime;
        } elseif (!empty($plain_mime) && !empty($html_mime)) {
            $mime->setType('multipart/alternative');
            $mime->addPart($plain_mime);
            $mime->addPart($html_mime);
        }
        $html_mime = null;
        $plain_mime = null;

        // If we have attachments, create a multipart/mixed wrapper.
        if ($this->_imapMessage->hasAttachments()) {
            $base = $this->_buildMultipartWrapper($mime);
            $this->_easMessage->airsyncbaseattachments = $this->_imapMessage
                ->getAttachments($this->_version);
        } else {
            $base = $mime;
        }
        $mime = null;

        // Populate the EAS body structure with the MIME data.
        $this->_airsyncBody->data = $base->toString(array(
            'headers' => $this->_getHeaders,
            'stream' => true)
        );
        $this->_airsyncBody->estimateddatasize = $base->getBytes();
    }

    /**
     * Handle any truncaction and set properties accordingly.
     */
    protected function _doTruncation()
    {
        // @todo Remove this sanity-check hack in 3.0. This is needed
        // since truncationsize incorrectly defaulted to a
        // MIME_TRUNCATION constant and could be cached in the sync-cache.
        $ts = !empty($this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize'])
            ? $this->_options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize']
            : false;
        $mime_truncation = (!empty($ts) && $ts > 9)
            ? $ts
            : (!empty($this->_options['truncation']) && $this->_options['truncation'] > 9
                ? $this->_options['truncation']
                : false);

        $this->_logger->info(sprintf(
            '[%s] Checking MIMETRUNCATION: %s, ServerData: %s',
            $this->_procid,
            $mime_truncation,
            $airsync_body->estimateddatasize));

        if (!empty($mime_truncation) &&
            $this->_airsyncBody->estimateddatasize > $mime_truncation) {
            ftruncate($this->_airsyncBody->data, $mime_truncation);
            $this->_airsyncBody->truncated = '1';
        } else {
            $this->_airsyncBody->truncated = '0';
        }
    }

    /**
     * Returns a Horde_Mime_Part representing a plain text body.
     *
     * @return Horde_Mime_Part
     */
    protected function _buildPlainMime()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContents($this->_mbd->plain['body']->stream, array('usestream' -> true));
        $part->setCharset('UTF-8');

        return $part;
    }

    /**
     * Returns a Horde_Mime_Part representing a HTML body.
     *
     * @return Horde_Mime_Part
     */
    protected function _buildHtmlMime()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/html');
        $part->setContents($this->_mbd->html['body']->stream, array('usestream' => true));
        $part->setCharset('UTF-8');

        return $part;
    }

    /**
     * Returns a multipart/mixed Horde_Mime_Part that wraps the body and all
     * attachments parts.
     *
     * @return Horde_Mime_Part
     */
    protected function _buildMultipartWrapper(Horde_Mime_Part $mime)
    {
        $part = new Horde_Mime_Part();
        $part->setType('multipart/mixed');
        $part->addPart($mime);
        $atc = $this->_imapMessage->getAttachmentsMimeParts();
        foreach ($atc as $atc_part) {
            $part->addPart($atc_part);
        }

        return $part;
    }

    /**
     * Returns the headers for the current IMAP message, with the Content-Type
     * and Content-Transfer-Encoding headers removed since we build those
     * ourselves.
     *
     * @return Horde_Mime_Headers
     */
    protected function _getHeaders()
    {
        $headers = $this->_imapMessage->getHeaders();
        $headers->removeHeader('Content-Type');
        $headers->removeHeader('Content-Transfer-Encoding');

        return $headers;
    }

    /**
     * Set airsyncBody properties with the raw IMAP message. Used when message
     * is encrypted and/or signed since we can't modify anything.
     */
    protected function _buildEncrypted()
    {
        $raw = new Horde_ActiveSync_Rfc822(
            $this->_imapMessage->getFullMsg(true),
            false
        );
        $this->_airsyncBody->estimateddatasize = $raw->getBytes();
        $this->_airsyncBody->data = $raw->getString();
        $this->_easMessage->airsyncbaseattachments = $this->_imapMessage->getAttachments($this->_version);
    }

    /**
     * Returns if we are able to (re)build our own MIME message or if we must
     * use the original raw message.
     *
     * @return boolean  True if able to modify false otherwise.
     */
    protected function _canModify()
    {
        return !$this->_imapMessage->isSigned() && !$this->_imapMessage->isEncrypted();
    }

}