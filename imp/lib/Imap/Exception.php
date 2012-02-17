<?php
/**
 * Exception class for handling Horde_Imap_Client exceptions in IMP.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 *
 * @property boolean $logged  Logged the error?
 * @property boolean $notified  Sent notification of the error?
 */
class IMP_Imap_Exception extends Horde_Imap_Client_Exception
{
    /**
     * Logged the error.
     *
     * @var boolean
     */
    protected $_logged = false;

    /**
     * Sent the error to the notification system.
     *
     * @var boolean
     */
    public $_notified = false;

    /**
     * Log the error.
     *
     * @param string $msg     Log message.
     * @param integer $level  Log level.
     * @param boolean $force  Force logging, even if already done?
     */
    public function log($msg = null, $level = 'ERR', $force = false)
    {
        if (!$this->_logged || $force) {
            Horde::logMessage(
                is_null($msg) ? $this : $msg,
                $level
            );

            $this->_logged = true;
        }
    }

    /**
     * Send notification of the error.
     *
     * @param string $msg     Notification message.
     * @param string $level   Notification level.
     * @param boolean $force  Force notification, even if already done?
     */
    public function notify($msg = null, $level = null, $force = false)
    {
        if (!$this->_notified || $force) {
            $GLOBALS['notification']->push(
                is_null($msg) ? $this->getMessage() : $msg,
                is_null($level) ? 'horde.error' : $level
            );

            $this->_notified = true;
        }
    }

    /**
     * Generates an authentication exception.
     *
     * @return Horde_Auth_Exception  An authentication exception.
     */
    public function authException()
    {
        $e = $this;

        switch ($this->getCode()) {
        case Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED:
        case Horde_Imap_Client_Exception::LOGIN_AUTHORIZATIONFAILED:
            $code = Horde_Auth::REASON_BADLOGIN;
            break;

        case Horde_Imap_Client_Exception::LOGIN_EXPIRED:
            $code = Horde_Auth::REASON_EXPIRED;
            break;

        case Horde_Imap_Client_Exception::SERVER_CONNECT:
        case Horde_Imap_Client_Exception::LOGIN_UNAVAILABLE:
            $code = Horde_Auth::REASON_MESSAGE;
            $e = _("Remote server is down. Please try again later.");
            break;

        case Horde_Imap_Client_Exception::LOGIN_NOAUTHMETHOD:
        case Horde_Imap_Client_Exception::LOGIN_PRIVACYREQUIRED:
        case Horde_Imap_Client_Exception::LOGIN_TLSFAILURE:
        default:
            $code = Horde_Auth::REASON_FAILED;
            break;
        }

        return new Horde_Auth_Exception($e, $code);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'logged':
            return $this->_logged;

        case 'notified':
            return $this->_notified;
        }
    }

}
