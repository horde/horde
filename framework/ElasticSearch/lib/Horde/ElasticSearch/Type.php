<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  ElasticSearch
 */

/**
 * ElasticSearch type class
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   ElasticSearch
 */
class Horde_ElasticSearch_Type
{
    protected $_type;
    protected $_index;

    public function __construct($type, Horde_ElasticSearch_Index $index)
    {
        $this->_type = $type;
        $this->_index = $index;
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/{TYPE}/_search?q= ...
     */
    public function search($q)
    {
        return $this->_index->search($this->_type, $q);
    }

    /**
     * curl -XGET {SERVER}/{INDEX}/{TYPE/{ID}
     */
    public function get($id)
    {
        return $this->_index->get($this->_type, $id);
    }

    /**
     * curl -X PUT {SERVER}/{INDEX}/{TYPE}/{ID} -d ...
     */
    public function add($id, $data)
    {
        return $this->_index->add($this->_type, $id, $data);
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/{TYPE}/_count -d {matchAll:{}}
     */
    public function count()
    {
        return $this->_index->count($this->_type);
    }

    /**
     * curl -X PUT {SERVER}/{INDEX}/{TYPE}/_mapping -d ...
     */
    public function map($data)
    {
        return $this->_index->map($this->_type, $data);
    }
}
