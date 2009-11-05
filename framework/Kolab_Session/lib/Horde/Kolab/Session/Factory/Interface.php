<?php
/**
 * Interface for Horde_Kolab_Session factories.
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
 * Interface for Horde_Kolab_Session factories.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
interface Horde_Kolab_Session_Factory_Interface
{
    /**
     * Return the kolab user db connection.
     *
     * @return Horde_Kolab_Server_Interface The server connection.
     */
    public function getServer();

    /**
     * Return the auth handler for sessions.
     *
     * @return Horde_Kolab_Session_Auth_Interface The authentication handler.
     */
    public function getSessionAuth();

    /**
     * Return the configuration parameters for the session.
     *
     * @return array The configuration values.
     */
    public function getSessionConfiguration();

    /**
     * Return the session storage driver.
     *
     * @return Horde_Kolab_Session_Storage_Interface The driver for storing sessions.
     */
    public function getSessionStorage();

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
    );

    /**
     * Validate the given session.
     *
     * Validate the given session.
     *
     * @param Horde_Kolab_Session_Interface $session The session to validate.
     * @param string                        $user    The session will be validated
     *                                               for this user ID.
     *
     * @return boolean True if the given session is valid.
     */
    public function validate(
        Horde_Kolab_Session_Interface $session,
        $user = null
    );

    /**
     * Returns a new session handler.
     *
     * @param string $user The session will be setup for the user with this ID.
     *
     * @return Horde_Kolab_Session_Interface The concrete Kolab session reference.
     */
    public function createSession($user = null);

    /**
     * Returns either a reference to a session handler with data retrieved from
     * the session or a new session handler.
     *
     * @param string $user        The session will be setup for the user with
     *                            this ID.
     * @param array  $credentials An array of login credentials.
     *
     * @return Horde_Kolab_Session_Interface The concrete Kolab session reference.
     */
    public function getSession($user = null, array $credentials = null);
}
