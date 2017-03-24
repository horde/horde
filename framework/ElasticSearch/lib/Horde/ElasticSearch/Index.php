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
 * ElasticSearch index class
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   ElasticSearch
 */
class Horde_ElasticSearch_Index
{
    protected $_client;
    protected $_index;

    public function __construct($index, Horde_ElasticSearch_Client $client)
    {
        $this->_index = $index;
        $this->_client = $client;
    }

    /**
     * curl -X PUT {SERVER}/{INDEX}/
     */
    public function create()
    {
        $this->_client->request(null, 'PUT');
    }

    /**
     * curl -X DELETE {SERVER}/{INDEX}/
     */
    public function drop()
    {
        $this->_client->request(null, 'DELETE');
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/_status
     */
    public function status()
    {
        return $this->_client->status($this->_index);
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/{TYPE}/_search?q= ...
     */
    public function search($type, $q)
    {
        return $this->_client->search($this->_index, $type, $q);
    }

    /**
     * curl -XGET {SERVER}/{INDEX}/{TYPE/{ID}
     */
    public function get($type, $id)
    {
        return $this->_client->get($this->_index, $type, $id);
    }

    /**
     * curl -X PUT {SERVER}/{INDEX}/{TYPE}/{ID} -d ...
     */
    public function add($type, $id, $data)
    {
        return $this->_client->add($this->_index, $type, $id, $data);
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/{TYPE}/_count -d {matchAll:{}}
     */
    public function count($type)
    {
        return $this->_client->count($this->_index, $type);
    }

    /**
     * curl -X PUT {SERVER}/{INDEX}/{TYPE}/_mapping -d ...
     */
    public function map($type, $data)
    {
        return $this->_client->map($this->_index, $type, $data);
    }
}
