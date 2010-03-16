<?php
/**
 * A factory decorator that adds logging to the generated instances.
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
 * A factory decorator that adds logging to the generated instances.
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
class Horde_Kolab_Session_Factory_Decorator_Logged
implements Horde_Kolab_Session_Factory_Interface
{
    /**
     * The factory setup resulting from the configuration.
     *
     * @var Horde_Kolab_Session_Factory_Interface
     */
    private $_factory;

    /**
     * The logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Session_Factory_Interface $factory The base factory.
     * @param mixed                                 $logger  The logger isntance.
     */
    public function __construct(
        Horde_Kolab_Session_Factory_Interface $factory,
        $logger
    ) {
        $this->_factory = $factory;
        $this->_logger  = $logger;
    }

    /**
     * Return the kolab user db connection.
     *
     * @return Horde_Kolab_Server_Interface The server connection.
     */
    public function getServer()
    {
        return $this->_factory->getServer();
    }

    /**
     * Return the auth handler for sessions.
     *
     * @return Horde_Kolab_Session_Auth_Interface The authentication handler.
     */
    public function getSessionAuth()
    {
        return $this->_factory->getSessionAuth();
    }

    /**
     * Return the configuration parameters for the session.
     *
     * @return array The configuration values.
     */
    public function getSessionConfiguration()
    {
        return $this->_factory->getSessionConfiguration();
    }

    /**
     * Return the session storage driver.
     *
     * @return Horde_Kolab_Session_Storage_Interface The driver for storing sessions.
     */
    public function getSessionStorage()
    {
        return $this->_factory->getSessionStorage();
    }

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
        $valid = $this->_factory->getSessionValidator($session, $auth);
        $valid = new Horde_Kolab_Session_Valid_Decorator_Logged(
            $valid, $this->_logger
        );
        return $valid;
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
        return $this->_factory->validate($session);
    }

    /**
     * Returns a new session handler.
     *
     * @return Horde_Kolab_Session_Interface The concrete Kolab session reference.
     */
    public function createSession()
    {
        $session = $this->_factory->createSession();
        $session = new Horde_Kolab_Session_Decorator_Logged(
            $session, $this->_logger
        );
        return $session;
    }

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
    public function getSession()
    {
        return $this->_factory->getSession();
    }
}
