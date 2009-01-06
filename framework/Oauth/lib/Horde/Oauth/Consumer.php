<?php
/**
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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

    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function __get($name)
    {
        return isset($this->_config[$name]) ? $this->_config[$name] : null;
    }

    public function getRequestToken($params = array())
    {
        $params['oauth_consumer_key'] = $this->key;

        $request = new Horde_Oauth_Request($this->requestTokenUrl, $params);
        $request->sign($this->signatureMethod, $this);

        $client = new Horde_Http_Client;
        $response = $client->post(
            $this->requestTokenUrl,
            $request->buildHttpQuery()
        );
        return Horde_Oauth_Token::fromString($response->getBody());
    }

    public function getUserAuthorizationUrl($token)
    {
        return $this->authorizeTokenUrl . '?oauth_token=' . urlencode($token->key) . '&oauth_callback=' . urlencode($this->callbackUrl);
    }

    public function getAccessToken($token, $params = array())
    {
        $params['oauth_consumer_key'] = $this->key;
        $params['oauth_token'] = $token->key;

        $request = new Horde_Oauth_Request($this->accessTokenUrl, $params);
        $request->sign($this->signatureMethod, $this, $token);

        $client = new Horde_Http_Client;
        $response = $client->post(
            $this->accessTokenUrl,
            $request->buildHttpQuery()
        );
        return Horde_Oauth_Token::fromString($response->getBody());
    }

}
