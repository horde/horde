<?php
/**
 * A factory implementing the default policy.
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
 * A factory implementing the default policy.
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
class Horde_Kolab_Session_Factory_Default
extends Horde_Kolab_Session_Factory_Base
{
    /**
     * Configuration parameters for the session.
     *
     * @var array
     */
    private $_configuration;

    /**
     * The server factory used by this instance.
     *
     * @var array
     */
    private $_server_factory;

    /**
     * Constructor.
     *
     * @param array                      $config  Configuration parameters for
     *                                            the session.
     * @param Horde_Kolab_Server_Factory $factory The factory for the Kolab user
     *                                            db connection.
     */
    public function __construct(
        array $config,
        Horde_Kolab_Server_Factory $factory
    ) {
        $this->_configuration  = $config;
        $this->_server_factory = $factory;
    }

    /**
     * Return the kolab user db connection.
     *
     * @return Horde_Kolab_Server The server connection.
     */
    public function getServer()
    {
        return $this->_server_factory->getServer();
    }

    /**
     * Return the auth handler for sessions.
     *
     * @return Horde_Kolab_Session_Auth The authentication handler.
     */
    public function getSessionAuth()
    {
        $auth = new Horde_Kolab_Session_Auth_Horde();
        return $auth;
    }

    /**
     * Return the configuration parameters for the session.
     *
     * @return array The configuration values.
     */
    public function getSessionConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * Return the session storage driver.
     *
     * @return Horde_Kolab_Session_Storage The driver for storing sessions.
     */
    public function getSessionStorage()
    {
        $storage = new Horde_Kolab_Session_Storage_Sessionobjects(
            Horde_SessionObjects::singleton()
        );
        return $storage;
    }
}
