<?php
/**
 * Horde_Service_Twitter class abstracts communication with Twitter's
 * rest interface.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter
{
    /* Constants */
    const REQUEST_TOKEN_URL = 'http://twitter.com/oauth/request_token';
    const USER_AUTHORIZE_URL = 'http://twitter.com/oauth/authorize';
    const ACCESS_TOKEN_URL = 'http://twitter.com/oauth/access_token';

    /**
     * Cache for the various objects we lazy load in __get()
     *
     * @var hash of Horde_Service_Twitter_* objects
     */
    protected $_objCache = array();

    /**
     * (Optional) Cache object
     *
     * @var Horde_Cache
     */
    protected $_responseCache;

    /**
     * Default cache lifetime.
     *
     * @var integer
     */
    protected $_cacheLifetime = 300;

    /**
     * Optional logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Can't lazy load the auth or request class since we need to know early if
     * we are OAuth or Basic
     *
     * @var Horde_Service_Twitter_Auth
     */
    protected $_auth;

    /**
     * The twitter request object.
     *
     * @var Horde_Service_Twitter_Request
     */
    protected $_request;

    /**
     * The http client.
     *
     * @var Horde_Http_Client
     */
     protected $_httpClient;

    /**
     * Const'r
     *
     * @param array $config  Configuration parameters:
     *   <pre>
     *     'oauth'    - Horde_Oauth object if using Oauth
     *     'username' - if using Basic auth
     *     'password' - if using Basic auth
     *   </pre>
     */
    public function __construct(Horde_Service_Twitter_Auth $auth, Horde_Service_Twitter_Request $request)
    {
        $this->_auth = $auth;
        $this->_auth->setTwitter($this);
        $this->_request = $request;
        $this->_request->setTwitter($this);
    }

    public function setCache(Horde_Cache $cache)
    {
        $this->_responseCache = $cache;
    }

    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Set the http client.
     *
     * @param Horde_Http_Client $client  The http client
     */
    public function setHttpClient(Horde_Http_Client $client)
    {
        $this->_httpClient = $client;
    }

    /**
     * Get the http client.
     *
     * @return Horde_Http_Client
     */
    public function getHttpClient()
    {
        return $this->_httpClient;
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
        case 'auth':
            return $this->_auth;
        case 'request':
            return $this->_request;
        case 'responseCache':
            return $this->_responseCache;
        case 'cacheLifetime':
            return $this->_cacheLifetime;
        }

        // If not, assume it's a method/action class...
        $class = 'Horde_Service_Twitter_' . ucfirst($value);
        if (!empty($this->_objCache[$class])) {
            return $this->_objCache[$class];
        }

        if (!class_exists($class)) {
            throw new Horde_Service_Twitter_Exception(sprintf("%s class not found", $class));
        }


        $this->_objCache[$class] = new $class($this);
        return $this->_objCache[$class];
    }

}
