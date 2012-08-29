<?php
/**
 * Horde_Service_Facebook_Auth:: wrap functionality associated with
 * authenticating to Facebook.
 *
 * For now, only provide methods for authenticating that make sense from
 * within a Horde context.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Auth extends Horde_Service_Facebook_Base
{
    /**
     * Cache the current auth_token.
     *
     * @var string
     */
    protected $_sessionKey;

    /** User Data Perms **/
    const EXTEND_PERMS_USER_ABOUT = 'user_about_me';
    const EXTEND_PERMS_USER_BIRTHDAY = 'user_birthday';
    const EXTEND_PERMS_USER_EVENTS = 'user_events';
    const EXTEND_PERMS_USER_HOMETOWN = 'user_hometown';
    const EXTEND_PERMS_USER_LOCATION = 'user_location';
    const EXTEND_PERMS_USER_PHOTOS = 'user_photos';

    /** Friends Data **/
    const EXTEND_PERMS_FRIENDS_ABOUT = 'friends_about_me';
    const EXTEND_PERMS_FRIENDS_BIRTHDAY = 'friends_birthday';
    const EXTEND_PERMS_FRIENDS_HOMETOWN = 'friends_hometown';
    const EXTEND_PERMS_FRIENDS_LOCATION = 'friends_location';
    const EXTEND_PERMS_FRIENDS_PHOTOS = 'friends_photos';

    /** Misc **/
    const EXTEND_PERMS_PUBLISHSTREAM = 'publish_stream';
    const EXTEND_PERMS_READSTREAM = 'read_stream';

    /**
     * Get the URL for the user to authenticate the application and authorize
     * various extender permissions/
     *
     * @param string $callback  The callback url. FB will redirect back to here.
     * @param array $perms      An array of FB permissions to request.
     * @param string $state     A random, but unique string for FB to return
     *                          to ensure security.
     *
     * @return string  The URL.
     */
    public function getOAuthUrl($callback, array $perms = array(), $state = null)
    {
        return $this->_facebook->getFacebookUrl()
            . '/dialog/oauth?client_id=' . $this->_facebook->appId
            . '&redirect_uri=' . urlencode($callback)
            . '&scope=' . implode(',', $perms)
            . (!empty($state) ? '&state=' . $state : '');
    }

    /**
     * Returns the URL to obtain the auth_token from FB after getOAuthUrl
     * redirects back to your callback URL.
     *
     * @param string $code      The code returned by FB after the OAuth2 dialog
     * @param string $callback  The callback url. Required in order to
     *                          authenticate via OAuth2.
     *
     * @return string  The URL.
     */
    public function getAuthTokenUrl($code, $callback)
    {
        return $this->_facebook->getFacebookUrl('graph')
            . '/oauth/access_token?client_id=' . $this->_facebook->appId
            . '&redirect_uri=' . urlencode($callback) . '&client_secret=' . $this->_facebook->secret
            . '&code=' . $code;
    }

    /**
     * Obtain the current access_token. Either returns the currently set token
     * or, if a OAuth2 code is provided, sends a GET request to FB requesting
     * the access_token.
     *
     * @param string $code      The code returned from FB's OAuth dialog.
     * @param string $callback  If provided, used as the callback URL required
     *                          during the final steps in the OAuth2 process.
     *
     * @return string  The access_token
     * @throws Horde_Service_Facebook_Exception
     */
    public function getSessionKey($code = null, $callback = '')
    {
        if (!empty($code)) {
            try {
                $result = $this->_http->request(
                    'GET', $this->getAuthTokenUrl($code, $callback));
            } catch (Horde_Http_Exception $e) {
                throw new Horde_Service_Facebook_Exception($e);
            }

            if ($result->code !== 200) {
                throw new Horde_Service_Facebook_Exception('Unable to contact Facebook', $result->code);
            }
            parse_str($result->getBody(), $vars);
            $this->_sessionKey = $vars['access_token'];
        }

        return $this->_sessionKey;
    }

    /**
     * Sets an existing access_token for this session.
     *
     * @param string $sessionKey  The FB OAuth2 access_token
     */
    public function setSession($sessionKey)
    {
        $this->_sessionKey = $sessionKey;
    }

    /**
     * Revoke a previously authorizied extended permission
     *
     * @param string $perm  The extended permission to remove.
     *
     * @return unknown_type
     */
    public function revokeExtendedPermission($perm)
    {
        // Session key is *required*
        if (!$skey = $this->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callGraphApi(
            'me/permissions',
            array('permission' => $perm),
            array('request' => 'DELETE')
        );
    }

    /**
     * Returns the user corresponding to the current session object.
     *
     * @throws Horde_Service_Facebook_Exception
     * @return string User id
     */
    public function getLoggedInUser()
    {
        if (empty($this->_sessionKey)) {
            throw new Horde_Service_Facebook_Exception(
                'users.getLoggedInUser requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $results = $this->_facebook->callGraphApi('me');
        return $results->id;
    }

}
