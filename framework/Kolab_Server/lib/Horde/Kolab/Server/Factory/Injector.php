<?php
/**
 * A library for accessing the Kolab user database.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A factory for Kolab server objects.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Factory_Injector
implements Horde_Kolab_Server_Factory
{
    /**
     * The conn factory class.
     *
     * @param string
     */
    private $_conn_factory;

    /**
     * Configuration parameters for the server.
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
     * @param Horde_Injector $injector The injector to use.
     */
    public function __construct(
        Horde_Injector $injector
    ) {
        $this->_conn_factory  = $factory;
        $this->_configuration = $config;
        $this->_injector      = $injector;
        $this->_setup();
    }

    /**
     * Prepares the injector with basic configuration information.
     *
     * @param string         $factory  The class name of the conn connection
     *                                 factory.
     * @param array          $config   Configuration parameters for the server.
     * @param Horde_Injector $injector The injector to use.
     *
     * @return NULL
     */
    static public function setup(
        $factory,
        array $config,
        Horde_Injector $injector
    ) {
        self::_setupConfiguration($config, $injector);
        self::_setupConnectionFactory($factory, $injector);
    }

    /**
     * Inject the server configuration.
     *
     * @param array          $config   Configuration parameters for the server.
     * @param Horde_Injector $injector The injector to use.
     *
     * @return NULL
     */
    static private function _setupConfiguration(
        array $config,
        Horde_Injector $injector
    ) {
        $injector->setInstance(
            'Horde_Kolab_Server_Config', $config
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Factory_Conn.
     *
     * @param string         $factory  The class name of the conn connection
     *                                 factory.
     * @param Horde_Injector $injector The injector to use.
     *
     * @return NULL
     */
    static private function _setupConnectionFactory(
        $factory,
        Horde_Injector $injector
    ) {
        $injector->bindImplementation(
            'Horde_Kolab_Server_Factory_Conn', $factory
        );
    }

    /**
     * Setup the machinery to create Horde_Kolab_Server objects.
     *
     * @return NULL
     */
    private function _setup()
    {
        $this->_setupObjects();
        $this->_setupSearch();
        $this->_setupSchema();
        $this->_setupStructure();
        $this->_setupConnection();
        $this->_setupServer();
        $this->_setupComposite();
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Objects handler.
     *
     * @return NULL
     */
    private function _setupObjects()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_Server_Objects',
            'Horde_Kolab_Server_Objects_Base'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Search handler.
     *
     * @return NULL
     */
    private function _setupSearch()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_Server_Search',
            'Horde_Kolab_Server_Search_Base'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Schema handler.
     *
     * @return NULL
     */
    private function _setupSchema()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_Server_Schema',
            'Horde_Kolab_Server_Schema_Base'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Structure handler.
     *
     * @return NULL
     */
    private function _setupStructure()
    {
        if (!isset($this->_configuration['structure']['driver'])) {
            $driver = 'Horde_Kolab_Server_Structure_Kolab';
        } else {
            $driver = $this->_configuration['structure']['driver'];
        }

        $injector->bindImplementation('Horde_Kolab_Server_Structure', $driver);
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server.
     *
     * @return NULL
     */
    private function _setupConnection()
    {
        $injector->bindFactory(
            'Horde_Kolab_Server_Connection',
            'Horde_Kolab_Server_Factory_Conn_Injector',
            'getConnection'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server.
     *
     * @return NULL
     */
    private function _setupServer()
    {
        $injector->bindFactory(
            'Horde_Kolab_Server',
            'Horde_Kolab_Server_Factory_Injector',
            'getServer'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Composite server.
     *
     * @return NULL
     */
    private function _setupComposite()
    {
        /**
         * Nothing to do here for now as class and interface name are the same.
         */
    }

    /**
     * Return the conn server connection that should be used.
     *
     * @return Horde_Kolab_Server The Horde_Kolab_Server connection.
     */
    public function getConnectionFactory()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Factory_Conn');
    }

    /**
     * Return the conn server connection that should be used.
     *
     * @return Horde_Kolab_Server The Horde_Kolab_Server connection.
     */
    public function getConnection()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Connection');
    }

    /**
     * Returns the server configuration parameters.
     *
     * @return array The configuration parameters.
     */
    public function getConfiguration()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Configuration');
    }

    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server The Horde_Kolab_Server connection.
     */
    public function getServer()
    {
        $configuration = $this->getConfiguration();
        if (!isset($configuration['basedn'])) {
            throw new Horde_Kolab_Server_Exception('The base DN is missing');
        }

        $connection = $this->getConnection();

        if (!isset($configuration['filter'])) {
            $server = new Horde_Kolab_Server_Ldap_Standard(
                $connection,
                $configuration['basedn']
            );
        } else {
            $server = new Horde_Kolab_Server_Ldap_Filtered(
                $connection,
                $configuration['basedn'],
                $configuration['filter']
            );
        }
        return $server;
    }

    /**
     * Return the object handler that should be used.
     *
     * @return Horde_Kolab_Server_Objects The handler for objects on the server.
     */
    public function getObjects()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Objects');
    }

    /**
     * Return the structural representation that should be used.
     *
     * @return Horde_Kolab_Server_Structure The representation of the db
     *                                      structure.
     */
    public function getStructure()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Structure');
    }

    /**
     * Return the search handler that should be used.
     *
     * @return Horde_Kolab_Server_Search The search handler.
     */
    public function getSearch()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Search');
    }

    /**
     * Return the db schema representation that should be used.
     *
     * @return Horde_Kolab_Server_Schema The db schema representation.
     */
    public function getSchema()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Schema');
    }

    /**
     * Returns a concrete Horde_Kolab_Server_Composite instance.
     *
     * @return Horde_Kolab_Server_Composite The newly created concrete
     *                                      Horde_Kolab_Server_Composite
     *                                      instance.
     */
    public function getComposite()
    {
        return $this->_injector->getInstance('Horde_Kolab_Server_Composite');
    }
}