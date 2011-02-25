<?php
/**
 * Adds a set of cached queries to the list handlers.
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
 * Adds a set of cached queries to the list handlers.
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
class Horde_Kolab_Storage_QuerySet_Cached
extends Horde_Kolab_Storage_QuerySet_Base
{
    /**
     * The query class map.
     *
     * @var array
     */
    protected $_class_map = array(
        Horde_Kolab_Storage_List::QUERY_SHARE => 'Horde_Kolab_Storage_List_Query_Share_Cache',
        Horde_Kolab_Storage_List::QUERY_BASE => 'Horde_Kolab_Storage_List_Query_List_Cache',
        Horde_Kolab_Storage_List::QUERY_ACL => 'Horde_Kolab_Storage_List_Query_Acl_Cache'
    );

    /**
     * The cache.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     * @param Horde_Kolab_Storage_Cache   $cache   The cache.
     */
    public function __construct(
        Horde_Kolab_Storage_Factory $factory,
        Horde_Kolab_Storage_Cache $cache
    ) {
        parent::__construct($factory);
        $this->_cache = $cache;
    }

    /**
     * Fetch any additional parameters required when creating list queries.
     *
     * @param Horde_Kolab_Storage_List $list   The list.
     *
     * @return array The parameters for list queries.
     */
    protected function _getListQueryParameters(Horde_Kolab_Storage_List $list) {
        return array(
            'cache' => $this->_cache->getListCache(
                $list->getIdParameters()
            )
        );
    }
}

