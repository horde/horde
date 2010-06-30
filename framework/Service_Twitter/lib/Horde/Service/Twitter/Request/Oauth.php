<?php
/**
 * Horde_Service_Twitter_Request_Oauth class wraps sending requests to Twitter's
 * REST API using OAuth authentication.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Request_Oauth extends Horde_Service_Twitter_Request
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
     * Perform a GET request with OAuth authorization.
     *
     * @param string $url
     * @param array  $params
     *
     * @return mixed  Call results.
     */
    public function get($url, $params = array())
    {
        $key = md5($url . 'get' . serialize($params) . serialize($this->_twitter->auth->getAccessToken()));
        $cache = $this->_twitter->responseCache;
        if (!empty($cache) && $results = $cache->get($key, $this->_twitter->cacheLifetime)) {
            return $results;
        }
        $request = new Horde_Oauth_Request($url, $params, 'GET');
        $request->sign($this->_twitter->auth->oauth->signatureMethod,
                       $this->_twitter->auth->oauth,
                       $this->_twitter->auth->getAccessToken());
        $client = new Horde_Http_Client();
        try {
            $response = $client->get($url, array('Authorization' => $request->buildAuthorizationHeader('Twitter API')));
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
     * Send a POST request to the twitter API. Purposely do not cache results
     * from these since POST requests alter data on the server.
     *
     * @see self::get
     */
    public function post($url, $params = array())
    {
        $request = new Horde_Oauth_Request($url, $params);
        $request->sign($this->_twitter->auth->oauth->signatureMethod,
                       $this->_twitter->auth->oauth,
                       $this->_twitter->auth->getAccessToken());

        $client = new Horde_Http_Client();
        try {
            $response = $client->post($url, $params, array('Authorization' => $request->buildAuthorizationHeader('Twitter API')));
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Service_Twitter_Exception($e);
        }

        if ($response->code >= 400 && $response->code <= 500) {
            throw new Horde_Service_Twitter_Exception($response->getBody());
        }
        return $response->getBody();
    }

}
