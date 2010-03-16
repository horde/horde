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
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
implements Horde_Kolab_Session_Factory_Interface
{
    /**
     * Return the session validation driver.
     *
     * @param Horde_Kolab_Session_Interface      $session The session to validate.
     * @param Horde_Kolab_Session_Auth_Interface $auth    The auth handler.
     *
     * @return Horde_Kolab_Session_Valid_Interface The driver for validating
     *                                             sessions.
     */
    public function getSessionValidator(
        Horde_Kolab_Session_Interface $session,
        Horde_Kolab_Session_Auth_Interface $auth
    ) {
        $validator = new Horde_Kolab_Session_Valid_Base(
            $session, $auth
        );
        return $validator;
    }

    /**
     * Validate the given session.
     *
     * @param Horde_Kolab_Session_Interface $session The session to validate.
     *
     * @return boolean True if the given session is valid.
     */
    public function validate(
        Horde_Kolab_Session_Interface $session
    ) {
        return $this->getSessionValidator(
            $session,
            $this->getSessionAuth()
        )->isValid();
    }

    /**
     * Returns a new session handler.
     *
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function createSession()
    {
        $session = new Horde_Kolab_Session_Base(
            $this->getServer(),
            $this->getSessionConfiguration()
        );
        /** If we created a new session handler it needs to be stored once */
        $session = new Horde_Kolab_Session_Decorator_Stored(
            $session,
            $this->getSessionStorage()
        );
        return $session;
    }

    /**
     * Returns either a reference to a session handler with data retrieved from
     * the session or a new session handler.
     *
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function getSession()
    {
        $storage = $this->getSessionStorage();
        $session = $storage->load();

        if (!empty($session) && $this->validate($session)) {
            return $session;
        }
        $session = $this->createSession();
        return $session;
    }
}
