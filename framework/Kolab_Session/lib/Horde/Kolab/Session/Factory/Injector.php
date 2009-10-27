<?php
/**
 * A factory using a Horde_Injector instance.
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
 * A factory using a Horde_Injector instance.
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
class Horde_Kolab_Session_Factory_Injector
extends Horde_Kolab_Session_Factory_Base
{
    /**
     * Configuration parameters for the session.
     *
     * @var array
     */
    private $_configuration;

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param array          $config   Configuration parameters for the session.
     * @param Horde_Injector $injector The injector to use.
     */
    public function __construct(
        array $config,
        Horde_Injector $injector
    ) {
        $this->_configuration = $config;
        $this->_injector      = $injector;
        $this->_setup();
    }

    /**
     * Setup the machinery to create Horde_Kolab_Session objects.
     *
     * @return NULL
     */
    private function _setup()
    {
        $this->_setupAuth();
        $this->_setupStorage();
        $this->_setupConfiguration();
        $this->_setupSession();
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Session_Auth handler.
     *
     * @return NULL
     */
    private function _setupAuth()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_Session_Auth',
            'Horde_Kolab_Session_Auth_Horde'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Session_Storage handlers.
     *
     * @return NULL
     */
    private function _setupStorage()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_Session_Storage',
            'Horde_Kolab_Session_Storage_Sessionobjects'
        );
    }

    /**
     * Provide configuration settings for Horde_Kolab_Session.
     *
     * @return NULL
     */
    private function _setupConfiguration()
    {
        $this->_injector->setInstance(
            'Horde_Kolab_Session_Configuration', $this->_configuration
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Session handler.
     *
     * @return NULL
     */
    private function _setupSession()
    {
        $this->_injector->bindFactory(
            'Horde_Kolab_Session',
            'Horde_Kolab_Session_Factory_Injector',
            'getSession'
        );
    }

    /**
     * Return the kolab user db connection.
     *
     * @return Horde_Kolab_Server The server connection.
     */
    public function getServer()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server');
    }

    /**
     * Return the auth handler for sessions.
     *
     * @return Horde_Kolab_Session_Auth The authentication handler.
     */
    public function getSessionAuth()
    {
        return $this->_injector->getInstance('Horde_Kolab_Session_Auth');
    }

    /**
     * Return the configuration parameters for the session.
     *
     * @return array The configuration values.
     */
    public function getSessionConfiguration()
    {
        return $this->_injector->getInstance('Horde_Kolab_Session_Configuration');
    }

    /**
     * Return the session storage driver.
     *
     * @return Horde_Kolab_Session_Storage The driver for storing sessions.
     */
    public function getSessionStorage()
    {
        return $this->_injector->getInstance('Horde_Kolab_Session_Storage');
    }
}
