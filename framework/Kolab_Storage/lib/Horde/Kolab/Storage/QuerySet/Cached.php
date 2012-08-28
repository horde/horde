<?php
/**
 * Adds a set of cached queries to the list handlers.
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
 * Adds a set of cached queries to the list handlers.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_QuerySet_Cached
extends Horde_Kolab_Storage_QuerySet_Base
{
    /**
     * The query class map.
     *
     * @var array
     */
    protected $_class_map = array(
        Horde_Kolab_Storage_Data::QUERY_PREFS => 'Horde_Kolab_Storage_Data_Query_Preferences_Cache',
        Horde_Kolab_Storage_Data::QUERY_HISTORY => 'Horde_Kolab_Storage_Data_Query_History_Cache',
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
     * @param array                       $params  Optional parameters.
     * @param Horde_Kolab_Storage_Cache   $cache   The cache.
     */
    public function __construct(Horde_Kolab_Storage_Factory $factory,
                                array $params = array(),
                                Horde_Kolab_Storage_Cache $cache = null)
    {
        parent::__construct($factory, $params);
        $this->_cache = $cache;
    }

    /**
     * Fetch any additional parameters required when creating data queries.
     *
     * @param Horde_Kolab_Storage_Data $data   The data.
     *
     * @return array The parameters for data queries.
     */
    protected function _getDataQueryParameters(Horde_Kolab_Storage_Data $data)
    {
        return array(
            'cache' => $this->_cache->getDataCache(
                $data->getIdParameters()
            )
        );
    }
}

