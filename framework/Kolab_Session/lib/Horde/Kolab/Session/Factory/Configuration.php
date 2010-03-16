<?php
/**
 * A factory that receives all required details via configuration parameters.
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
 * A factory that receives all required details via configuration parameters.
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
class Horde_Kolab_Session_Factory_Configuration
implements Horde_Kolab_Session_Factory_Interface
{
    /**
     * Configuration parameters for the session.
     *
     * @var array
     */
    private $_configuration;

    /**
     * The factory used for creating the instances.
     *
     * @var Horde_Kolab_Session_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param array $config Configuration parameters for the session.
     */
    public function __construct(array $config)
    {
        $this->_configuration = $config;

        if (isset($config['server'])) {
            $server_configuration = $config['server'];
        } elseif (isset($config['ldap'])) {
            $server_configuration = $config['ldap'];
        } else {
            throw new Horde_Kolab_Session_Exception(
                'The Kolab server configuration is missing!'
            );
        }

        $server_factory = new Horde_Kolab_Server_Factory_Configuration(
            $server_configuration
        );

        $factory = new Horde_Kolab_Session_Factory_Default(
            $config, $server_factory
        );

        if (isset($config['logger'])) {
            $factory = new Horde_Kolab_Session_Factory_Decorator_Logged(
                $factory, $config['logger']
            );
        }

        if (isset($config['session']['anonymous']['user'])
            && isset($config['session']['anonymous']['pass'])
        ) {
            $factory = new Horde_Kolab_Session_Factory_Decorator_Anonymous(
                $factory,
                $config['session']['anonymous']['user'],
                $config['session']['anonymous']['pass']
            );
        }

        $this->_factory = $factory;
    }

    /**
     * Return the kolab user db connection.
     *
     * @return Horde_Kolab_Server The server connection.
     */
    public function getServer()
    {
        return $this->_factory->getServer();
    }

    /**
     * Return the auth handler for sessions.
     *
     * @return Horde_Kolab_Session_Auth The authentication handler.
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
     * @return Horde_Kolab_Session_Storage The driver for storing sessions.
     */
    public function getSessionStorage()
    {
        return $this->_factory->getSessionStorage();
    }

    /**
     * Return the session validation driver.
     *
     * @param Horde_Kolab_Session      $session The session to validate.
     * @param Horde_Kolab_Session_Auth $auth    The auth handler.
     *
     * @return Horde_Kolab_Session_Valid The driver for validating sessions.
     */
    public function getSessionValidator(
        Horde_Kolab_Session_Interface $session,
        Horde_Kolab_Session_Auth_Interface $auth
    ) {
        return $this->_factory->getSessionValidator($session, $auth);
    }

    /**
     * Validate the given session.
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
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function createSession()
    {
        return $this->_factory->createSession();
    }

    /**
     * Returns either a reference to a session handler with data retrieved from
     * the session or a new session handler.
     *
     * @return Horde_Kolab_Session The concrete Kolab session reference.
     */
    public function getSession()
    {
        return $this->_factory->getSession();
    }
}
