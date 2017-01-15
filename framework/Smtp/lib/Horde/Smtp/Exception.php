<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */

/**
 * Exception handler for the Horde_Smtp package.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 *
 * @property-read boolean $permanent  Is this a permanent (non-transient)
 *                                    error? (@since 1.7.0)
 * @property-read string $raw_msg  Raw error message from server (in English).
 *                                 (@since 1.4.0)
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

    // "Syntax errors, syntactically correct commands that do not fit any
    // functional category, and unimplemented or superfluous commands."
    // (@since 1.7.0)
    const CATEGORY_SYNTAX = 201;

    // "Replies to requests for information" (@since 1.7.0)
    const CATEGORY_INFORMATIONAL = 202;

    // "Replies referring to the transmission channel" (@since 1.7.0)
    const CATEGORY_CONNECTIONS = 203;

    // "Status of the receiver mail system vis-a-vis the requested transfer or
    // other mail system action."
    // "Mail system status indicates that something having to do with the
    // destination system has caused this DSN. System issues are assumed to be
    // under the general control of the destination system administrator."
    // (@since 1.7.0)
    const CATEGORY_MAILSYSTEM = 204;

    // "Reports on the originator or destination address. It may include
    // address syntax or validity." (@since 1.7.0)
    const CATEGORY_ADDRESS = 205;

    // "Mailbox status indicates that something having to do with the mailbox
    // has caused this DSN." (@since 1.7.0)
    const CATEGORY_MAILBOX = 206;

    // "Status about the delivery system itself. These system components
    // include any necessary infrastructure such as directory and routing
    // services." (@since 1.7.0)
    const CATEGORY_NETWORK = 207;

    // "The mail delivery protocol status codes report failures involving the
    // message delivery protocol. These failures include the full range of
    // problems resulting from implementation errors or an unreliable
    // connection." (@since 1.7.0)
    const CATEGORY_DELIVERY = 208;

    // "Failures involving the content of the message. These codes report
    // failures due to translation, transcoding, or otherwise unsupported
    // message media." (@since 1.7.0)
    const CATEGORY_CONTENT = 209;

    // "Failures involving policies such as per-recipient or per-host
    // filtering and cryptographic operations." (@since 1.7.0)
    const CATEGORY_SECURITY = 210;


    /* Login failures codes. */

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
     * SMTP Enhanced Mail System Status Code (see RFC 3463).
     *
     * @var string
     */
    protected $_enhancedcode = null;

    /**
     * Raw error message (in English).
     *
     * @var string
     */
    protected $_rawmsg = '';

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

        $this->_rawmsg = $message;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'permanent':
            $str_code = is_null($this->_enhancedcode)
                ? strval($this->_smtpcode)
                : explode('.', $this->_enhancedcode);
            /* Enhanced codes: Permanent errors are 5.y.z codes. (4.y.z are
             * tranisent errors)
             * Status code: permanent errors are 5yz codes. (4yz are tranisent
             * errors) */
            return ($str_code[0] === '5');

        case 'raw_msg':
            return $this->_rawmsg;
        }
    }

    /**
     * Set the SMTP reply code.
     *
     * @param integer $smtpcode  SMTP reply code.
     */
    public function setSmtpCode($smtpcode)
    {
        $this->_enhancedcode = null;
        $this->code = 0;
        $this->_smtpcode = $smtpcode;

        /* Any code not listed here will get the details of the error message
         * as returned from the server.
         * Need to store $code/$message here because getCode()/getMessage() is
         * declared final in the parent class and we can not alter on-demand
         * at that location (darn). */
        switch ($smtpcode) {
        case 450:
            $this->code = self::MAILBOX_UNAVAILABLE;
            $this->message = Horde_Smtp_Translation::t("Mailbox unavailable.");

            return;

        case 452:
            $this->code = self::INSUFFICIENT_STORAGE;
            $this->message = Horde_Smtp_Translation::t("Insufficient system storage.");
            return;

        case 454:
            $this->code = self::LOGIN_TLSFAILURE;
            $this->message = Horde_Smtp_Translation::t("Could not open secure TLS connection to the server.");
            return;

        case 530:
            $this->code = self::LOGIN_REQUIREAUTHENTICATION;
            $this->message = Horde_Smtp_Translation::t("Server requires authentication.");
            return;

        case 550:
            $this->code = self::MAILBOX_UNAVAILABLE;
            $this->message = Horde_Smtp_Translation::t("Message could not be delivered - the address was not found, is unknown, or is not receiving messages.");
            return;

        case 551:
            $this->code = self::UNKNOWN_LOCAL_USER;
            return;

        case 552:
            $this->code = self::OVERQUOTA;
            return;

        case 554:
            $this->code = self::DISCONNECT;
            $this->message = Horde_Smtp_Translation::t("Server is not accepting SMTP connections.");
            return;
        }

        $str_code = strval($smtpcode);
        switch ($str_code[1]) {
        case '0':
            $this->code = self::CATEGORY_SYNTAX;
            break;

        case '1':
            $this->code = self::CATEGORY_INFORMATIONAL;
            break;

        case '2':
            $this->code = self::CATEGORY_CONNECTIONS;
            break;

        case '5':
            $this->code = self::CATEGORY_MAILSYSTEM;
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
     * Set SMTP Enhanced Mail System Status Code (RFC 3463).
     *
     * @param string $code  Enhanced status code.
     */
    public function setEnhancedSmtpCode($code)
    {
        $this->_enhancedcode = $code;

        /* Only set code if more specific than general category codes. */
        if ($this->code && ($this->code < 100)) {
            return;
        }

        $parts = explode('.', $code);
        switch ($parts[1]) {
        case '1':
            $this->code = self::CATEGORY_ADDRESS;
            break;

        case '2':
            $this->code = self::CATEGORY_MAILBOX;
            break;

        case '3':
            $this->code = self::CATEGORY_MAILSYSTEM;
            break;

        case '4':
            $this->code = self::CATEGORY_NETWORK;
            break;

        case '5':
            $this->code = self::CATEGORY_DELIVERY;
            break;

        case '6':
            $this->code = self::CATEGORY_CONTENT;
            break;

        case '7':
            $this->code = self::CATEGORY_SECURITY;
            break;
        }
    }

    /**
     * Get SMTP Enhanced Mail System Status Code (RFC 3463).
     *
     * @return string  Enhanced status code.
     */
    public function getEnhancedSmtpCode()
    {
        return $this->_enhancedcode;
    }

}
