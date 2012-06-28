<?php
/**
 * Horde_Service_Twitter class abstracts communication with Twitter's
 * rest interface.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @property-read Horde_Service_Twitter_Account $account
 *                The twitter account object for calling account methods.
 * @property-read Horde_Service_Twitter_Statuses $statuses
 *                The twitter status object for updating and retrieving user
 *                statuses.
 * @property-read Horde_Service_Twitter_Auth $auth
 *                The twitter authentication object.
 * @property-read Horde_Service_Twitter_Request $request
 *                The twitter request object that wraps sending requests to
 *                Twitter's REST API.
 * @property-read Horde_Cache $responseCache
 *                The cache object.
 * @property-read integer $cacheLifetime
 *                The default cache lifetime.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package Service_Twitter
 */
class Horde_Service_Twitter
{
    /* Constants */
    const REQUEST_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';
    const USER_AUTHORIZE_URL = 'https://api.twitter.com/oauth/authorize';
    const ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/access_token';

    /**
     * Cache for the various objects we lazy load in __get()
     *
     * @var hash of Horde_Service_Twitter_* objects
     */
    protected $_objCache = array();

    /**
     * (Optional) Cache object.
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
     * Constructor.
     *
     * @param Horde_Service_Twitter_Auth $auth        An authentication object
     * @param Horde_Service_Twitter_Request $request  A request object.
     */
    public function __construct(Horde_Service_Twitter_Auth $auth,
                                Horde_Service_Twitter_Request $request)
    {
        $this->_auth = $auth;
        $this->_auth->setTwitter($this);
        $this->_request = $request;
        $this->_request->setTwitter($this);
    }

    /**
     * Factory method to easily build a working Twitter client object.
     *
     * @param array $params  Configuration parameters, with the following keys:
     *                       - 'oauth' (required):
     *                       - 'consumer_key' (required): The application's
     *                         consumer key
     *                       - 'consumer_secret' (required): The application's
     *                         consumer secret
     *                       - 'access_token' (optional): The user's access
     *                         token
     *                       - 'access_token_secret' (optional): The user's
     *                         access token secret.
     *                       - 'http' (optional): any configuration parameters
     *                         for Horde_Http_Client, e.g. proxy settings.
     *
     * @return Horde_Service_Twitter  A twitter object that can be used
     *                                immediately to update and receive
     *                                statuses etc.
     */
    static public function create($params)
    {
        if (!isset($params['oauth'])) {
            throw new Horde_Service_Twitter_Exception('Only OAuth authentication is supported.');
        }

        /* Parameters required for the Horde_Oauth_Consumer */
        $consumer_params = array(
            'key' => $params['oauth']['consumer_key'],
            'secret' => $params['oauth']['consumer_secret'],
            'requestTokenUrl' => self::REQUEST_TOKEN_URL,
            'authorizeTokenUrl' => self::USER_AUTHORIZE_URL,
            'accessTokenUrl' => self::ACCESS_TOKEN_URL,
            'signatureMethod' => new Horde_Oauth_SignatureMethod_HmacSha1());

        /* Create the Consumer */
        $oauth = new Horde_Oauth_Consumer($consumer_params);

        /* Create the Twitter client */
        $twitter = new Horde_Service_Twitter(
            new Horde_Service_Twitter_Auth_Oauth($oauth),
            new Horde_Service_Twitter_Request_Oauth(
                new Horde_Controller_Request_Http()));

        /* Create HTTP client. */
        $http_params = isset($params['http']) ? $params['http'] : array();
        $twitter->setHttpClient(new Horde_Http_Client($http_params));

        /* Check for an existing token */
        if (!empty($params['oauth']['access_token']) &&
            !empty($params['oauth']['access_token_secret'])) {
            $auth_token = new Horde_Oauth_Token(
                $params['oauth']['access_token'],
                $params['oauth']['access_token_secret']);
            $twitter->auth->setToken($auth_token);
        }

        return $twitter;
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
