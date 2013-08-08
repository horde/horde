<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */

/**
 * Exception handler for the Horde_Smtp package.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */
class Horde_Smtp_Exception extends Horde_Exception_Wrapped
{
    /* Error message codes. */

    // Unspecified error (default)
    const UNSPECIFIED = 0;

    // Thrown if server denies the network connection.
    const SERVER_CONNECT = 1;

    // Thrown if read error for server response.
    const SERVER_READERROR = 2;

    // Thrown if write error in server interaction.
    const SERVER_WRITEERROR = 3;

    // The server ended the connection.
    const DISCONNECT = 4;

    // Mailbox unavailable.
    const MAILBOX_UNAVAILABLE = 5;

    // Insufficient system storage.
    const INSUFFICIENT_STORAGE = 6;

    // Unknown local user.
    const UNKNOWN_LOCAL_USER = 7;

    // User has exceeded storage allocation.
    const OVERQUOTA = 8;


    // Login failures

    // Could not start mandatory TLS connection.
    const LOGIN_TLSFAILURE = 100;

    // Generic authentication failure.
    const LOGIN_AUTHENTICATIONFAILED = 101;

    // Requires authentication.
    const LOGIN_REQUIREAUTHENTICATION = 102;


    /**
     * SMTP Enhanced Mail System Status Code (see RFC 3463).
     *
     * @var string
     */
    protected $_enhancedcode = null;

    /**
     * SMTP reply code.
     *
     * @var integer
     */
    protected $_smtpcode = 0;

    /**
     * Set the SMTP reply code.
     *
     * @param integer $code  SMTP reply code.
     */
    public function setSmtpCode($code)
    {
        $this->_smtpcode = $code;

        // Any code not listed here will get the details of the error message
        // as returned from the server.

        switch ($code) {
        case 450:
        case 550:
            $this->message = Horde_Smtp_Translation::t("Mailbox unavailable.");
            $this->code = self::MAILBOX_UNAVAILABLE;
            break;

        case 452:
            $this->message = Horde_Smtp_Translation::t("Insufficient system storage.");
            $this->code = self::INSUFFICIENT_STORAGE;
            break;

        case 454:
            $this->message = Horde_Smtp_Translation::t("Could not open secure TLS connection to the server.");
            $this->code = self::LOGIN_TLSFAILURE;
            break;

        case 530:
            $this->message = Horde_Smtp_Translation::t("Server requires authentication.");
            $this->code = self::LOGIN_REQUIREAUTHENTICATION;
            break;

        case 551:
            $this->code = self::UNKNOWN_LOCAL_USER;
            break;

        case 552:
            $this->code = self::OVERQUOTA;
            break;

        case 554:
            $this->message = Horde_Smtp_Translation::t("Server is not accepting SMTP connections.");
            $this->code = self::DISCONNECT;
            break;
        }
    }

    /**
     * Get the SMTP reply code.
     *
     * @return integer  Reply code.
     */
    public function getSmtpCode()
    {
        return $this->_smtpcode;
    }

    /**
     * Set SMTP Enhanced Mail System Status Code.
     *
     * @param string $code  Status code.
     */
    public function setEnhancedSmtpCode($code)
    {
        $this->_enhancedcode = $code;
    }

    /**
     * Get SMTP Enhanced Mail System Status Code.
     *
     * @return string  Status code.
     */
    public function getEnhancedSmtpCode()
    {
        return $this->_enhancedcode;
    }

}
