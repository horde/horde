<?php
/**
 * A class to check if the given session is valid.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * A class to check if the given session is valid.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Valid_Base
implements Horde_Kolab_Session_Valid_Interface
{
    /**
     * The session handler this instance provides with anonymous access.
     *
     * @var Horde_Kolab_Session
     */
    private $_session;

    /**
     * Provides authentication information for this object.
     *
     * @var mixed The user ID or false if no user is logged in.
     */
    private $_auth;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Session $session The session that should be validated.
     * @param mixed               $auth    The user ID or false if no user is
     *                                     logged in.
     */
    public function __construct(
        Horde_Kolab_Session $session,
        $auth
    ) {
        $this->_session = $session;
        $this->_auth    = $auth;
    }

    /**
     * Does the current session still match the authentication information?
     *
     * @param string $user The user the session information is being requested
     *                     for. This is usually empty, indicating the current
     *                     user.
     *
     * @return boolean True if the session is still valid.
     */
    public function isValid($user = null)
    {
        $mail = $this->_session->getMail();
        if ($this->_auth != $mail) {
            return false;
        }
        if (empty($user)) {
            return true;
        }
        if ($user != $mail && $user != $this->_session->getUid()) {
            return false;
        }
        return true;
    }

    /**
     * Return the session this validator checks.
     *
     * @return Horde_Kolab_Session The session checked by this
     * validator.
     */
    public function getSession()
    {
        return $this->_session;
    }

    /**
     * Return the auth driver of this validator.
     *
     * @return mixed The user ID or false if no user is logged in.
     */
    public function getAuth()
    {
        return $this->_auth;
    }
}