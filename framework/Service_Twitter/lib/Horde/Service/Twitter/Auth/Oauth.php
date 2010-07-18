<?php
/**
 * Horde_Service_Twitter_Auth class to abstract all auth related tasks
 *
 * Basically implements Horde_Oauth_Client and passes the calls along to the
 * protected oauth object.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Auth_Oauth extends Horde_Service_Twitter_Auth
{
    /**
     *
     */
    protected $_token;

    public function __construct(Horde_OAuth_Consumer $oauth)
    {
        $this->_config['oauth'] = $oauth;
    }

    /**
     * Obtain the URL used to get an authorization token.
     *
     * @param Horde_Oauth_Token $requestToken The request token
     *
     * @return string  The Url
     */
    public function getUserAuthorizationUrl($requestToken)
    {
        return $this->oauth->getUserAuthorizationUrl($requestToken);
    }

    /**
     * Set the access token
     *
     * @param $token
     * @return unknown_type
     */
    public function setToken($token)
    {
        // @TODO: sanity check this
        $this->_token = $token;
    }

    /**
     * Obtain the access token. This is the token that should be persisted to
     * storage.
     *
     * @param Horde_Controller_Request_Http     Http request object
     * @param Horde_Oauth_Token $requestSecret  The token secret returned by
     *                                          Twitter after the user authorizes
     *                                          the application.
     * @return Horde_Oauth_Token
     */
    public function getAccessToken(Horde_Controller_Request_Http $request, $requestSecret = null)
    {
        if (!empty($this->_token)) {
            return $this->_token;
        }

        //@TODO: Verify the existence of requestSecret...

        $params = $request->getGetParams();
        if (empty($params['oauth_token'])) {
            return false;
        }
        $token = new Horde_Oauth_Token($params['oauth_token'], $requestSecret);
        try {
            return $this->oauth->getAccessToken($token);
        } catch (Horde_Oauth_Exception $e) {
            throw new Horde_Service_Twitter_Exception($e->getMessage());
        }
    }

    public function getRequestToken($params = array())
    {
        try {
            return $this->oauth->getRequestToken($params);
        } catch (Horde_Oauth_Exception $e) {
            throw new Horde_Service_Twitter_Exception($e->getMessage());
        }
    }

}
