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
    static public function setup(array $configuration, Horde_Injector $injector)
    {
        self::setupObjects($injector);
        self::setupSearch($injector);
        self::setupSchema($injector);

        self::setupStructure(
            isset($configuration['structure'])
            ? $configuration['structure'] : array(),
            $injector
        );
        unset($configuration['structure']);

        self::setupCache(
            $injector,
            isset($configuration['cache'])
            ? $configuration['cache'] : null
        );
        unset($configuration['cache']);

        self::setupLogger(
            $injector,
            isset($configuration['logger'])
            ? $configuration['logger'] : null
        );
        unset($configuration['logger']);

        self::setupServer($configuration, $injector);
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
        array $configuration,
        Horde_Injector $injector
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
     * Provide a cache handler for Horde_Kolab_Server.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     * @param mixed          $instance The cache handler or empty if it
     *                                 should be created.
     *
     * @return NULL
     */
    static protected function setupCache(
        Horde_Injector $injector,
        $instance = null
    ) {
        if (empty($instance)) {
            $instance = new Horde_Cache_Null();
        }
        $injector->setInstance('Horde_Kolab_Server_Cache', $instance);
    }

    /**
     * Provide a log handler for Horde_Kolab_Server.
     *
     * @param Horde_Injector $injector The object providing our dependencies.
     * @param mixed          $instance The log handler or empty if it
     *                                 should be created.
     *
     * @return NULL
     */
    static protected function setupLogger(
        Horde_Injector $injector,
        $instance = null
    ) {
        if (empty($instance)) {
            $instance = new Horde_Log_Logger(new Horde_Log_Handler_Null());
        }
        $injector->setInstance('Horde_Kolab_Server_Logger', $instance);
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
    static protected function setupServer(
        array $configuration,
        Horde_Injector $injector
    ) {
        if (empty($configuration['driver'])) {
            $configuration['driver'] = 'Horde_Kolab_Server_Ldap';
        }
         
        $config = new stdClass;

        switch (ucfirst(strtolower($configuration['driver']))) {
        case 'Ldap':
        case 'Test':
        case 'File':
            $config->driver = 'Horde_Kolab_Server_'
                . ucfirst(strtolower($configuration['driver']));
            break;
        default:
            $config->driver = $configuration['driver'];
            break;
        }

        $config->params = isset($configuration['params'])
            ? $configuration['params'] : array();

        $injector->setInstance('Horde_Kolab_Server_Config', $config);

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
    static public function &getServer(Horde_Injector $injector)
    {
        $config = $injector->getInstance('Horde_Kolab_Server_Config');
        $driver = $config->driver;
        $server = new $driver(
            $injector->getInstance('Horde_Kolab_Server_Objects'),
            $injector->getInstance('Horde_Kolab_Server_Structure'),
            $injector->getInstance('Horde_Kolab_Server_Search'),
            $injector->getInstance('Horde_Kolab_Server_Schema')
        );
        $server->setParams($config->params);
        $server->setCache($injector->getInstance('Horde_Kolab_Server_Cache'));
        $server->setLogger($injector->getInstance('Horde_Kolab_Server_Logger'));

        return $server;
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
        $signature = hash('md5', serialize(ksort($params)));

        if (!isset(self::$_instances[$signature])) {
            $params['cache'] = Horde_Cache::singleton(
                $GLOBALS['conf']['cache']['driver'],
		//@todo: Can we omit Horde:: here?
                Horde::getDriverConfig(
                    'cache',
                    $GLOBALS['conf']['cache']['driver']
                )
            );
            $params['logger'] = Horde::getLogger();
            $injector = new Horde_Injector(new Horde_Injector_TopLevel());
            self::setup($params, $injector);
            self::$_instances[$signature] = $injector->getInstance(
                'Horde_Kolab_Server'
            );
        }

/*         if (empty($this->_ldap_read)) { */
/*             $this->handleError( */
/*                 Net_LDAP2::checkLDAPExtension(), */
/*                 Horde_Kolab_Server_Exception::MISSING_LDAP_EXTENSION */
/*             ); */

/*             $this->_ldap_read = new Net_LDAP2($this->params); */

/*             if (isset($this->params['host_master']) */
/*                 && $this->params['host_master'] == $this->params['host'] */
/*             ) { */

/*                 $params         = $this->params; */
/*                 $params['host'] = $this->params['host_master']; */

/*                 $this->_ldap_write = new Net_LDAP2($params); */
/*             } else { */
/*                 $this->_ldap_write = $this->_ldap_read; */
/*             } */
/*         } */

/*         if ($write) { */
/*             return $this->_ldap_write; */
/*         } else { */
/*             return $this->_ldap_read; */
/*         } */

        return self::$_instances[$signature];
    }
}