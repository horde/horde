<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */

/**
 * Exception handler for the Horde_Smtp package.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */
class Horde_Smtp_Exception extends Horde_Exception
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

    // Server does not support necessary extension(s).
    // @since 1.5.0
    const LOGIN_MISSINGEXTENSION = 103;


    /**
     * Raw error message (in English).
     *
     * @since 1.4.0
     *
     * @var string
     */
    public $raw_msg = '';

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
     * Constructor.
     *
     * @param string $msg  Error message (non-translated).
     * @param code $code   Error code.
     */
    public function __construct($message = null, $code = null)
    {
        parent::__construct(
            Horde_Smtp_Translation::t($message),
            $code
        );

        $this->raw_msg = $message;
    }

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
            $this->raw_msg = Horde_Smtp_Translation::r("Mailbox unavailable.");
            $this->message = Horde_Smtp_Translation::t($this->raw_msg);
            $this->code = self::MAILBOX_UNAVAILABLE;
            break;

        case 452:
            $this->raw_msg = Horde_Smtp_Translation::r("Insufficient system storage.");
            $this->message = Horde_Smtp_Translation::t($this->raw_msg);
            $this->code = self::INSUFFICIENT_STORAGE;
            break;

        case 454:
            $this->raw_msg = Horde_Smtp_Translation::r("Could not open secure TLS connection to the server.");
            $this->message = Horde_Smtp_Translation::t($this->raw_msg);
            $this->code = self::LOGIN_TLSFAILURE;
            break;

        case 530:
            $this->raw_msg = Horde_Smtp_Translation::r("Server requires authentication.");
            $this->message = Horde_Smtp_Translation::t($this->raw_msg);
            $this->code = self::LOGIN_REQUIREAUTHENTICATION;
            break;

        case 550:
            $this->raw_msg = Horde_Smtp_Translation::r("Message could not be delivered - the address was not found, is unknown, or is not receiving messages.");
            $this->message = Horde_Smtp_Translation::t($this->raw_msg);
            $this->code = self::MAILBOX_UNAVAILABLE;
            break;

        case 551:
            $this->code = self::UNKNOWN_LOCAL_USER;
            break;

        case 552:
            $this->code = self::OVERQUOTA;
            break;

        case 554:
            $this->raw_msg = Horde_Smtp_Translation::r("Server is not accepting SMTP connections.");
            $this->message = Horde_Smtp_Translation::t($this->raw_msg);
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
