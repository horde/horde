<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Exception class for handling Horde_Imap_Client exceptions in IMP.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $notified  Sent notification of the error?
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
     * @return Horde_Auth_Exception  An authentication exception.
     */
    public function authException()
    {
        $code = $this->authError();

        return new Horde_Auth_Exception(
            $this,
            is_null($code) ? Horde_Auth::REASON_FAILED : $code
        );
    }

    /**
     * Returns the authentication error, if any.
     *
     * @return integer  Authentication error, or null if exception not caused
     *                  by auth error.
     */
    public function authError()
    {
        switch ($this->getCode()) {
        case self::LOGIN_AUTHENTICATIONFAILED:
        case self::LOGIN_AUTHORIZATIONFAILED:
            return Horde_Auth::REASON_BADLOGIN;

        case self::LOGIN_EXPIRED:
            return Horde_Auth::REASON_EXPIRED;

        case self::SERVER_CONNECT:
        case self::LOGIN_UNAVAILABLE:
            return Horde_Auth::REASON_MESSAGE;

        case self::LOGIN_NOAUTHMETHOD:
        case self::LOGIN_PRIVACYREQUIRED:
        case self::LOGIN_TLSFAILURE:
            return Horde_Auth::REASON_FAILED;
        }

        return null;
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
