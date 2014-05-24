<?php
/**
 * Horde_ActiveSync_Request_SendMail::
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
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle SendMail requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
 */
class Horde_ActiveSync_Request_SendMail extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle the request
     *
     * @return boolean
     */
    protected function _handle()
    {
        // Check for wbxml vs RFC822
        if (!$this->_decoder->isWbxml()) {
            $this->_logger->info(sprintf(
                '[%s] Handling SENDMAIL command with no Wbxml.',
                $this->_procid));
            $stream = $this->_decoder->getFullInputStream();
            try {
                $result = $this->_driver->sendMail($stream, false, false, false, true);
                fclose($stream);
                return $result;
            } catch (Horde_ActiveSync_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_handError(
                    Horde_ActiveSync_Status::MAIL_SUBMISSION_FAILED,
                    Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SENDMAIL);

                return true;
            }
        } else {
            return $this->_handleWbxmlRequest();
        }
    }

    /**
     * Handle EAS 14+ SendMail/SmartReply/SmartForward requests.
     *
     * @return boolean
     */
    protected function _handleWbxmlRequest()
    {
        $this->_logger->info(sprintf(
            '[%s] Handling SENDMAIL command with Wbxml.',
            $this->_procid));
        // Get the first element and see what type of mail request we have.
        $e = $this->_decoder->getElement();
        if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
            $this->_handleError(
                Horde_ActiveSync_Status::INVALID_WBXML,
                Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SENDMAIL);
            return true;
        }

        $sendmail = $smartreply = $smartforward = false;
        switch ($e[Horde_ActiveSync_Wbxml::EN_TAG]) {
        case Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SENDMAIL:
            $sendmail = true;
            break;
        case Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SMARTREPLY:
            $smartreply = true;
            break;
        case Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SMARTFORWARD:
            $smartforward = true;
        }

        if (!$sendmail && !$smartreply && !$smartforward) {
           return $this->_handleError(
            Horde_ActiveSync_Status::INVALID_CONTENT,
            Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SENDMAIL);
        }

        $mail = Horde_ActiveSync::messageFactory('SendMail');
        $mail->decodeStream($this->_decoder);
        if ($smartreply || $smartforward) {
            $mail->source->folderid = $this->_activeSync->getCollectionsObject()->getBackendIdForFolderUid($mail->source->folderid);
        }

        try {
            // @TODO fix this ugly method call in H6 when we can break BC.
            return $this->_driver->sendMail(null, $smartforward, $smartreply, null, null, $mail);
        } catch (Horde_Exception_NotFound $ex) {
            $this->_logger->err($ex->getMessage());
            $this->_handleError(
                Horde_ActiveSync_Status::ITEM_NOT_FOUND,
                $e[Horde_ActiveSync_Wbxml::EN_TAG]);
        } catch (Horde_ActiveSync_Exception $ex) {
            $this->_logger->err($ex->getMessage());
            $this->_handleError(
                Horde_ActiveSync_Status::MAIL_SUBMISSION_FAILED,
                $e[Horde_ActiveSync_Wbxml::EN_TAG]);
        }

        return true;
    }

    /**
     * Helper to output a global error response.
     *
     * @param integer $status  A Horde_ActiveSync_Status:: constant.
     * @param string $type     The type of response tag.
     */
    protected function _handleError($status, $type)
    {
        $this->_encoder->startWBXML();
        $this->_encoder->startTag($type);
        $this->_encoder->startTag(Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();
        $this->_enocder->endTag();
    }

}