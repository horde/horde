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
class Horde_Kolab_Server_Factory
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static private $_instances = array();

    /**
     * Setup the machinery to create Horde_Kolab_Server objects.
     *
     * @param array          $configuration The parameters required to create
     *                                      the desired Horde_Kolab_Server object.
     * @param Horde_Injector $injector      The object providing our dependencies.
     *
     * @return NULL
     */
    static public function setup(Horde_Injector $injector, array $configuration)
    {
        self::setupObjects($injector);
        self::setupSearch($injector);
        self::setupSchema($injector);

        self::setupStructure(
            $injector,
            isset($configuration['structure'])
            ? $configuration['structure'] : array()
        );
        unset($configuration['structure']);

        self::setupConfiguration($injector, $configuration);

        self::setupServer($injector);
        self::setupComposite($injector);
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Objects handler.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupObjects(Horde_Injector $injector)
    {
        $injector->bindImplementation(
            'Horde_Kolab_Server_Objects',
            'Horde_Kolab_Server_Objects_Base'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Structure handler.
     *
     * @param array          $configuration The parameters required to create
     *                                      the desired
     *                                      Horde_Kolab_Server_Structure handler.
     * @param Horde_Injector $injector      The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupStructure(
        Horde_Injector $injector,
        array $configuration
    ) {
        if (!isset($configuration['driver'])) {
            $configuration['driver'] = 'Horde_Kolab_Server_Structure_Kolab';
        }

        switch (ucfirst(strtolower($configuration['driver']))) {
        case 'Ldap':
        case 'Kolab':
            $driver = 'Horde_Kolab_Server_Structure_'
                . ucfirst(strtolower($configuration['driver']));
            break;
        default:
            $driver = $configuration['driver'];
            break;
        }

        $injector->bindImplementation('Horde_Kolab_Server_Structure', $driver);
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Search handler.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupSearch(Horde_Injector $injector)
    {
        $injector->bindImplementation(
            'Horde_Kolab_Server_Search',
            'Horde_Kolab_Server_Search_Base'
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Schema handler.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupSchema(Horde_Injector $injector)
    {
        $injector->bindImplementation(
            'Horde_Kolab_Server_Schema',
            'Horde_Kolab_Server_Schema_Base'
        );
    }

    /**
     * Inject the server configuration.
     *
     * @param Horde_Injector $injector      The object providing our dependencies.
     * @param array          $configuration The parameters required to create
     *                                      the desired Horde_Kolab_Server.
     *
     * @return NULL
     */
    static protected function setupConfiguration(
        Horde_Injector $injector,
        array $configuration
    ) {
        $injector->setInstance('Horde_Kolab_Server_Config', $configuration);
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server.
     *
     * @param array          $configuration The parameters required to create
     *                                      the desired Horde_Kolab_Server.
     * @param Horde_Injector $injector      The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupServer(Horde_Injector $injector) {
        $injector->bindFactory(
            'Horde_Kolab_Server',
            'Horde_Kolab_Server_Factory',
            'getServer'
        );
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Server instance.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return Horde_Kolab_Server The newly created concrete Horde_Kolab_Server
     *                            instance.
     */
    static public function getServer(Horde_Injector $injector)
    {
        $configuration = $injector->getInstance('Horde_Kolab_Server_Config');

        if (empty($configuration['driver'])) {
            $configuration['driver'] = 'Ldap';
        }

        if (isset($configuration['params'])) {
            $params = $configuration['params'];
        } else {
            $params = $configuration;
        }

        $driver = ucfirst(strtolower($configuration['driver']));
        switch ($driver) {
        case 'Ldap':
        case 'Test':
        case 'File':
            $server = self::getLdapServer($driver, $params);
            break;
        default:
            throw new Horde_Kolab_Server_Exception('Invalid server configuration!');
        }

        if (isset($params['map'])) {
            $server = new Horde_Kolab_Server_Mapped($server, $params['map']);
        }
        if (isset($configuration['logger'])) {
            $server = new Horde_Kolab_Server_Logged($server, $configuration['logger']);
        }
        if (isset($configuration['cache'])) {
            $server = new Horde_Kolab_Server_Cached($server, $configuration['cache']);
        }

        return $server;
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Server_Ldap instance.
     *
     * @param array $params LDAP connection parameters.
     *
     * @return Horde_Kolab_Server_Ldap The newly created concrete
     *                                 Horde_Kolab_Server_Ldap instance.
     */
    static protected function getLdapServer($driver, array $params)
    {
        if (!isset($params['basedn'])) {
            throw new Horde_Kolab_Server_Exception('The base DN is missing');
        }

        if (isset($params['server'])) {
            $params['host'] = $params['server'];
            unset($params['server']);
        }

        if (isset($params['phpdn'])) {
            $params['binddn'] = $params['phpdn'];
            unset($params['phpdn']);
        }

        if (isset($params['phppw'])) {
            $params['bindpw'] = $params['phppw'];
            unset($params['phppw']);
        }

        //@todo: Place this is a specific connection factory.
        switch ($driver) {
        case 'Ldap':
            $ldap_read = new Net_LDAP2($params);
            if (isset($params['host_master'])) {
                $params['host'] = $params['host_master'];
                $ldap_write = new Net_LDAP2($params);
                $connection = new Horde_Kolab_Server_Connection_Splittedldap(
                    $ldap_read, $ldap_write
                );
            } else {
                $connection = new Horde_Kolab_Server_Connection_Simpleldap(
                    $ldap_read
                );
            }
            break;
        case 'File':
        case 'Test':
            $connection = new Horde_Kolab_Server_Connection_Mock($params);
            break;
        }

        if (!isset($params['filter'])) {
            $server = new Horde_Kolab_Server_Ldap_Standard(
                $connection,
                $params['basedn']
            );
        } else {
            $server = new Horde_Kolab_Server_Ldap_Filtered(
                $connection,
                $params['basedn'],
                $params['filter']
            );
        }
        return $server;
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Composite server.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     *
     * @return NULL
     */
    static protected function setupComposite(Horde_Injector $injector)
    {
        /**
         * Nothing to do here for now as class and interface name are the same.
         */
    }

    /**
     * Attempts to return a reference to a concrete Horde_Kolab_Server
     * instance based on $driver. It will only create a new instance
     * if no Horde_Kolab_Server instance with the same parameters currently
     * exists.
     *
     * This method must be invoked as:
     * <code>
     *   $var = &Horde_Kolab_Server::singleton()
     * </code>
     *
     * @param array $params An array of parameters.
     *
     * @return Horde_Kolab_Server The concrete Horde_Kolab_Server reference.
     */
    static public function &singleton($params = array())
    {
        global $conf;

        if (empty($params) && isset($conf['kolab']['ldap'])) {
            $params = $conf['kolab']['ldap'];
        }

        ksort($params);
        $signature = hash('md5', serialize($params));
        if (!isset(self::$_instances[$signature])) {
            /** @todo: The caching decorator is still missing.
/*             $params['cache'] = Horde_Cache::singleton( */
/*                 $GLOBALS['conf']['cache']['driver'], */
/*                 //@todo: Can we omit Horde:: here? */
/*                 Horde::getDriverConfig( */
/*                     'cache', */
/*                     $GLOBALS['conf']['cache']['driver'] */
/*                 ) */
/*             ); */
            $params['logger'] = Horde::getLogger();
            $injector = new Horde_Injector(new Horde_Injector_TopLevel());
            self::setup($injector, $params);
            self::$_instances[$signature] = $injector->getInstance(
                'Horde_Kolab_Server'
            );
        }

        return self::$_instances[$signature];
    }
}