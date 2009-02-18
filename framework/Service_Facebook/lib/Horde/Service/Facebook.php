<?php
/**
 * Horde_Service_Facebook class abstracts communication with Facebook's
 * rest interface.
 *
 * Code is basically a refactored version of Facebook's
 * facebookapi_php5_restclient.php library, completely ripped apart and put
 * back together in a Horde friendly way.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */

/**
 * Facebook Platform PHP5 client
 *
 * Copyright 2004-2009 Facebook. All Rights Reserved.
 *
 * Copyright (c) 2007 Facebook, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * For help with this library, contact developers-help@facebook.com
 */

class Horde_Service_Facebook
{
    /**
     * The application's API Key
     *
     * @var stirng
     */
    public $api_key;

    /**
     * The API Secret Key
     *
     * @var string
     */
    public $secret;

    // Used since we are emulating a FB Desktop Application - since we are not
    // being used within the context of a FB Canvas.
    protected  $_app_secret;

    // Flag used internally to help with authentication
    protected $_verify_sig = false;

    // Store the current session_key
    public $session_key;

    // Session expiry
    protected $_session_expires;

    // All parameters passed back to us from FB
    public $fb_params;

    // The current session user
    public $user;

    // Use only ssl resource flag
    public $use_ssl_resources = false;
    private $_batchRequest;

    protected $_base_domain;
    protected $_call_as_apikey;


    /**
     *
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     *
     * @var Horde_Controller_Request_Http
     */
    protected $_request;

    /**
     *
     * @var array
     */
    protected $_context;

    const API_VALIDATION_ERROR = 1;
    const REST_SERVER_ADDR = 'http://api.facebook.com/restserver.php';

    /**
     * Const'r
     *
     * @param string $api_key  Developer API key.
     * @param string $secret   Developer API secret.
     * @param array $context   Array of context information containing:
     *  <pre>
     *      http_client - required
     *      http_response - required
     *      login_redirect_callback - optional
     *
     * @param session_key
     */
    public function __construct($api_key, $secret, $context)
    {
        // We require a http client object
        if (empty($context['http_client'])) {
            throw new InvalidArgumentException('A http client object is required');
        } else {
            $this->_http = $context['http_client'];
        }

        // Horde_Controller_Request object
        if (empty($context['http_request'])) {
            throw new InvalidArgumentException('A http request object is required');
        } else {
            $this->_request = $context['http_request'];
        }

        $this->api_key = $api_key;
        $this->secret = $secret;
        $this->_app_secret = $secret;
        $this->validate_fb_params();
        $this->_call_as_apikey = '';

        // Set the default user id for methods that allow the caller to
        // pass an explicit uid instead of using a session key.
        $this->user = !empty($this->user) ? $this->user : null;

        if (!empty($context['use_ssl'])) {
            $this->useSslResources = true;
        }

        // Save the rest
        $this->_context = $context;
    }

    /**
     * Validates that the parameters passed in were sent from Facebook. It does so
     * by validating that the signature matches one that could only be generated
     * by using your application's secret key.
     *
     * Facebook-provided parameters will come from $_POST, $_GET, or $_COOKIE,
     * in that order. $_POST and $_GET are always more up-to-date than cookies,
     * so we prefer those if they are available.
     *
     * For nitty-gritty details of when each of these is used, check out
     * http://wiki.developers.facebook.com/index.php/Verifying_The_Signature
     *
     * @param bool  resolve_auth_token  convert an auth token into a session
     */
    public function validate_fb_params($resolve_auth_token = true)
    {
        // Prefer $_POST data - but if absent, try $_GET and $_POST with
        // 'fb_post_sig' since that might be returned by FQL queries.
        $post = $this->_request->getPostParams();
        $get = $this->_request->getGetParams();

        $this->fb_params = $this->get_valid_fb_params($post, 48 * 3600, 'fb_sig');
        if (!$this->fb_params) {
            $fb_params = $this->get_valid_fb_params($get, 48 * 3600, 'fb_sig');
            $fb_post_params = $this->get_valid_fb_params($post, 48 * 3600, 'fb_post_sig');
            $this->fb_params = array_merge($fb_params, $fb_post_params);
        }

        if ($this->fb_params) {
            $user = isset($this->fb_params['user']) ? $this->fb_params['user'] : null;
            $this->_base_domain  = isset($this->fb_params['base_domain']) ? $this->fb_params['base_domain'] : null;
            if (isset($this->fb_params['session_key'])) {
                $session_key = $this->fb_params['session_key'];
            } elseif (isset($this->fb_params['profile_session_key'])) {
                $session_key = $this->fb_params['profile_session_key'];
            } else {
                $session_key = null;
            }
            $expires = isset($this->fb_params['expires']) ? $this->fb_params['expires'] : null;
            $this->set_user($user, $session_key, $expires);
        } elseif ($cookies = $this->get_valid_fb_params($this->_request->getCookie(), null, $this->api_key)) {
            $base_domain_cookie = 'base_domain_' . $this->api_key;
            if ($this->_request->getCookie($base_domain_cookie)) {
                $this->_base_domain = $this->_request->getCookie($base_domain_cookie);
            }
            // use $api_key . '_' as a prefix for the cookies in case there are
            // multiple facebook clients on the same domain.
            $expires = isset($cookies['expires']) ? $cookies['expires'] : null;
            $this->set_user($cookies['user'], $cookies['session_key'], $expires);
        } elseif ($resolve_auth_token && isset($get['auth_token']) &&
                  $session = $this->do_get_session($get['auth_token'])) {

            if (isset($session['base_domain'])) {
                $this->_base_domain = $session['base_domain'];
            }

            $this->set_user($session['uid'],
                            $session['session_key'],
                            $session['expires']);
        }

        return !empty($this->fb_params);
    }

