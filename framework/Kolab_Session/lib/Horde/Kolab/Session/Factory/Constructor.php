<?php
/**
 * A factory that receives all required details via the factory constructor.
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
 * A factory that receives all required details via the factory constructor.
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
class Horde_Kolab_Session_Factory_Constructor
extends Horde_Kolab_Session_Factory_Base
{
    /**
     * The connection to the Kolab user db.
     *
     * @var Horde_Kolab_Server_Composite_Interface
     */
    private $_server;

    /**
     * The auth handler for the session.
     *
     * @var Horde_Kolab_Session_Auth_Interface
     */
    private $_auth;

    /**
     * Configuration parameters for the session.
     *
     * @var array
     */
    private $_configuration;

    /**
     * The storage handler for the session.
     *
     * @var Horde_Kolab_Session_Storage_Interface
     */
    private $_storage;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server_Composite_Interface $server  The connection to the
     *                                                        Kolab user db.
     * @param Horde_Kolab_Session_Auth_Interface     $auth    The auth handler for
     *                                                        the session.
     * @param array                                  $config  Configuration
     *                                                        parameters for the
     *                                                        session.
     * @param Horde_Kolab_Session_Storage_Interface  $storage The storage handler
     *                                                        for the session.
     */
    public function __construct(
        Horde_Kolab_Server_Composite_Interface $server,
        Horde_Kolab_Session_Auth_Interface $auth,
        array $config,
        Horde_Kolab_Session_Storage_Interface $storage
    ) {
        $this->_server        = $server;
        $this->_auth          = $auth;
        $this->_configuration = $config;
        $this->_storage       = $storage;
    }

    /**
     * Return the kolab user db connection.
     *
     * @return Horde_Kolab_Server The server connection.
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * Return the auth handler for sessions.
     *
     * @return Horde_Kolab_Session_Auth_Interface The authentication handler.
     */
    public function getSessionAuth()
    {
        return $this->_auth;
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
     * @return Horde_Kolab_Session_Storage_Interface The driver for storing sessions.
     */
    public function getSessionStorage()
    {
        return $this->_storage;
    }
}
