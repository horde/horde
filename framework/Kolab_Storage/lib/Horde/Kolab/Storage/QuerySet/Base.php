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
class Horde_Kolab_Storage_QuerySet_Base
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
        $this->_factory   = $factory;
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
        $this->addListQuery($list, Horde_Kolab_Storage_List::QUERY_BASE);
        $this->addListQuery($list, Horde_Kolab_Storage_List::QUERY_ACL);
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
    public function addListQuery(Horde_Kolab_Storage_List $list, $type, $params = array())
    {
        switch ($type) {
        case Horde_Kolab_Storage_List::QUERY_SHARE:
            $class = 'Horde_Kolab_Storage_List_Query_Share_Base';
            break;
        case Horde_Kolab_Storage_List::QUERY_BASE:
            $class = 'Horde_Kolab_Storage_List_Query_List_Base';
            break;
        case Horde_Kolab_Storage_List::QUERY_ACL:
            $class = 'Horde_Kolab_Storage_List_Query_Acl_Base';
            break;
        default:
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Query type %s not supported!', $type)
            );
        }
        $list->registerQuery(
            $type, $this->_factory->createListQuery($class, $list, $params)
        );
    }
}

