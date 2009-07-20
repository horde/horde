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
class Horde_Service_Twitter_Request_Oauth extends Horde_Service_Twitter_Request
{

    protected $_twitter;

    public function __construct($twitter)
    {
        $this->_twitter = $twitter;
    }

    public function get($url, $params = array())
    {
        $request = new Horde_Oauth_Request($url, $params);
        $request->sign($this->_twitter->auth->oauth->signatureMethod,
                       $this->_twitter->auth->oauth,
                       $this->_twitter->auth->getAccessToken());
        $client = new Horde_Http_Client();
        try {
            $response = $client->get($url, array('Authorization' => $request->buildAuthorizationHeader('Twitter API')));
        } catch (Horde_Http_Client_Exception $e) {
            // Currently we can't obtain any information regarding the resposne
            // when a 4xx/5xx response is rec'd due to fopen() failing.
            // For now, fake it and return the error from the exception.
            return '{"request":"' . $url . '", "error:", "' . $e->getMessage() . '"}';
        }

        return $response->getBody();
    }

    public function post($url, $params = array())
    {
        $request = new Horde_Oauth_Request($url, $params);
        $request->sign($this->_twitter->auth->oauth->signatureMethod,
                       $this->_twitter->auth->oauth,
                       $this->_twitter->auth->getAccessToken());

        $client = new Horde_Http_Client();
        try {
            $response = $client->post($url, $params, array('Authorization' => $request->buildAuthorizationHeader('Twitter API')));
        } catch (Horde_Http_Client_Exception $e) {
            // Currently we can't obtain any information regarding the resposne
            // when a 4xx/5xx response is rec'd due to fopen() failing.
            // For now, fake it and return the error from the exception.
            return '{"request":"' . $url . '", "error:", "' . $e->getMessage() . '"}';
        }

        return $response->getBody();
    }

}
