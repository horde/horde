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
     * Sent the error to the notification system.
     *
     * @var boolean
     */
    public $_notified = false;

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
     * @param boolean $default  Return exception, even if no code exists?
     *
     * @return Horde_Auth_Exception  An authentication exception.
     */
    public function authException($default = true)
    {
        $e = $this;

        switch ($this->getCode()) {
        case self::LOGIN_AUTHENTICATIONFAILED:
        case self::LOGIN_AUTHORIZATIONFAILED:
            $code = Horde_Auth::REASON_BADLOGIN;
            break;

        case self::LOGIN_EXPIRED:
            $code = Horde_Auth::REASON_EXPIRED;
            break;

        case self::SERVER_CONNECT:
        case self::LOGIN_UNAVAILABLE:
            $code = Horde_Auth::REASON_MESSAGE;
            break;

        case self::LOGIN_NOAUTHMETHOD:
        case self::LOGIN_PRIVACYREQUIRED:
        case self::LOGIN_TLSFAILURE:
            $code = Horde_Auth::REASON_FAILED;
            break;

        default:
            $code = $default
                ? Horde_Auth::REASON_FAILED
                : null;
            break;
        }

        return is_null($code)
            ? null
            : new Horde_Auth_Exception($e, $code);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'notified':
            return $this->_notified;
        }
    }

}