    /**
     * Return the session information
     *
     * @param $auth_token
     * @return unknown_type
     */
    protected function _do_get_session($auth_token)
    {
        try {
            return $this->auth_getSession($auth_token);
        } catch (Horde_Service_Facebook_Exception $e) {
            // API_EC_PARAM means we don't have a logged in user, otherwise who
            // knows what it means, so just throw it.
            if ($e->getCode() != Horde_Service_Facebook_ErrorCodes::API_EC_PARAM) {
                throw $e;
            }
        }
    }

    /**
     * Get authenticated session information for a "desktop" app.
     *
     * @param $auth_token
     * @return unknown_type
     */
    public function do_get_session($auth_token)
    {
        $this->secret = $this->_app_secret;
        $this->session_key = null;
        $session_info = $this->_do_get_session($auth_token);
        if (!empty($session_info['secret'])) {
            // store the session secret
            $this->set_session_secret($session_info['secret']);
        }

        return $session_info;
    }

    public function set_session_secret($session_secret)
    {
        $this->secret = $session_secret;
    }

    /**
     * Invalidate the session currently being used, and clear any state
     * associated with it.
     *
     * TODO: This calls setcookie() so need to ensure we aren't called after
     * headers are sent or throw an exception.
     */
    public function expire_session()
    {
        if ($this->auth_expireSession()) {
            if (!$this->in_fb_canvas() && $this->_request->getCookie($this->api_key . '_user')) {
                $cookies = array('user', 'session_key', 'expires', 'ss');
                foreach ($cookies as $name) {
                    setcookie($this->api_key . '_' . $name, false, time() - 3600);
                }
                setcookie($this->api_key, false, time() - 3600);
            }

            // now, clear the rest of the stored state
            $this->user = 0;
            $this->session_key = 0;

            return true;
        } else {

            return false;
        }
    }

    /**
     * Either redirect to the FB login page, or call a callback function to let
     * the client code handle the redirect.
     *
     * @param string $url  The URL to redirect to.
     *
     * @return void
     */
    protected function _redirect($url)
    {
        // If we have a callback, call it then return.
        if (!empty($this->_context['login_redirect_callback'])) {
            call_user_func($this->_context['login_redirect_callback'], $url);
            return;
        }

        if (preg_match('/^https?:\/\/([^\/]*\.)?facebook\.com(:\d+)?/i', $url)) {
            // make sure facebook.com url's load in the full frame so that we don't
            // get a frame within a frame.
            echo "<script type=\"text/javascript\">\ntop.location.href = \"$url\";\n</script>";
        } else {
            header('Location: ' . $url);
        }
        exit;
    }

    /**
     * Getter for session user.
     *
     * @return string  The FB userid
     */
    public function get_loggedin_user()
    {
        return $this->user;
    }

    /**
     * Return the current request's url
     *
     * @return string
     */
    protected function _current_url()
    {
        return sprintf("%s/%s", $this->_request->getHost(), $this->_request->getUri());
    }

    /**
     * Ensure the user is logged in and has a current FB session. If not,
     * attempt to redirect to a FB login page.
     *
     * @return void
     */
    public function require_login()
    {
        if ($this->get_loggedin_user()) {
            try {
                // try a session-based API call to ensure that we have the correct
                // session secret
                $user = $this->users_getLoggedInUser();

                // now that we have a valid session secret, verify the signature
                $this->_verify_sig = true;
                if ($this->validate_fb_params(false)) {
                    return $user;
                } else {
                    // validation failed
                    return null;
                }
            } catch (Horde_Service_Facebook_Exception $ex) {
                $get = $this->_request->getGetParams();
                if (isset($get['auth_token'])) {
                    // if we have an auth_token, use it to establish a session
                    $session_info = $this->do_get_session($get['auth_token']);
                    if ($session_info) {
                      return $session_info['uid'];
                    }
                }
            }
        }

        // if we get here, we need to redirect the user to log in
        $this->_redirect($this->_get_login_url(self::_current_url()));
    }

    /**
     * Helper function to get the appropriate facebook url
     *
     * @return string
     */
    protected static function _get_facebook_url($subdomain = 'www')
    {
        return 'http://' . $subdomain . '.facebook.com';
    }

