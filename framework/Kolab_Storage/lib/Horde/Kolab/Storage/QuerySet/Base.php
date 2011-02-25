<?php
/**
 * Adds a set of uncached queries to the list handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Adds a set of uncached queries to the list handlers.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
abstract class Horde_Kolab_Storage_QuerySet_Base
implements Horde_Kolab_Storage_QuerySet
{
    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     */
    public function __construct(Horde_Kolab_Storage_Factory $factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Add the set of list queries.
     *
     * @param Horde_Kolab_Storage_List $list   The list.
     * @param array                    $params Additional query parameters.
     *
     * @return NULL
     */
    public function addListQuerySet(Horde_Kolab_Storage_List $list, $params = array())
    {
        $this->_addListQuery($list, Horde_Kolab_Storage_List::QUERY_BASE);
        $this->_addListQuery($list, Horde_Kolab_Storage_List::QUERY_ACL);
    }

    /**
     * Add a list query.
     *
     * @param Horde_Kolab_Storage_List $list   The list.
     * @param string                   $type   The query type.
     * @param array                    $params Additional query parameters.
     *
     * @return NULL
     */
    private function _addListQuery(Horde_Kolab_Storage_List $list, $type, $params = array())
    {
        if (isset($this->_class_map[$type])) {
            $params = array_merge(
                $params,
                $this->_getListQueryParameters($list)
            );
            $list->registerQuery(
                $type,
                $this->_createListQuery(
                    $this->_class_map[$type], $list, $params
                )
            );
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Query type %s not supported!', $type)
            );
        }
    }

    /**
     * Fetch any additional parameters required when creating list queries.
     *
     * @param Horde_Kolab_Storage_List $list   The list.
     *
     * @return array The parameters for list queries.
     */
    abstract protected function _getListQueryParameters(
        Horde_Kolab_Storage_List $list
    );

    /**
     * Create the specified list query type.
     *
     * @param string                   $name   The query name.
     * @param Horde_Kolab_Storage_List $list   The list that should be queried.
     * @param array                    $params Additional parameters provided
     *                                         to the query constructor.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    private function _createListQuery($name, Horde_Kolab_Storage_List $list, $params = array())
    {
        return $this->_createQuery($name, $list, $params);
    }

    /**
     * Create the specified data query type.
     *
     * @param string                   $name   The query name.
     * @param Horde_Kolab_Storage_Data $data   The data that should be queried.
     * @param array                    $params Additional parameters provided
     *                                         to the query constructor.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    private function _createDataQuery($name, Horde_Kolab_Storage_Data $data, $params = array())
    {
        return $this->_createQuery($name, $data, $params);
    }

    /**
     * Create the specified query type.
     *
     * @param string $name   The query name.
     * @param mixed  $data   The data that should be queried.
     * @param array  $params Additional parameters provided
     *                       to the query constructor.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    private function _createQuery($name, $data, $params = array())
    {
        if (class_exists($name)) {
            $constructor_params = array_merge(
                array('factory' => $this->_factory), $params
            );
            $query = new $name($data, $constructor_params);
        } else {
            throw new Horde_Kolab_Storage_Exception(sprintf('No such query "%s"!', $name));
        }
        return $query;
    }

}

