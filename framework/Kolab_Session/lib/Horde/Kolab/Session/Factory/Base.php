<?php
/**
 * Revives an old Horde_Kolab_Session handler or generates a new one if
 * required.
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
 * Revives an old Horde_Kolab_Session handler or generates a new one if
 * required.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
abstract class Horde_Kolab_Session_Factory_Base
implements Horde_Kolab_Session_Factory
{
    /**
     * Return the session validation driver.
     *
     * @param Horde_Kolab_Session      $session The session to validate.
     * @param Horde_Kolab_Session_Auth $auth    The auth handler.
     *
     * @return Horde_Kolab_Session_Valid The driver for validating sessions.
     */
    public function getSessionValidator(
        Horde_Kolab_Session $session,
        Horde_Kolab_Session_Auth $auth
    ) {
        $validator = new Horde_Kolab_Session_Valid_Base(
            $session, $auth
        );
        return $validator;
    }

    /**
     * Validate the given session.
     *
     * @param Horde_Kolab_Session $session The session to validate.
     * @param string              $user    The session will be validated for
     *                                     this user ID.
     *
     * @return boolean True if the given session is valid.
     */
    public function validate(Horde_Kolab_Session $session, $user = null)
    {
        return $this->getSessionValidator(
            $session,
            $this->getSessionAuth()
        )->isValid($user);
    }

    /**
     * Returns a new session handler.
     *
     * @param string $user The session will be setup for the user with this ID.
     *
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function createSession($user = null)
    {
        $session = new Horde_Kolab_Session_Base(
            $user, 
            $this->getServer(),
            $this->getSessionConfiguration()
        );
        /** If we created a new session handler it needs to be stored once */
        $session = new Horde_Kolab_Session_Stored(
            $session,
            $this->getSessionStorage()
        );
        return $session;
    }

    /**
     * Returns either a reference to a session handler with data retrieved from
     * the session or a new session handler.
     *
     * @param string $user The session will be setup for the user with this ID.
     *
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function getSession($user = null)
    {
        $storage = $this->getSessionStorage();
        $session = $storage->load();

        if (!empty($session) && $this->validate($session, $user)) {
            return $session;
        }
        return $this->createSession($user);
    }
}
