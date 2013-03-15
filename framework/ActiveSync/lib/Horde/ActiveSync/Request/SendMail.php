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
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
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
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Request_SendMail extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            '[%s] Handling SENDMAIL command.',
            $this->_device->id));

        // Check for wbxml vs RFC822
        if (!$this->_decoder->isWbxml()) {
            $stream = $this->_decoder->getFullInputStream();
            try {
                $result = $this->_driver->sendMail($stream, false, false, false, true);
                fclose($stream);
                return $result;
            } catch (Horde_ActiveSync_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception_InvalidRequest($e->getMessage());
            }
        } else {
            return $this->_handleWbxmlRequest();
        }
    }

    /**
     * Handle EAS 14+ SendMail/SmartReply/SmartForward requests.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_InvalidRequest
     */
    protected function _handleWbxmlRequest()
    {
        // Get the first element and see what type of mail request we have.
        $e = $this->_decoder->getElement();
        if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
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
            throw new Horde_ActiveSync_Exception('Protocol Error. Did not receive sendmail/smartreply/smartforward in wbxml request.');
        }

        $mail = new Horde_ActiveSync_Message_SendMail(array('logger' => $this->_logger, 'protocolversion' => $this->_version));
        $mail->decodeStream($this->_decoder);
        try {
            // @TODO fix this ugly method call in H6 when we can break BC.
            $result = $this->_driver->sendMail(null, $smartforward, $smartreply, null, null, $mail);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception_InvalidRequest($e->getMessage());
        }
    }

}