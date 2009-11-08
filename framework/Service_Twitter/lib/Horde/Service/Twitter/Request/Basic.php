<?php
/**
 * Horde_Service_Twitter_Request_Basic class wraps sending requests to Twitter's
 * REST API using http basic authentication.
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
    /**
     *
     * @var Horde_Service_Twitter
     */
    protected $_twitter;

    /**
     * Const'r
     *
     * @param Horde_Service_Twitter $twitter
     */
    public function __construct($twitter)
    {
        $this->_twitter = $twitter;
    }

    /**
     * Perform a GET request.
     *
     * @param string $url  The URL for the request
     * @param array $params
     *
     * @return mixed The response
     */
    public function get($url, $params = array())
    {
        $key = md5($url . 'get' . serialize($params) . $this->_twitter->auth->username);
        $cache = $this->_twitter->responseCache;
        if (!empty($cache) && $results = $cache->get($key, $this->_twitter->cacheLifetime)) {
            return $results;
        }
        $client = new Horde_Http_Client();
        try {
            $response = $client->get($url, array('Authorization' => $this->_twitter->auth->buildAuthorizationHeader()));
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Service_Twitter_Exception($e);
        }

        $body = $response->getBody();
        if ($response->code >= 400 && $response->code <= 500) {
            throw new Horde_Service_Twitter_Exception($body);
        }
        if (!empty($cache)) {
            $cache->set($key, $body);
        }

        return $body;
    }

    /**
     * Perform a POST request
     *
     * @see self::get
     */
    public function post($url, $params = array())
    {
        $client = new Horde_Http_Client();
        try {
            $response = $client->post($url, $params, array('Authorization' => $this->_twitter->auth->buildAuthorizationHeader()));
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Service_Twitter_Exception($e);
        }

        if ($response->code >= 400 && $response->code <= 500) {
            throw new Horde_Service_Twitter_Exception($body);
        }
        return $response->getBody();
    }

}
