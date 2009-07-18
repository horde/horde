<?php
/**
 * Horde_Service_Twitter class abstracts communication with Twitter's
 * rest interface.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter
{

    /**
     * Cache for the various objects we lazy load in __get()
     *
     * @var hash of Horde_Service_Twitter_* objects
     */
    protected $_objCache = array();

    protected $_config;

    /**
     * Const'r
     *
     * @param array $config  Configuration parameters:
     *   <pre>
     *     'oauth'  - Horde_Oauth object
     */
    public function __construct($config)
    {
        // TODO: Check for req'd config
        $this->_config = $config;

    }

    /**
     * Lazy load the twitter classes.
     *
     * @param string $value  The lowercase representation of the subclass.
     *
     * @throws Horde_Service_Twitter_Exception
     * @return Horde_Service_Twitter_* object.
     */
    public function __get($value)
    {
        // First, see if it's an allowed protected value.
        switch ($value) {
        case 'oauth':
            return $this->_config['oauth'];

        }

        // If not, assume it's a method/action class...
        $class = 'Horde_Service_Twitter_' . ucfirst($value);
        if (!empty($this->_objCache[$class])) {
            return $this->_objCache[$class];
        }

        if (!class_exists($class)) {
            throw new Horde_Service_Twitter_Exception(sprintf("%s class not found", $class));
        }


        $this->_objCache[$class] = new $class($this, $this->oauth);
        return $this->_objCache[$class];
    }

    /**
     * Send a request to the Twitter api
     *
     * @param $url
     * @param $params
     * @return unknown_type
     */
    public function getRequest($url, $params = array())
    {
        $request = new Horde_Oauth_Request($url, $params);
        $request->sign($this->oauth->signatureMethod, $this->oauth, $this->auth->getAccessToken());

        $client = new Horde_Http_Client();
        $response = $client->get($url, array('Authorization' => $request->buildAuthorizationHeader()));

        return $response->getBody();
    }

    public function postRequest($url, $params = array())
    {
        $request = new Horde_Oauth_Request($url, $params);
        $request->sign($this->oauth->signatureMethod, $this->oauth, $this->auth->getAccessToken());

        $client = new Horde_Http_Client();
        $response = $client->post($url, $params, array('Authorization' => $request->buildAuthorizationHeader()));

        return $response->getBody();
    }

}
