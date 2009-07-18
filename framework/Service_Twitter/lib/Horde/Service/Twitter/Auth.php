<?php
/**
 * Horde_Service_Twitter_Auth class to abstract all auth related tasks
 *
 * Basically implements Horde_Oauth_Client and passes the calls along to the
 * protected oauth object.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Auth {

    /**
     *
     * @var Horde_Service_Twitter
     */
    protected $_twitter;

    /**
     *
     */
    protected $_token;

    /**
     * Const'r
     *
     * @return Horde_Service_Twitter_Auth
     */
    public function __construct($twitter, $oauth)
    {
        $this->_twitter = $twitter;
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
        return $this->_twitter->oauth->getUserAuthorizationUrl($requestToken);
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
    public function getAccessToken($request = null, $requestSecret = null)
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

        return $this->_twitter->oauth->getAccessToken($token);
    }

    public function getRequestToken($params = array())
    {
        return $this->_twitter->oauth->getRequestToken($params);
    }

}
