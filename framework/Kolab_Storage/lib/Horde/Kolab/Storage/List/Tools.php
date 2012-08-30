<?php
/**
 * Manages and provides the toolset available for dealing with the list of Kolab folders.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Manages and provides the toolset available for dealing with the list of Kolab folders.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_List_Tools
{
    /** Identifies the basic list query */
    const QUERY_BASE  = 'List';

    /** Identifies the ACL query */
    const QUERY_ACL   = 'Acl';

    /** Identifies the share query */
    const QUERY_SHARE = 'Share';

    /** The collection of list queries currently supported */
    static private $_supported_queries = array(
        self::QUERY_BASE,
        self::QUERY_ACL,
        self::QUERY_SHARE
    );

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The Kolab Storage data cache.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * The list specific cache.
     *
     * @var Horde_Kolab_Storage_List_Cache
     */
    private $_list_cache;

    /**
     * A logger.
     *
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Parameters for constructing the various tools.
     *
     * @var array
     */
    private $_params;

    /**
     * The handler for list manipulations.
     *
     * @var Horde_Kolab_Storage_List_Manipulation
     */
    private $_manipulation;

    /**
     * The handler for synchronizing with the backend.
     *
     * @var Horde_Kolab_Storage_List_Synchronization
     */
    private $_synchronization;

    /**
     * The queries currently registered.
     *
     * @var array
     */
    private $_queries;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver  The backend driver.
     * @param Horde_Kolab_Storage_Cache  $cache   The cache.
     * @param Horde_Log_Logger           $logger  A logger.
     * @param array                      $params  
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver,
                                Horde_Kolab_Storage_Cache $cache,
                                $logger,
                                $params = array())
    {
        $this->_driver = $driver;
        $this->_cache  = $cache;
        $this->_logger = $logger;
        $this->_params = $params;
        $this->_prepareManipulationHandler();
        $this->_prepareSynchronizationHandler();
        $this->_prepareListCache();
        $this->_prepareQueries();
    }

    /**
     * Setup for the manipulation handler.
     */
    private function _prepareManipulationHandler()
    {
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($this->_driver);
        if (isset($this->_params['log'])
            && (in_array('debug', $this->_params['log'])
                || in_array('list_manipulation', $this->_params['log']))) {
            $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
                $manipulation, $this->_logger
            );
        }
        $this->_manipulation = $manipulation;
    }

    /**
     * Setup for the synchronization handler.
     */
    private function _prepareSynchronizationHandler()
    {
        $synchronization = new Horde_Kolab_Storage_List_Synchronization_Base($this->_driver);
        if (isset($this->_params['log'])
            && (in_array('debug', $this->_params['log'])
                || in_array('list_synchronization', $this->_params['log']))) {
            $synchronization = new Horde_Kolab_Storage_List_Synchronization_Decorator_Log(
                $synchronization, $this->_logger
            );
        }
        $this->_synchronization = $synchronization;
    }

    /**
     * Setup the list cache.
     */
    private function _prepareListCache()
    {
        $this->_list_cache = new Horde_Kolab_Storage_List_Cache(
            $this->_cache, $this->_driver->getParameters()
        );
    }

    /**
     * Setup the queries.
     */
    private function _prepareQueries()
    {
        if (isset($this->_params['queries']['list'])) {
            $query_list = array_keys($this->_params['queries']['list']);
        } else {
            $query_list = array(self::QUERY_BASE);
        }
        foreach ($query_list as $query) {
            $method = '_prepare' . $query . 'Query';
            if (isset($this->_params['queries']['list'][$query])) {
                $this->{$method}($this->_params['queries']['list'][$query]);
            } else {
                $this->{$method}();
            }
        }
    }

    /**
     * Prepare the general list query.
     *
     * @param array $params Query specific configuration parameters.
     */
    private function _prepareListQuery($params = null)
    {
        if (!empty($params['defaults_bail'])) {
            $defaults = new Horde_Kolab_Storage_List_Query_List_Defaults_Bail();
        } else {
            $defaults = new Horde_Kolab_Storage_List_Query_List_Defaults_Log(
                $this->_logger
            );
        }
        if (empty($params['cache'])) {
            $this->_queries[self::QUERY_BASE] = new Horde_Kolab_Storage_List_Query_List_Base(
                $this->_driver,
                new Horde_Kolab_Storage_Folder_Types(),
                $defaults
            );
        } else {
            $this->_queries[self::QUERY_BASE] = new Horde_Kolab_Storage_List_Query_List_Cache(
                new Horde_Kolab_Storage_List_Query_List_Cache_Synchronization(
                    $this->_driver,
                    new Horde_Kolab_Storage_Folder_Types(),
                    $defaults
                ),
                $this->_list_cache
            );
            $this->_synchronization->registerListener($this->_queries[self::QUERY_BASE]);
            $this->_manipulation->registerListener($this->_queries[self::QUERY_BASE]);
        }
    }

    /**
     * Prepare the ACL query.
     *
     * @param array $params Query specific configuration parameters.
     */
    private function _prepareAclQuery($params = null)
    {
        $this->_queries[self::QUERY_ACL] = new Horde_Kolab_Storage_List_Query_Acl_Base(
            $this->_driver
        );
        if (!empty($params['cache'])) {
            $this->_queries[self::QUERY_ACL] = new Horde_Kolab_Storage_List_Query_Acl_Cache(
                $this->_queries[self::QUERY_ACL], $this->_list_cache
            );
            $this->_synchronization->registerListener($this->_queries[self::QUERY_ACL]);
            $this->_manipulation->registerListener($this->_queries[self::QUERY_ACL]);
        }
    }

    /**
     * Prepare the query for shares.
     *
     * @param array $params Query specific configuration parameters.
     */
    private function _prepareShareQuery($params = null)
    {
        $this->_queries[self::QUERY_SHARE] = new Horde_Kolab_Storage_List_Query_Share_Base(
            $this->_driver
        );
        if (!empty($params['cache'])) {
            $this->_queries[self::QUERY_SHARE] = new Horde_Kolab_Storage_List_Query_Share_Cache(
                $this->_queries[self::QUERY_SHARE], $this->_list_cache
            );
            $this->_synchronization->registerListener($this->_queries[self::QUERY_SHARE]);
            $this->_manipulation->registerListener($this->_queries[self::QUERY_SHARE]);
        }
    }

    /**
     * Return the ID of the underlying connection.
     *
     * @return string The connection ID.
     */
    public function getId()
    {
        return $this->_driver->getId();
    }

    /**
     * Return the namespace handler for the underlying connection.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The namespace handler.
     */
    public function getNamespace()
    {
        return $this->_driver->getNamespace();
    }

    /**
     * Return the handler for list manipulations.
     */
    public function getListManipulation()
    {
        return $this->_manipulation;
    }

    /**
     * Return the handler for list synchronizations.
     */
    public function getListSynchronization()
    {
        return $this->_synchronization;
    }

    /**
     * Return a query object.
     *
     * @param string $type The query type that should be returned.
     */
    public function getQuery($type = null)
    {
        if ($type === null) {
            $type = self::QUERY_BASE;
        }
        if (!in_array($type, self::$_supported_queries)) {
            throw new Horde_Kolab_Storage_List_Exception(
                sprintf("Queries of type '%s' are not supported!", $type)
            );
        }
        if (!isset($this->_queries[$type])) {
            throw new Horde_Kolab_Storage_List_Exception(
                sprintf("No query of type '%s' registered!", $type)
            );
        }
        return $this->_queries[$type];
    }
}

