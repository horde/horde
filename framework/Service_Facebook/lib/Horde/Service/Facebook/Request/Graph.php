<?php
/**
 * Horde_Service_Facebook_Request_Graph:: encapsulate a request to the
 * Graph API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Request_Graph extends Horde_Service_Facebook_Request
{
    protected $_endpoint;

    protected $_request = 'GET';

    public function __construct(
        $facebook, $method = '', array $params = array(), array $options = array())
    {
        parent::__construct($facebook, $method, $params);

        $this->_endpoint = $facebook->getFacebookUrl('graph');
        if (!empty($options['request'])) {
            $this->_request = $options['request'];
        }
    }

    /**
     * Run this request and return the data.
     *
     * @return mixed Either raw JSON, or an array of decoded values.
     * @throws Horde_Service_Facebook_Exception
     */
    public function run()
    {
        $url = new Horde_Url($this->_endpoint . '/' . $this->_method);
        $this->_params['access_token'] = $this->_facebook->auth->getSessionKey();
        if ($this->_request != 'POST') {
            $url->add($this->_params);
            $params = array();
        } else {
            $params = $this->_params;
        }

        try {
            $result = $this->_http->request($this->_request, $url->toString(true), $params);
        } catch (Horde_Http_Client_Exception $e) {
            $this->_facebook->logger->err($e->getMessage());
            throw new Horde_Service_Facebook_Exception($e);
        }

        if ($result->code != '200') {
            throw new Horde_Service_Facebook_Exception($result->getBody());
        }

        return json_decode($result->getBody());
    }

}