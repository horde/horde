<?php
/**
 * Horde_Service_Twitter_Request_Oauth class wraps sending requests to Twitter's
 * REST API using OAuth authentication.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Request_Basic extends Horde_Service_Twitter_Request
{

    protected $_twitter;

    public function __construct($twitter)
    {
        $this->_twitter = $twitter;
    }

    public function get($url, $params = array())
    {
        $client = new Horde_Http_Client();
        $response = $client->get($url, array('Authorization' => $this->_twitter->auth->buildAuthorizationHeader()));

        return $response->getBody();
    }

    public function post($url, $params = array())
    {
        $client = new Horde_Http_Client();
        $response = $client->post($url, $params, array('Authorization' => $this->_twitter->auth->buildAuthorizationHeader()));

        return $response->getBody();
    }

}
