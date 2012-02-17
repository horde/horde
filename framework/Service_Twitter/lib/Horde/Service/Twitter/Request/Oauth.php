<?php
/**
 * Horde_Service_Twitter_Request_Oauth class wraps sending requests to Twitter's
 * REST API using OAuth authentication.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package Service_Twitter
 */
class Horde_Service_Twitter_Request_Oauth extends Horde_Service_Twitter_Request
{
    /**
     * Perform a GET request with OAuth authorization.
     *
     * @param mixed (string | Horde_Url) $url  The url to request.
     * @param array  $params                   URL parameters.
     *
     * @return string  Call results.
     * @throws Horde_Service_Twitter_Exception
     */
    public function get($url, array $params = array())
    {
        $key = md5($url . 'get' . serialize($params) . serialize($this->_twitter->auth->getAccessToken($this->_request)));
        $cache = $this->_twitter->responseCache;
        if (!empty($cache) && $results = $cache->get($key, $this->_twitter->cacheLifetime)) {
            return $results;
        }
        $request = new Horde_Oauth_Request($url, $params, 'GET');
        $request->sign($this->_twitter->auth->oauth->signatureMethod,
                       $this->_twitter->auth->oauth,
                       $this->_twitter->auth->getAccessToken($this->_request));
        $url = ($url instanceof Horde_Url) ? $url : new Horde_Url($url);
        $url->add($params);
        try {
            $response = $this->_twitter->getHttpClient()->get($url->setRaw(true), array('Authorization' => $request->buildAuthorizationHeader('Twitter API')));
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Service_Twitter_Exception($e);
        }

        // Looks like some of the http clients (like Fopen) will thrown an
        // exception if we try to read an empty stream. Ignore this.
        try {
            $body = $response->getBody();
            if ($response->code >= 400 && $response->code <= 500) {
                throw new Horde_Service_Twitter_Exception($body);
            }
        } catch (Horde_Http_Exception $e) {}

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
    public function post($url, array $params = array())
    {
        $request = new Horde_Oauth_Request($url, $params);
        $request->sign($this->_twitter->auth->oauth->signatureMethod,
                       $this->_twitter->auth->oauth,
                       $this->_twitter->auth->getAccessToken($this->_request));
        $url = ($url instanceof Horde_Url) ? $url : new Horde_Url($url);
        try {
            $response = $this->_twitter->getHttpClient()->post($url->setRaw(true), $params, array('Authorization' => $request->buildAuthorizationHeader('Twitter API')));
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Service_Twitter_Exception($e);
        }

        if ($response->code >= 400 && $response->code <= 500) {
            throw new Horde_Service_Twitter_Exception($response->getBody());
        }
        return $response->getBody();
    }

}
