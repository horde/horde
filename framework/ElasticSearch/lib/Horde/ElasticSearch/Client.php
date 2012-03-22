<?php
/**
 * ElasticSearch client class
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  ElasticSearch
 */
class Horde_ElasticSearch_Client
{
    protected $_server = 'http://localhost:9200/';
    protected $_httpClient;

    public function __construct($server, Horde_Http_Client $httpClient)
    {
        $this->_server = $server;
        $this->_httpClient = $httpClient;
    }

    /**
     * curl -X GET {SERVER}/_status
     */
    public function status($index = null)
    {
        return $this->_request($this->_path($index, '_status'));
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/{TYPE}/_search?q= ...
     */
    public function search($index, $type, $q)
    {
        return $this->_request($this->_path($index, $type, '_search') . '?' . http_build_query(array('q' => $q)));
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/{TYPE/{ID}
     */
    public function get($index, $type, $id)
    {
        return $this->_request($this->_path($index, $type, $id));
    }

    /**
     * curl -X PUT {SERVER}/{INDEX}/{TYPE}/{ID} -d ...
     */
    public function add($index, $type, $id, $data)
    {
        return $this->_request($this->_path($index, $type, $id), 'PUT', $data);
    }

    /**
     * curl -X GET {SERVER}/{INDEX}/{TYPE}/_count -d {matchAll:{}}
     */
    public function count($index, $type)
    {
        return $this->_request($this->_path($index, $type, '_count'), 'GET', '{ matchAll:{} }');
    }

    /**
     * curl -X PUT {SERVER}/{INDEX}/{TYPE}/_mapping -d ...
     */
    public function map($index, $type, $data)
    {
        return $this->_request($this->_path($index, $type, '_mapping'), 'PUT', $data);
    }

    protected function _request($path, $method = 'GET', $data = null, $headers = array())
    {
        try {
            $result = $this->_httpClient->request($method, $this->_server . $path, $data, $headers);
            return json_decode($result->getBody());
        } catch (Horde_Http_Exception $e) {
            throw new Horde_ElasticSearch_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function _path()
    {
        $path = array_filter(func_get_args());
        foreach ($path as &$element) { $element = urlencode($element); }
        return implode('/', $path);
    }
}