    /**
     *  Return a valid FB login URL with necessary GET parameters appended.
     *
     *  @return string
     */
    protected function _get_login_url($next)
    {
        return self::_get_facebook_url() . '/login.php?v=1.0&api_key='
            . $this->api_key . ($next ? '&next=' . urlencode($next)  : '');
    }

    /**
     * Set the current session user
     *
     * @param string $user  The FB userid
     * @param string $session_key
     * @param timestamp $expires
     *
     * @return void
     */
    public function set_user($user, $session_key, $expires = null)
    {
        if (!$this->_request->getCookie($this->api_key . '_user') ||
            $this->_request->getCookie($this->api_key . '_user') != $user) {

            $this->set_cookies($user, $session_key, $expires);
        }
        $this->user = $user;
        $this->session_key = $session_key;
        $this->_session_expires = $expires;
    }

    /**
     * Set session cookies (Facebook *requires* cookies to be enabled).
     *
     * @param string $user  FB userid
     * @param string $session_key  The current session key
     * @param timestamp $expires
     *
     * @return void
     */
    public function set_cookies($user, $session_key, $expires = null)
    {
        $cookies = array();
        $cookies['user'] = $user;
        $cookies['session_key'] = $session_key;
        if ($expires != null) {
            $cookies['expires'] = $expires;
        }
        foreach ($cookies as $name => $val) {
            setcookie($this->api_key . '_' . $name, $val, (int)$expires, '', $this->_base_domain);
        }
        $sig = self::generate_sig($cookies, $this->secret);
        setcookie($this->api_key, $sig, (int)$expires, '', $this->_base_domain);
        if ($this->_base_domain != null) {
            $base_domain_cookie = 'base_domain_' . $this->api_key;
            setcookie($base_domain_cookie, $this->_base_domain, (int)$expires, '', $this->_base_domain);
        }
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
    public function get_valid_fb_params($params, $timeout = null, $namespace = 'fb_sig')
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
        if (!$signature || (!$this->_verify_signature($fb_params, $signature))) {
            return array();
        }

        return $fb_params;
    }

