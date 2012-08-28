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

    /** Identifies the basic query_set */
    const QUERYSET_BASIC  = 'basic';

    /** Identifies the Horde specific query_set */
    const QUERYSET_HORDE  = 'horde';

    /** The collection of list queries currently supported */
    static private $_supported_queries = array(
        self::QUERY_BASE,
        self::QUERY_ACL,
        self::QUERY_SHARE
    );

    /** The collection of list queries currently supported */
    static private $_querysets = array(
        self::QUERYSET_BASIC => array(
            self::QUERY_BASE,
            self::QUERY_ACL,
        ),
        self::QUERYSET_HORDE => array(
            self::QUERY_BASE,
            self::QUERY_ACL,
            self::QUERY_SHARE,
        )
    );

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

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
     * @param Horde_Kolab_Storage_Driver $driver  The primary connection driver.
     * @param array                      $params  
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver, $params = array())
    {
        $this->_driver = $driver;
        $this->_params = $params;
        $this->_prepareManipulationHandler();
        $this->_prepareSynchronizationHandler();
        $this->_prepareQueries();
    }

    /**
     * Setup for the manipulation handler.
     */
    private function _prepareManipulationHandler()
    {
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base(
            $this->_driver
        );
        if (isset($this->_params['logger'])) {
            $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
                $manipulation, $this->_params['logger']
            );
        }
        $this->_manipulation = $manipulation;
    }

    /**
     * Setup for the synchronization handler.
     */
    private function _prepareSynchronizationHandler()
    {
        $synchronization = new Horde_Kolab_Storage_List_Synchronization_Base(
            $this->_driver
        );
        if (isset($this->_params['logger'])) {
            $synchronization = new Horde_Kolab_Storage_List_Synchronization_Decorator_Log(
                $synchronization, $this->_params['logger']
            );
        }
        $this->_synchronization = $synchronization;
    }

    /**
     * Setup the queries.
     */
    private function _prepareQueries()
    {
        $query_list = array();
        if (isset($this->_params['queryset'])) {
            if (empty(self::$_querysets[$this->_params['queryset']])) {
                throw new Horde_Kolab_Storage_List_Exception(
                    sprintf("Invalid queryset '%s'!", $this->_params['queryset'])
                );
            }
            $query_list = array_merge(
                $query_list, self::$_querysets[$this->_params['queryset']]
            );
        }
        if (isset($this->_params['queries'])) {
            $query_list = array_merge($query_list, $this->_params['queries']);
        }
        if (empty($query_list)) {
            $query_list = self::$_querysets[self::QUERYSET_BASIC];
        }
        foreach ($query_list as $query) {
            $method = '_prepare' . $query . 'Query';
            $this->{$method}();
        }
    }

    /**
     * Prepare the general list query.
     */
    private function _prepareListQuery()
    {
        $this->_queries[self::QUERY_BASE] = new Horde_Kolab_Storage_List_Query_List_Base(
            $this->_driver,
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
    }

    /**
     * Prepare the ACL query.
     */
    private function _prepareAclQuery()
    {
        $this->_queries[self::QUERY_ACL] = new Horde_Kolab_Storage_List_Query_Acl_Base(
            $this->_driver
        );
    }

    /**
     * Prepare the query for shares.
     */
    private function _prepareShareQuery()
    {
        $this->_queries[self::QUERY_SHARE] = new Horde_Kolab_Storage_List_Query_Share_Base(
            $this->_driver
        );
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

