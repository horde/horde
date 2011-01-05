<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */

/**
 * OAuth consumer class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Oauth
 */
class Horde_Oauth_Consumer
{
    protected $_config;

    /**
     * Const'r for consumer.
     *
     * @param array $config  Configuration values:
     *  <pre>
     *    'key'               - Consumer key
     *    'secret'            - Consumer secret
     *    'requestTokenUrl'   - The request token URL
     *    'authorizeTokenUrl' - The authorize URL
     *    'accessTokenUrl'    = To obtain an access token
     *    'signatureMethod    - Horde_Oauth_SignatureMethod object
     *  </pre>
     *
     * @return Horde_Oauth_Consumer
     */
    public function __construct($config)
    {
        // Check for required config
        if (!is_array($config) || empty($config['key']) || empty($config['secret']) ||
            empty($config['requestTokenUrl']) || empty($config['authorizeTokenUrl']) ||
            empty($config['signatureMethod'])) {

            throw new InvalidArgumentException('Missing a required parameter in Horde_Oauth_Consumer::__construct');
        }
        $this->_config = $config;
    }

    public function __get($name)
    {
        return isset($this->_config[$name]) ? $this->_config[$name] : null;
    }

    /**
     * Obtain an unprivileged request token
     *
     * @param array $params  Parameter array
     *
     * @return Horde_Oauth_Token  The oauth request token
     */
    public function getRequestToken($params = array())
    {
        $params['oauth_consumer_key'] = $this->key;

        $request = new Horde_Oauth_Request($this->requestTokenUrl, $params);
        $request->sign($this->signatureMethod, $this);

        $client = new Horde_Http_Client;

        try {
            $response = $client->post(
                $this->requestTokenUrl,
                $request->buildHttpQuery()
            );
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Oauth_Exception($e->getMessage());
        }

        return Horde_Oauth_Token::fromString($response->getBody());
    }

    /**
     * Get the user authorization url used to request user authorization
     *
     * @param Horde_Oauth_Token $token  the oauth request token
     *
     * @return string The user authorization url string
     */
    public function getUserAuthorizationUrl($token)
    {
        return $this->authorizeTokenUrl . '?oauth_token=' . urlencode($token->key) . '&oauth_callback=' . urlencode($this->callbackUrl);
    }

    /**
     * Obtain an access token from a request token
     *
     * @param Horde_Oauth_Token $token Open auth token containing the oauth_token
     *                                 returned from provider after authorization
     *                                 and the token secret returned with the
     *                                 original request token.
     * @param array $params           Any additional parameters for this request
     *
     * @return unknown_type
     */
    public function getAccessToken($token, $params = array())
    {
        $params['oauth_consumer_key'] = $this->key;
        $params['oauth_token'] = $token->key;

        $request = new Horde_Oauth_Request($this->accessTokenUrl, $params);
        $request->sign($this->signatureMethod, $this, $token);

        $client = new Horde_Http_Client;
        try {
            $response = $client->post(
                $this->accessTokenUrl,
                $request->buildHttpQuery()
            );
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Oauth_Exception($e->getMessage());
        }

        return Horde_Oauth_Token::fromString($response->getBody());
    }
}
