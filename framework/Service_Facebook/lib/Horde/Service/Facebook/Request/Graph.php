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

    public function __construct($facebook, $method, array $params = array())
    {
        parent::__construct($facebook, $method, $params);
        $this->_endpoint = $facebook->getFacebookUrl('graph');
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
        $url->add('access_token', $this->_facebook->auth->getSessionKey());
        try {
            $result = $this->_http->request('GET', $url->toString());
        } catch (Horde_Http_Client_Exception $e) {
            $this->_facebook->logger->err($e->getMessage());
            throw new Horde_Service_Facebook_Exception($e);
        }

        if ($result->code != '200') {
            throw new Horde_Service_Facebook_Exception();
        }

        return json_decode($result->getBody());
    }

}