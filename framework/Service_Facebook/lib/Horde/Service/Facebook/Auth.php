<?php
/**
 * Horde_Service_Facebook_Auth:: wrap functionality associated with
 * authenticating to Facebook.
 *
 * For now, only provide methods for authenticating that make sense from
 * within a Horde context.
 *
 * Note, we don't extend Base since we are a special case in that not all
 * the info is set until *after* we are authenticated.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Auth
{
    /**
     *
     * @var Horde_Service_Facebook
     */
    protected $_facebook;

    /**
     *
     * @var string
     */
    protected $_base_domain;

    /**
     * @var string
     */
    protected $_sessionKey;

    /**
     * The current userid
     *
     * @var string
     */
    protected $_user;

    /**
     *
     * @var Horde_Controller_Request
     */
    protected $_request;

    /** EXTEND_PERMS constants **/
    const EXTEND_PERMS_OFFLINE = 'offline_access';

    // These perms are now wrapped up in the publish_stream permission, but are
    // left here for BC and to allow atomic setting of the perms if desired.
    const EXTEND_PERMS_STATUSUPDATE = 'status_update';
    const EXTEND_PERMS_SHAREITEM = 'share_item';
    const EXTEND_PERMS_UPLOADPHOTO = 'photo_upload';

    const EXTEND_PERMS_PUBLISHSTREAM = 'publish_stream';
    const EXTEND_PERMS_READSTREAM = 'read_stream';


    /**
     * Const'r
     *
     * @param Horde_Service_Facebook         $facebook
     * @param Horde_Service_Facebook_Request $request
     * @param array $params
     */
    public function __construct($facebook, $request, $params = array())
    {
        $this->_facebook = $facebook;
        $this->_request = $request;
    }

    /**
     *  Return a valid FB login URL with necessary GET parameters appended.
     *
     * @param string $next  URL to return to
     *
     * @return string  The Facebook Login Url
     */
    public function getLoginUrl($next)
    {
        return Horde_Service_Facebook::getFacebookUrl() . '/login.php?v=1.0&api_key='
            . $this->_facebook->apiKey . ($next ? '&next=' . urlencode($next)  : '');
    }

    /**
     * Returns the URL for a user to obtain the auth token needed to generate an
     * infinite session for a web app.
     *
     * http://www.sitepoint.com/article/developing-facebook-platform/
     * http://forum.developers.facebook.com/viewtopic.php?id=20223
     *
     * This has been replaced by having the user grant "offline access"
     * to the application using extended permissions.
     *
     * @return string
     */
    public function getAuthTokenUrl()
    {
        return $this->_facebook->getFacebookUrl() . '/code_gen.php?v=1.0&api_key='
            . $this->_facebook->apiKey;
    }

    /**
     * Return the URL needed for approving an extendted permission.
     *
     * @param string $perm         An EXTEND_PERMS_* constant
     * @param string $success_url  URL to redirect to on success
     * @param string $cancel_url   URL to redirect to on cancel
     *
     * @return string
     */
    public function getExtendedPermUrl($perm, $success_url = '', $cancel_url = '')
    {
        return $this->_facebook->getFacebookUrl() . '/authorize.php?v=1'
            . '&ext_perm=' . $perm . '&api_key=' . $this->_facebook->apiKey
            . (!empty($success_url) ? '&next=' . urlencode($success_url) : '')
            . (!empty($cancel_url) ? '&cancel=' . urlencode($cancel_url) : '');
    }

    /**
     * Getter
     *
     * @return string  The current session key
     */
    public function getSessionKey()
    {
        return $this->_sessionKey;
    }

    /**
     * Getter
     *
     * @return string  The current userid
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Returns the session information available after current user logs in.
     *
     * @param string $auth_token             the token
     *
     * @return array  An assoc array containing session_key, uid
     */
    public function getSession($auth_token)
    {
        try {
            $results = $this->_facebook->callMethod(
                'facebook.auth.getSession',
                array('auth_token' => $auth_token));
            return $results;
        } catch (Horde_Service_Facebook_Exception $e) {
            if ($e->getCode() != Horde_Service_Facebook_ErrorCodes::API_EC_PARAM) {
                // API_EC_PARAM means we don't have a logged in user, otherwise who
                // knows what it means, so just throw it.
                throw $e;
            }
        }
    }

    /**
     * Creates an authentication token to be used as part of the desktop login
     * flow.  For more information, please see
     * http://wiki.developers.facebook.com/index.php/Auth.createToken.
     *
     * @return string  An authentication token.
     */
    public function createToken()
    {
        return $this->_facebook->callMethod('facebook.auth.createToken');
    }

    /**
     * Expires the session that is currently being used.  If this call is
     * successful, no further calls to the API (which require a session) can be
     * made until a valid session is created.
     *
     * @return bool  true if session expiration was successful, false otherwise
     */
    private function _expireSession()
    {
        // Requires a session
        if (empty($this->_sessionKey)) {
            throw new Horde_Service_Facebook_Exception(
            'No Session', Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callMethod('facebook.auth.expireSession', array('session_key' => $this->_sessionKey));
    }

    /**
     * Invalidate the session currently being used, and clear any state
     * associated with it.
     */
    public function expireSession()
    {
        if ($this->_expireSession()) {
            $cookies = $this->_request->getCookieVars();
            if ($cookies[$this->_facebook->apiKey . '_user']) {
                $cookies = array('user', 'session_key', 'expires', 'ss');
                foreach ($cookies as $name) {
                    setcookie($this->_facebook->apiKey . '_' . $name, false, time() - 3600);
                }
                setcookie($this->_facebook->apiKey, false, time() - 3600);
            }

            // now, clear the rest of the stored state
            $this->setUser(0, 0, time() - 3600);
            return true;
        } else {

            return false;
        }
    }

    /**
     * Attempt to create or obtain Facebook session parameters from data sent
     * to us by Facebook. This will come from $_POST, $_GET, or $_COOKIE,
     * in that order. $_POST and $_GET are always more up-to-date than cookies,
     * so we prefer those if they are available. Data obtained from Facebook
     * is always validated by checking the signature.
     *
     * For nitty-gritty details of when each of these is used, check out
     * http://wiki.developers.facebook.com/index.php/Verifying_The_Signature
     *
     * @param boolean $ignore_cookies      Ignore any seemingly valid session
     *                                     session data obtained from a cookie.
     *                                     (Needed to allow us to overwrite a
     *                                      stale value when we get a new token)
     * @param boolean $resolve_auth_token  Call auth.getSession if we have an
     *                                     auth_token and no other parameters?
     *
     * @return boolean
     */
    public function validateSession($ignore_cookies = false, $resolve_auth_token = true)
    {
        // Prefer $_POST data - but if absent, try $_GET and $_POST with
        // 'fb_post_sig' since that might be returned by FQL queries.
        $post = $this->_request->getPostVars();
        $get = $this->_request->getGetVars();

        // Parse the values
        $fb_params = $this->_getParams($post, 48 * 3600, 'fb_sig');
        if (!$fb_params) {
            $fb_params = $this->_getParams($get, 48 * 3600, 'fb_sig');
            $fb_post_params = $this->_getParams($post, 48 * 3600, 'fb_post_sig');
            $fb_params = array_merge($fb_params, $fb_post_params);
        }

        if ($fb_params) {
            // If we have valid params, set up the session.
            $user = isset($fb_params['user']) ? $fb_params['user'] : null;
            $this->_base_domain  = isset($fb_params['base_domain']) ? $fb_params['base_domain'] : null;
            if (isset($fb_params['session_key'])) {
                $sessionKey = $fb_params['session_key'];
            } elseif (isset($fb_params['profile_session_key'])) {
                $sessionKey = $fb_params['profile_session_key'];
            } else {
                $sessionKey = null;
            }
            $expires = isset($fb_params['expires']) ? $fb_params['expires'] : null;
            $this->setUser($user, $sessionKey, $expires);

        } elseif (!$ignore_cookies &&
                  $fb_params = $this->_getParams($this->_request->getCookieVars(), null, $this->_facebook->apiKey)) {

            $cookies = $this->_request->getCookieVars();
            // Nothing yet, try cookies...this is where we will get our values
            // for an extenral web app accessing FB's API - assuming the session
            // has already been retrieved previously.
            $base_domain_cookie = 'base_domain_' . $this->_facebook->apiKey;
            if ($cookies[$base_domain_cookie]) {
                $this->_base_domain = $cookie[$base_domain_cookie];
            }
            // use $api_key . '_' as a prefix for the cookies in case there are
            // multiple facebook clients on the same domain.
            $expires = isset($fb_params['expires']) ? $fb_params['expires'] : null;
            $this->setUser($fb_params['user'], $fb_params['session_key'], $expires);

        } elseif ($resolve_auth_token && isset($get['auth_token']) &&
                  $session = $this->getSession($get['auth_token'])) {

            if (isset($session['base_domain'])) {
                $this->_base_domain = $session['base_domain'];
            }

            $this->setUser($session['uid'],
                            $session['session_key'],
                            $session['expires']);

            return true;
        }

        return !empty($fb_params);
    }

    /**
     * Get the signed parameters that were sent from Facebook. Validates the set
     * of parameters against the included signature.
     *
     * Since Facebook sends data to your callback URL via unsecured means, the
     * signature is the only way to make sure that the data actually came from
     * Facebook. So if an app receives a request at the callback URL, it should
     * always verify the signature that comes with against your own secret key.
     * Otherwise, it's possible for someone to spoof a request by
     * pretending to be someone else, i.e.:
     *      www.your-callback-url.com/?fb_user=10101
     *
     * This is done automatically by verify_fb_params.
     *
     * @param array $params       A hash of all external parameters.
     * @param int $timeout        Number of seconds that the args are good for.
     * @param string $namespace   Prefix string for the set of parameters we
     *                            want to verify(fb_sig or fb_post_sig).
     *
     * @return array  The subset of parameters containing the given prefix,
     *                and also matching the signature associated with them or an
     *                empty array if the signature did not match.
     */
    protected function _getParams($params, $timeout = null, $namespace = 'fb_sig')
    {
        $prefix = $namespace . '_';
        $prefix_len = strlen($prefix);
        $fb_params = array();
        if (empty($params)) {
            return array();
        }

        foreach ($params as $name => $val) {
            // pull out only those parameters that match the prefix
            // note that the signature itself ($params[$namespace]) is not in the list
            if (strpos($name, $prefix) === 0) {
                $fb_params[substr($name, $prefix_len)] = $val;
            }
        }

        // validate that the request hasn't expired. this is most likely
        // for params that come from $_COOKIE
        if ($timeout && (!isset($fb_params['time']) || time() - $fb_params['time'] > $timeout)) {
          return array();
        }

        // validate that the params match the signature
        $signature = isset($params[$namespace]) ? $params[$namespace] : null;
        if (!$signature || (!$this->validateSignature($fb_params, $signature))) {
            return array();
        }

        return $fb_params;
    }

    /**
     * Validates that a given set of parameters match their signature.
     * Parameters all match a given input prefix, such as "fb_sig".
     *
     * @param array  $fb_params     An array of all Facebook-sent parameters, not
     *                              including the signature itself.
     * @param string $expected_sig  The expected result to check against.
     *
     * @return boolean
     */
    public function validateSignature($fb_params, $expected_sig)
    {
        return self::generateSignature($fb_params, $this->_facebook->secret) == $expected_sig;
    }

    /**
     * Generate a signature using the application secret key.
     *
     * The only two entities that know your secret key are you and Facebook,
     * according to the Terms of Service. Since nobody else can generate
     * the signature, you can rely on it to verify that the information
     * came from Facebook.
     *
     * @param array $params   An array of all Facebook-sent parameters, NOT
     *                        INCLUDING the signature itself.
     * @param string $secret  The application's secret key.
     *
     * @return string  Hash to be checked against the FB provided signature.
     */
    public static function generateSignature($params, $secret)
    {
        $str = '';
        ksort($params);
        foreach ($params as $k => $v) {
            $str .= "$k=$v";
        }
        $str .= $secret;

        return md5($str);
    }
    /**
     * Set session cookies.
     *
     * @param string $user        FB userid
     * @param string $sessionKey  The current session key
     * @param timestamp $expires
     *
     * @return void
     */
    public function setCookies($user, $sessionKey, $expires = null)
    {
        $cookies = array();
        $cookies['user'] = $user;
        $cookies['session_key'] = $sessionKey;
        if ($expires != null) {
            $cookies['expires'] = $expires;
        }
        foreach ($cookies as $name => $val) {
            setcookie($this->_facebook->apiKey . '_' . $name, $val, (int)$expires, '', $this->_base_domain);
        }
        $sig = self::generateSignature($cookies, $this->_facebook->secret);
        setcookie($this->_facebook->apiKey, $sig, (int)$expires, '', $this->_base_domain);
        if ($this->_base_domain != null) {
            $base_domain_cookie = 'base_domain_' . $this->_facebook->apiKey;
            setcookie($base_domain_cookie, $this->_base_domain, (int)$expires, '', $this->_base_domain);
        }
    }

    /**
     * Set the current session user in the object and in a cookie.
     *
     * @param string $user        The FB userid
     * @param string $sessionKey  The current sessionkey
     * @param timestamp $expires  Expire time
     * @param boolean $noCookie   If true, do not set a user cookie.
     *
     * @return void
     */
    public function setUser($user, $sessionKey, $expires = null, $noCookie = false)
    {
        $cookies = $this->_request->getCookieVars();
        if (!$noCookie && (empty($cookies[$this->_facebook->apiKey . '_user']) ||
                           $cookies[$this->_facebook->apiKey . '_user'] != $user)) {
            $this->setCookies($user, $sessionKey, $expires);
        }
        $this->_user = $user;
        $this->_sessionKey = $sessionKey;
        $this->_session_expires = $expires;
    }

    /**
     * Revoke a previously authorizied extended permission
     *
     * @param string $perm  The extended permission to remove.
     * @param string $uid   The FB userid to remove permission from
     *
     * @return unknown_type
     */
    public function revokeExtendedPermission($perm, $uid)
    {
        // Session key is *required*
        if (!$skey = $this->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callMethod('Auth.revokeExtendedPermission',
                                            array('session_key' => $skey,
                                                  'perm' => $perm,
                                                  'user' => $uid));

    }

    /**
     * Revoke all application permissions for the current session user.
     *
     */
    function revokeAuthorization()
    {
        // Session key is *required*
        if (!$skey = $this->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        $this->_facebook->callMethod('Auth.revokeAuthorization',
                                            array('session_key' => $skey));
        $this->expireSession();

    }

    /**
     * Returns the user corresponding to the current session object.
     *
     * @throws Horde_Service_Facebook_Exception
     * @return integer  User id
     */
    public function &getLoggedInUser()
    {
        if (!$this->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.getLoggedInUser requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->callMethod('facebook.users.getLoggedInUser',
            array('session_key' => $this->getSessionKey()));
    }

}
