<?php
/**
 * A Horde_Injector:: based Horde_Kolab_Server:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Kolab_Server:: factory.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_KolabServer
{
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
        $this->_injector = $injector;
        $this->_setup();
    }

    /**
     * Setup the machinery to create Horde_Kolab_Server objects.
     *
     * @return NULL
     */
    private function _setup()
    {
        $this->_setupConfiguration();
        $this->_setupConnection();
        $this->_setupObjects();
        $this->_setupSearch();
        $this->_setupSchema();
        $this->_setupStructure();
        $this->_setupServer();
    }

    /**
     * Inject the server configuration.
     *
     * @return NULL
     */
    private function _setupConfiguration()
    {
        $configuration = array();

        //@todo: Update configuration parameters
        if (!empty($GLOBALS['conf']['kolab']['ldap'])) {
            $configuration = $GLOBALS['conf']['kolab']['ldap'];
        }
        if (!empty($GLOBALS['conf']['kolab']['server'])) {
            $configuration = $GLOBALS['conf']['kolab']['server'];
        }

        if (isset($configuration['server'])) {
            $configuration['host'] = $configuration['server'];
            unset($configuration['server']);
        }

        if (isset($configuration['phpdn'])) {
            $configuration['binddn'] = $configuration['phpdn'];
            unset($configuration['phpdn']);
        }

        if (isset($configuration['phppw'])) {
            $configuration['bindpw'] = $configuration['phppw'];
            unset($configuration['phppw']);
        }

        $this->_injector->setInstance(
            'Horde_Kolab_Server_Configuration', $configuration
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Connection.
     *
     * @return NULL
     */
    private function _setupConnection()
    {
        $this->_injector->bindFactory(
            'Horde_Kolab_Server_Connection',
            'Horde_Core_Factory_KolabServer',
            'getConnection'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Objects handler.
     *
     * @return NULL
     */
    private function _setupObjects()
    {
        $this->_injector->bindImplementation(
            'Horde_Kolab_Server_Objects_Interface',
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
            'Horde_Kolab_Server_Search_Interface',
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
            'Horde_Kolab_Server_Schema_Interface',
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
        $configuration = $this->getConfiguration();
        if (!isset($configuration['structure']['driver'])) {
            $driver = 'Horde_Kolab_Server_Structure_Kolab';
        } else {
            $driver = $configuration['structure']['driver'];
        }

        $this->_injector->bindImplementation(
            'Horde_Kolab_Server_Structure_Interface', $driver
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server.
     *
     * @return NULL
     */
    private function _setupServer()
    {
        $this->_injector->bindFactory(
            'Horde_Kolab_Server_Interface',
            'Horde_Core_Factory_KolabServer',
            'getServer'
        );
    }

    /**
     * Return the conn server connection that should be used.
     *
     * @return Horde_Kolab_Server The Horde_Kolab_Server connection.
     */
    public function getConnection()
    {
        $configuration = $this->_injector->getInstance('Horde_Kolab_Server_Configuration');
        if (empty($configuration['mock'])) {
            if (!isset($configuration['basedn'])) {
                throw new Horde_Exception('The parameter \'basedn\' is missing in the Kolab server configuration!');
            }

            $ldap_read = new Horde_Ldap($configuration);
            if (isset($configuration['host_master'])) {
                $configuration['host'] = $configuration['host_master'];
                $ldap_write = new Horde_Ldap($configuration);
                $connection = new Horde_Kolab_Server_Connection_Splittedldap(
                    $ldap_read, $ldap_write
                );
            } else {
                $connection = new Horde_Kolab_Server_Connection_Simpleldap(
                    $ldap_read
                );
            }
            return $connection;
        } else {
            if (isset($configuration['data'])) {
                $data = $configuration['data'];
            } else {
                $data = array();
            }
            $connection = new Horde_Kolab_Server_Connection_Mock(
                new Horde_Kolab_Server_Connection_Mock_Ldap(
                    $configuration, $data
                )
            );
            return $connection;
        }
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
            throw new Horde_Exception('The parameter \'basedn\' is missing in the Kolab server configuration!');
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

        if (isset($configuration['map'])) {
            $server = new Horde_Kolab_Server_Decorator_Map(
                $server, $configuration['map']
            );
        }

        if (isset($configuration['debug']) || isset($configuration['log'])) {
            $server = new Horde_Kolab_Server_Decorator_Log(
                $server, $this->_injector->getInstance('Horde_Log_Logger')
            );
        }

        if (isset($configuration['debug']) || isset($configuration['count'])) {
            $server = new Horde_Kolab_Server_Decorator_Count(
                $server, $this->_injector->getInstance('Horde_Log_Logger')
            );
        }

        if (!empty($configuration['cleanup'])) {
            $server = new Horde_Kolab_Server_Decorator_Clean(
                $server
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
        return $this->_injector->getInstance(
            'Horde_Kolab_Server_Objects_Interface'
        );
    }

    /**
     * Return the structural representation that should be used.
     *
     * @return Horde_Kolab_Server_Structure The representation of the db
     *                                      structure.
     */
    public function getStructure()
    {
        return $this->_injector->getInstance(
            'Horde_Kolab_Server_Structure_Interface'
        );
    }

    /**
     * Return the search handler that should be used.
     *
     * @return Horde_Kolab_Server_Search The search handler.
     */
    public function getSearch()
    {
        return $this->_injector->getInstance(
            'Horde_Kolab_Server_Search_Interface'
        );
    }

    /**
     * Return the db schema representation that should be used.
     *
     * @return Horde_Kolab_Server_Schema The db schema representation.
     */
    public function getSchema()
    {
        return $this->_injector->getInstance(
            'Horde_Kolab_Server_Schema_Interface'
        );
    }

    /**
     * Returns a concrete Horde_Kolab_Server_Composite instance.
     *
     * @return Horde_Kolab_Server_Composite The newly created concrete
     *                                      Horde_Kolab_Server_Composite
     *                                      instance.
     */
    public function create()
    {
        return new Horde_Kolab_Server_Composite(
            $this->getServer(),
            $this->getObjects(),
            $this->getStructure(),
            $this->getSearch(),
            $this->getSchema()
        );
    }
}