    /**
     * Validates that a given set of parameters match their signature.
     * Parameters all match a given input prefix, such as "fb_sig".
     *
     * @param array $fb_params  An array of all Facebook-sent parameters, not
     *                          including the signature itself.
     * @param $expected_sig     The expected result to check against.
     *
     * @return boolean
     */
    protected function _verify_signature($fb_params, $expected_sig)
    {
        // we don't want to verify the signature until we have a valid
        // session secret
        if ($this->_verify_sig) {
            return self::generate_sig($fb_params, $this->secret) == $expected_sig;
        } else {
            return true;
        }
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
    public static function generate_sig($params_array, $secret)
    {
        $str = '';
        ksort($params_array);
        foreach ($params_array as $k => $v) {
            $str .= "$k=$v";
        }
        $str .= $secret;

        return md5($str);
    }

    /**
     * Start a batch operation.
     */
    public function begin_batch()
    {
        if ($this->_batchRequest !== null) {
            $code = Horde_Service_Facebook_ErrorCodes::API_EC_BATCH_ALREADY_STARTED;
            $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
            throw new Horde_Service_Facebook_Exception($description, $code);
        }

        $this->_batchRequest = new Horde_Service_Facebook_BatchRequest($this, $this->_http);
    }

    /**
     * End current batch operation
     */
    public function end_batch()
    {
        if ($this->_batchRequest === null) {
            $code = Horde_Service_Facebook_ErrorCodes::API_EC_BATCH_NOT_STARTED;
            $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
            throw new Horde_Service_Facebook_Exception($description, $code);
        }

        $this->_batchRequest->run();
        $this->_batchRequest = null;
    }

    /**
     * Creates an authentication token to be used as part of the desktop login
     * flow.  For more information, please see
     * http://wiki.developers.facebook.com/index.php/Auth.createToken.
     *
     * @return string  An authentication token.
     */
    public function auth_createToken()
    {
        return $this->call_method('facebook.auth.createToken');
    }

    /**
     * Returns the session information available after current user logs in.
     *
     * @param string $auth_token             the token returned by
     *                                       auth_createToken or passed back to
     *                                       your callback_url.
     *
     * @return array  An assoc array containing session_key, uid
     */
    public function auth_getSession($auth_token)
    {
        //Check if we are in batch mode
        if ($this->_batchRequest === null) {
            $result = $this->call_method('facebook.auth.getSession', array('auth_token' => $auth_token));
            $this->session_key = $result['session_key'];
            if (!empty($result['secret'])) {
                // desktop apps have a special secret
                $this->secret = $result['secret'];
            }
            return $result;
        }
    }

    /**
     * Expires the session that is currently being used.  If this call is
     * successful, no further calls to the API (which require a session) can be
     * made until a valid session is created.
     *
     * @return bool  true if session expiration was successful, false otherwise
     */
    public function auth_expireSession()
    {
        return $this->call_method('facebook.auth.expireSession');
    }

    /**
     * Returns events according to the filters specified.
     *
     * @param int $uid            (Optional) User associated with events. A null
     *                            parameter will default to the session user.
     * @param string $eids        (Optional) Filter by these comma-separated event
     *                            ids. A null parameter will get all events for
     *                            the user.
     * @param int $start_time     (Optional) Filter with this unix time as lower
     *                            bound.  A null or zero parameter indicates no
     *                            lower bound.
     * @param int $end_time       (Optional) Filter with this UTC as upper bound.
     *                            A null or zero parameter indicates no upper
     *                            bound.
     * @param string $rsvp_status (Optional) Only show events where the given uid
     *                            has this rsvp status.  This only works if you
     *                            have specified a value for $uid.  Values are as
     *                            in events.getMembers.  Null indicates to ignore
     *                            rsvp status when filtering.
     *
     * @return array  The events matching the query.
     */
    public function &events_get($uid=null, $eids=null, $start_time=null,
                                $end_time=null, $rsvp_status=null)
    {
        // Note we return a reference to support batched calls
        //  (see self::call_method)
        return $this->call_method('facebook.events.get',
            array('uid' => $uid,
                  'eids' => $eids,
                  'start_time' => $start_time,
                  'end_time' => $end_time,
                  'rsvp_status' => $rsvp_status));
    }

    /**
     * Returns membership list data associated with an event.
     *
     * @param int $eid  event id
     *
     * @return array  An assoc array of four membership lists, with keys
     *                'attending', 'unsure', 'declined', and 'not_replied'
     */
    public function &events_getMembers($eid)
    {
        return $this->call_method('facebook.events.getMembers', array('eid' => $eid));
    }

    /**
     * RSVPs the current user to this event.
     *
     * @param int $eid             event id
     * @param string $rsvp_status  'attending', 'unsure', or 'declined'
     *
     * @return bool  true if successful
     */
    public function &events_rsvp($eid, $rsvp_status)
    {
        return $this->call_method('facebook.events.rsvp',
            array('eid' => $eid,
                  'rsvp_status' => $rsvp_status));
    }


    /**
     * Cancels an event. Only works for events where application is the admin.
     *
     * @param int $eid                event id
     * @param string $cancel_message  (Optional) message to send to members of
     *                                the event about why it is cancelled
     *
     * @return bool  true if successful
     */
    public function &events_cancel($eid, $cancel_message='')
    {
        return $this->call_method('facebook.events.cancel',
            array('eid' => $eid,
                  'cancel_message' => $cancel_message));
    }

    /**
     * Creates an event on behalf of the user is there is a session, otherwise on
     * behalf of app.  Successful creation guarantees app will be admin.
     *
     * @param assoc array $event_info  json encoded event information
     *
     * @return int  event id
     */
    public function &events_create($event_info)
    {
        return $this->call_method('facebook.events.create', array('event_info' => $event_info));
    }

    /**
     * Edits an existing event. Only works for events where application is admin.
     *
     * @param int $eid                 event id
     * @param assoc array $event_info  json encoded event information
     *
     * @return bool  true if successful
     */
    public function &events_edit($eid, $event_info)
    {
        return $this->call_method('facebook.events.edit',
            array('eid' => $eid,
                  'event_info' => $event_info));
    }

    /**
     * Makes an FQL query.  This is a generalized way of accessing all the data
     * in the API, as an alternative to most of the other method calls.  More
     * info at http://developers.facebook.com/documentation.php?v=1.0&doc=fql
     *
     * @param string $query  the query to evaluate
     *
     * @return array  generalized array representing the results
     */
    public function &fql_query($query)
    {
        return $this->call_method('facebook.fql.query', array('query' => $query));
    }


    /**
     * Returns whether or not pairs of users are friends.
     * Note that the Facebook friend relationship is symmetric.
     *
     * @param string $uids1  comma-separated list of ids (id_1, id_2,...)
     *                       of some length X
     * @param string $uids2  comma-separated list of ids (id_A, id_B,...)
     *                       of SAME length X
     *
     * @return array  An array with uid1, uid2, and bool if friends, e.g.:
     *   array(0 => array('uid1' => id_1, 'uid2' => id_A, 'are_friends' => 1),
     *         1 => array('uid1' => id_2, 'uid2' => id_B, 'are_friends' => 0)
     *         ...)
     * @error
     *    API_EC_PARAM_USER_ID_LIST
     */
    public function &friends_areFriends($uids1, $uids2)
    {
        return $this->call_method('facebook.friends.areFriends',
            array('uids1' => $uids1, 'uids2' => $uids2));
    }

    /**
     * Returns the friends of the current session user.
     *
     * @param int $flid  (Optional) Only return friends on this friend list.
     * @param int $uid   (Optional) Return friends for this user.
     *
     * @return array  An array of friends
     */
    public function &friends_get($flid=null, $uid = null)
    {
        $params = array();
        if ($uid) {
          $params['uid'] = $uid;
        }
        if ($flid) {
          $params['flid'] = $flid;
        }

        return $this->call_method('facebook.friends.get', $params);
    }

    /**
     * Returns the set of friend lists for the current session user.
     *
     * @return array  An array of friend list objects
     */
    public function &friends_getLists()
    {
        return $this->call_method('facebook.friends.getLists');
    }

    /**
     * Returns groups according to the filters specified.
     *
     * @param int $uid     (Optional) User associated with groups.  A null
     *                     parameter will default to the session user.
     * @param string $gids (Optional) Comma-separated group ids to query. A null
     *                     parameter will get all groups for the user.
     *
     * @return array  An array of group objects
     */
    public function &groups_get($uid, $gids)
    {
        return $this->call_method('facebook.groups.get',
            array('uid' => $uid, 'gids' => $gids));
    }

    /**
     * Returns the membership list of a group.
     *
     * @param int $gid  Group id
     *
     * @return array  An array with four membership lists, with keys 'members',
     *                'admins', 'officers', and 'not_replied'
     */
    public function &groups_getMembers($gid)
    {
        return $this->call_method('facebook.groups.getMembers', array('gid' => $gid));
    }

    /**
     * Returns cookies according to the filters specified.
     *
     * @param int $uid     User for which the cookies are needed.
     * @param string $name (Optional) A null parameter will get all cookies
     *                     for the user.
     *
     * @return array  Cookies!  Nom nom nom nom nom.
     */
    public function data_getCookies($uid, $name)
    {
        return $this->call_method('facebook.data.getCookies',
            array('uid' => $uid, 'name' => $name));
    }

    /**
     * Sets cookies according to the params specified.
     *
     * @param int $uid       User for which the cookies are needed.
     * @param string $name   Name of the cookie
     * @param string $value  (Optional) if expires specified and is in the past
     * @param int $expires   (Optional) Expiry time
     * @param string $path   (Optional) Url path to associate with (default is /)
     *
     * @return bool  true on success
     */
    public function data_setCookie($uid, $name, $value, $expires, $path)
    {
        return $this->call_method('facebook.data.setCookie',
            array('uid' => $uid,
                  'name' => $name,
                  'value' => $value,
                  'expires' => $expires,
                  'path' => $path));
    }

    /**
     * Retrieves links posted by the given user.
     *
     * @param int    $uid      The user whose links you wish to retrieve
     * @param int    $limit    The maximimum number of links to retrieve
     * @param array $link_ids (Optional) Array of specific link
     *                          IDs to retrieve by this user
     *
     * @return array  An array of links.
     */
    public function &links_get($uid, $limit, $link_ids = null)
    {
        return $this->call_method('links.get',
            array('uid' => $uid,
                  'limit' => $limit,
                  'link_ids' => json_encode($link_ids)));
    }

    /**
     * Posts a link on Facebook.
     *
     * @param string $url     URL/link you wish to post
     * @param string $comment (Optional) A comment about this link
     * @param int    $uid     (Optional) User ID that is posting this link;
     *                        defaults to current session user
     *
     * @return bool
     */
    public function &links_post($url, $comment='', $uid = null)
    {
        return $this->call_method('links.post',
            array('uid' => $uid,
                  'url' => $url,
                  'comment' => $comment));
    }


    /**
     * Creates a note with the specified title and content.
     *
     * @param string $title   Title of the note.
     * @param string $content Content of the note.
     * @param int    $uid     (Optional) The user for whom you are creating a
     *                        note; defaults to current session user
     *
     * @return int   The ID of the note that was just created.
     */
    public function &notes_create($title, $content, $uid = null)
    {
        return $this->call_method('notes.create',
            array('uid' => $uid,
                  'title' => $title,
                  'content' => $content));
    }

    /**
     * Deletes the specified note.
     *
     * @param int $note_id  ID of the note you wish to delete
     * @param int $uid      (Optional) Owner of the note you wish to delete;
     *                      defaults to current session user
     *
     * @return bool
     */
    public function &notes_delete($note_id, $uid = null)
    {
        return $this->call_method('notes.delete',
            array('uid' => $uid,
                  'note_id' => $note_id));
    }

    /**
    * Edits a note, replacing its title and contents with the title
    * and contents specified.
    *
    * @param int    $note_id  ID of the note you wish to edit
    * @param string $title    Replacement title for the note
    * @param string $content  Replacement content for the note
    * @param int    $uid      (Optional) Owner of the note you wish to edit;
    *                         defaults to current session user
    *
    * @return bool
    */
    public function &notes_edit($note_id, $title, $content, $uid = null) {
        return $this->call_method('notes.edit',
            array('uid' => $uid,
                  'note_id' => $note_id,
                  'title' => $title,
                  'content' => $content));
    }

    /**
     * Retrieves all notes by a user. If note_ids are specified,
     * retrieves only those specific notes by that user.
     *
     * @param int    $uid      User whose notes you wish to retrieve
     * @param array  $note_ids (Optional) List of specific note
     *                         IDs by this user to retrieve
     *
     * @return array A list of all of the given user's notes, or an empty list
     *               if the viewer lacks permissions or if there are no visible
     *               notes.
     */
    public function &notes_get($uid, $note_ids = null)
    {
        return $this->call_method('notes.get',
            array('uid' => $uid,
                  'note_ids' => json_encode($note_ids)));
    }

    /**
     * Returns the outstanding notifications for the session user.
     *
     * @return array An assoc array of notification count objects for
     *               'messages', 'pokes' and 'shares', a uid list of
     *               'friend_requests', a gid list of 'group_invites',
     *               and an eid list of 'event_invites'
     */
    public function &notifications_get()
    {
        return $this->call_method('facebook.notifications.get');
    }

    /**
     * Sends a notification to the specified users.
     *
     * @return A comma separated list of successful recipients
     * @error
     *    API_EC_PARAM_USER_ID_LIST
     */
    public function &notifications_send($to_ids, $notification, $type)
    {
        return $this->call_method('facebook.notifications.send',
            array('to_ids' => $to_ids,
                  'notification' => $notification,
                  'type' => $type));
    }

    /**
     * Sends an email to the specified user of the application.
     *
     * @param string $recipients comma-separated ids of the recipients
     * @param string $subject    subject of the email
     * @param string $text       (plain text) body of the email
     * @param string $fbml       fbml markup for an html version of the email
     *
     * @return string  A comma separated list of successful recipients
     * @error
     *    API_EC_PARAM_USER_ID_LIST
     */
    public function &notifications_sendEmail($recipients, $subject, $text, $fbml)
    {
        return $this->call_method('facebook.notifications.sendEmail',
            array('recipients' => $recipients,
                  'subject' => $subject,
                  'text' => $text,
                  'fbml' => $fbml));
    }

    /**
     * Adds a tag with the given information to a photo. See the wiki for details:
     *
     *  http://wiki.developers.facebook.com/index.php/Photos.addTag
     *
     * @param int $pid          The ID of the photo to be tagged
     * @param int $tag_uid      The ID of the user being tagged. You must specify
     *                          either the $tag_uid or the $tag_text parameter
     *                          (unless $tags is specified).
     * @param string $tag_text  Some text identifying the person being tagged.
     *                          You must specify either the $tag_uid or $tag_text
     *                          parameter (unless $tags is specified).
     * @param float $x          The horizontal position of the tag, as a
     *                          percentage from 0 to 100, from the left of the
     *                          photo.
     * @param float $y          The vertical position of the tag, as a percentage
     *                          from 0 to 100, from the top of the photo.
     * @param array $tags       (Optional) An array of maps, where each map
     *                          can contain the tag_uid, tag_text, x, and y
     *                          parameters defined above.  If specified, the
     *                          individual arguments are ignored.
     * @param int $owner_uid    (Optional)  The user ID of the user whose photo
     *                          you are tagging. If this parameter is not
     *                          specified, then it defaults to the session user.
     *
     * @return bool  true on success
     */
    public function &photos_addTag($pid, $tag_uid, $tag_text, $x, $y, $tags, $owner_uid = 0)
    {
        return $this->call_method('facebook.photos.addTag',
            array('pid' => $pid,
                  'tag_uid' => $tag_uid,
                  'tag_text' => $tag_text,
                  'x' => $x,
                  'y' => $y,
                  'tags' => (is_array($tags)) ? json_encode($tags) : null,
                  'owner_uid' => $this->get_uid($owner_uid)));
    }

    /**
     * Creates and returns a new album owned by the specified user or the current
     * session user.
     *
     * @param string $name         The name of the album.
     * @param string $description  (Optional) A description of the album.
     * @param string $location     (Optional) A description of the location.
     * @param string $visible      (Optional) A privacy setting for the album.
     *                             One of 'friends', 'friends-of-friends',
     *                             'networks', or 'everyone'.  Default 'everyone'.
     * @param int $uid             (Optional) User id for creating the album; if
     *                             not specified, the session user is used.
     *
     * @return array  An album object
     */
    public function &photos_createAlbum($name, $description='', $location='', $visible='', $uid=0)
    {
        return $this->call_method('facebook.photos.createAlbum',
            array('name' => $name,
                  'description' => $description,
                'location' => $location,
                'visible' => $visible,
                'uid' => $this->get_uid($uid)));
    }

    /**
     * Returns photos according to the filters specified.
     *
     * @param int $subj_id  (Optional) Filter by uid of user tagged in the photos.
     * @param int $aid      (Optional) Filter by an album, as returned by
     *                      photos_getAlbums.
     * @param string $pids   (Optional) Restrict to a comma-separated list of pids
     *
     * Note that at least one of these parameters needs to be specified, or an
     * error is returned.
     *
     * @return array  An array of photo objects.
     */
    public function &photos_get($subj_id, $aid, $pids)
    {
        return $this->call_method('facebook.photos.get',
            array('subj_id' => $subj_id, 'aid' => $aid, 'pids' => $pids));
    }

    /**
     * Returns the albums created by the given user.
     *
     * @param int $uid      (Optional) The uid of the user whose albums you want.
     *                       A null will return the albums of the session user.
     * @param string $aids  (Optional) A comma-separated list of aids to restricti
     *                       the query.
     *
     * Note that at least one of the (uid, aids) parameters must be specified.
     *
     * @returns an array of album objects.
     */
    public function &photos_getAlbums($uid, $aids)
    {
        return $this->call_method('facebook.photos.getAlbums',
            array('uid' => $uid,
                  'aids' => $aids));
    }

    /**
     * Returns the tags on all photos specified.
     *
     * @param string $pids  A list of pids to query
     *
     * @return array  An array of photo tag objects, which include pid,
     *                subject uid, and two floating-point numbers (xcoord, ycoord)
     *                for tag pixel location.
     */
    public function &photos_getTags($pids)
    {
        return $this->call_method('facebook.photos.getTags', array('pids' => $pids));
    }

    /**
     * Uploads a photo.
     *
     * @param string $file     The location of the photo on the local filesystem.
     * @param int $aid         (Optional) The album into which to upload the
     *                         photo.
     * @param string $caption  (Optional) A caption for the photo.
     * @param int uid          (Optional) The user ID of the user whose photo you
     *                         are uploading
     *
     * @return array  An array of user objects
     */
    public function photos_upload($file, $aid = null, $caption = null, $uid = null)
    {
        return $this->call_upload_method('facebook.photos.upload',
                                     array('aid' => $aid,
                                           'caption' => $caption,
                                           'uid' => $uid),
                                     $file);
    }


    /**
     * Uploads a video.
     *
     * @param  string $file        The location of the video on the local filesystem.
     * @param  string $title       (Optional) A title for the video. Titles over 65 characters in length will be truncated.
     * @param  string $description (Optional) A description for the video.
     *
     * @return array  An array with the video's ID, title, description, and a link to view it on Facebook.
     */
    public function video_upload($file, $title = null, $description = null)
    {
        return $this->call_upload_method('facebook.video.upload',
            array('title' => $title,
                  'description' => $description),
            $file, self::_get_facebook_url('api-video') . '/restserver.php');
    }

    /**
     * Returns an array with the video limitations imposed on the current session's
     * associated user. Maximum length is measured in seconds; maximum size is
     * measured in bytes.
     *
     * @return array  Array with "length" and "size" keys
     */
    public function &video_getUploadLimits()
    {
        return $this->call_method('facebook.video.getUploadLimits');
    }

    /**
     * Returns the requested info fields for the requested set of users.
     *
     * @param string $uids    A comma-separated list of user ids
     * @param string $fields  A comma-separated list of info field names desired
     *
     * @return array  An array of user objects
     */
    public function &users_getInfo($uids, $fields)
    {
        return $this->call_method('facebook.users.getInfo',
            array('uids' => $uids, 'fields' => $fields));
    }

    /**
     * Returns the requested info fields for the requested set of users. A
     * session key must not be specified. Only data about users that have
     * authorized your application will be returned.
     *
     * Check the wiki for fields that can be queried through this API call.
     * Data returned from here should not be used for rendering to application
     * users, use users.getInfo instead, so that proper privacy rules will be
     * applied.
     *
     * @param string $uids    A comma-separated list of user ids
     * @param string $fields  A comma-separated list of info field names desired
     *
     * @return array  An array of user objects
     */
    public function &users_getStandardInfo($uids, $fields)
    {
        return $this->call_method('facebook.users.getStandardInfo',
            array('uids' => $uids, 'fields' => $fields));
    }

    /**
    * Returns the user corresponding to the current session object.
    *
    * @return integer  User id
    */
    public function &users_getLoggedInUser()
    {
     return $this->call_method('facebook.users.getLoggedInUser');
    }

    /**
     * Returns 1 if the user has the specified permission, 0 otherwise.
     * http://wiki.developers.facebook.com/index.php/Users.hasAppPermission
     *
     * @return integer  1 or 0
     */
    public function &users_hasAppPermission($ext_perm, $uid = null)
    {
        return $this->call_method('facebook.users.hasAppPermission',
            array('ext_perm' => $ext_perm, 'uid' => $uid));
    }

    /**
     * Returns whether or not the user corresponding to the current
     * session object has the give the app basic authorization.
     *
     * @return boolean  true if the user has authorized the app
     */
    public function &users_isAppUser($uid=null)
    {
        if ($uid === null && isset($this->is_user)) {
            return $this->is_user;
        }

        return $this->call_method('facebook.users.isAppUser', array('uid' => $uid));
    }

    /**
    * Returns whether or not the user corresponding to the current
    * session object is verified by Facebook. See the documentation
    * for Users.isVerified for details.
    *
    * @return boolean  true if the user is verified
    */
    public function &users_isVerified()
    {
    return $this->call_method('facebook.users.isVerified');
    }

  /**
   * Sets the users' current status message. Message does NOT contain the
   * word "is" , so make sure to include a verb.
   *
   * Example: setStatus("is loving the API!")
   * will produce the status "Luke is loving the API!"
   *
   * @param string $status                text-only message to set
   * @param int    $uid                   user to set for (defaults to the
   *                                      logged-in user)
   * @param bool   $clear                 whether or not to clear the status,
   *                                      instead of setting it
   * @param bool   $status_includes_verb  if true, the word "is" will *not* be
   *                                      prepended to the status message
   *
   * @return boolean
   */
  public function &users_setStatus($status,
                                   $uid = null,
                                   $clear = false,
                                   $status_includes_verb = true)
 {
    $args = array(
      'status' => $status,
      'uid' => $uid,
      'clear' => $clear,
      'status_includes_verb' => $status_includes_verb,
    );
    return $this->call_method('facebook.users.setStatus', $args);
  }

    /**
     * Calls the specified normal POST method with the specified parameters.
     *
     * @param string $method  Name of the Facebook method to invoke
     * @param array $params   A map of param names => param values
     *
     * @return mixed  Result of method call; this returns a reference to support
     *                'delayed returns' when in a batch context.
     *     See: http://wiki.developers.facebook.com/index.php/Using_batching_API
     */
    public function &call_method($method, $params = array())
    {
        if ($this->_batchRequest === null) {
            $request = new Horde_Service_Facebook_Request($this, $method, $this->_http, $params);
            $results = &$request->run();
        } else {
            $results = &$this->_batchRequest->add($method, $params);
        }
        return $results;
    }

    /**
     * Calls the specified file-upload POST method with the specified parameters
     *
     * @param string $method Name of the Facebook method to invoke
     * @param array  $params A map of param names => param values
     * @param string $file   A path to the file to upload (required)
     *
     * @return array A dictionary representing the response.
     */
    public function call_upload_method($method, $params, $file, $server_addr = null)
    {
        if ($this->batch_queue === null) {
            if (!file_exists($file)) {
                $code = Horde_Service_Facebook_ErrorCodes::API_EC_PARAM;
                $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
                throw new Horde_Service_Facebook_Exception($description, $code);
            }
        }

        $json = $this->post_upload_request($method, $params, $file, $server_addr);
        $result = json_decode($json, true);
        if (is_array($result) && isset($result['error_code'])) {
            throw new Horde_Service_Facebook_Exception($result['error_msg'], $result['error_code']);
        } else {
            $code = Horde_Service_Facebook_ErrorCodes::API_EC_BATCH_METHOD_NOT_ALLOWED_IN_BATCH_MODE;
            $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
            throw new Horde_Service_Facebook_Exception($description, $code);
        }

        return $result;
    }

    private function post_upload_request($method, $params, $file, $server_addr = null)
    {
        // Ensure we ask for JSON
        $params['format'] = 'json';
        $server_addr = $server_addr ? $server_addr : self::REST_SERVER_ADDR;
        $this->finalize_params($method, $params);
        $result = $this->run_multipart_http_transaction($method, $params, $file, $server_addr);
        return $result;
    }

    private function run_http_post_transaction($content_type, $content, $server_addr)
    {
        $user_agent = 'Facebook API PHP5 Client 1.1 (non-curl) ' . phpversion();
        $content_length = strlen($content);
        $context =
            array('http' => array('method' => 'POST',
                                  'user_agent' => $user_agent,
                                  'header' => 'Content-Type: ' . $content_type . "\r\n" . 'Content-Length: ' . $content_length,
                                  'content' => $content));
        $context_id = stream_context_create($context);
        $sock = fopen($server_addr, 'r', false, $context_id);
        $result = '';
        if ($sock) {
          while (!feof($sock)) {
            $result .= fgets($sock, 4096);
          }
          fclose($sock);
        }
        return $result;
    }

    /**
     * TODO: This will probably be replace
     * @param $method
     * @param $params
     * @param $file
     * @param $server_addr
     * @return unknown_type
     */
    private function run_multipart_http_transaction($method, $params, $file, $server_addr)
    {
        // the format of this message is specified in RFC1867/RFC1341.
        // we add twenty pseudo-random digits to the end of the boundary string.
        $boundary = '--------------------------FbMuLtIpArT' .
                    sprintf("%010d", mt_rand()) .
                    sprintf("%010d", mt_rand());
        $content_type = 'multipart/form-data; boundary=' . $boundary;
        // within the message, we prepend two extra hyphens.
        $delimiter = '--' . $boundary;
        $close_delimiter = $delimiter . '--';
        $content_lines = array();
        foreach ($params as $key => &$val) {
            $content_lines[] = $delimiter;
            $content_lines[] = 'Content-Disposition: form-data; name="' . $key . '"';
            $content_lines[] = '';
            $content_lines[] = $val;
        }
        // now add the file data
        $content_lines[] = $delimiter;
        $content_lines[] = 'Content-Disposition: form-data; filename="' . $file . '"';
        $content_lines[] = 'Content-Type: application/octet-stream';
        $content_lines[] = '';
        $content_lines[] = file_get_contents($file);
        $content_lines[] = $close_delimiter;
        $content_lines[] = '';
        $content = implode("\r\n", $content_lines);
        return $this->run_http_post_transaction($content_type, $content, $server_addr);
    }

}