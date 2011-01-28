<?php
/**
 * Users methods for Horde_Service_Facebook
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Users extends Horde_Service_Facebook_Base
{
    /**
     * Returns the requested info fields for the requested set of users.
     *
     * @param string $uids    A comma-separated list of user ids
     * @param string $fields  A comma-separated list of info field names desired
     *
     * @return array  An array of user objects
     */
    public function &getInfo($uids, $fields)
    {
        return $this->_facebook->callMethod('facebook.users.getInfo',
            array('uids' => $uids,
                  'fields' => $fields,
                  'session_key' => $this->_sessionKey));
    }

    /**
     * Returns the requested info fields for the requested set of users. A
     * session key must not be specified. Only data about users that have
     * authorized your application will be returned.
     *
     * Check the wiki for fields that can be queried through this API call.
     * Data returned from here should *not* be used for rendering to application
     * users, use users.getInfo instead, so that proper privacy rules will be
     * applied.
     *
     * @param string $uids    A comma-separated list of user ids
     * @param string $fields  A comma-separated list of info field names desired
     *
     * @return array  An array of user objects
     */
    public function &getStandardInfo($uids, $fields)
    {
        return $this->_facebook->callMethod('facebook.users.getStandardInfo',
            array('uids' => $uids, 'fields' => $fields));
    }

    /**
     * Returns 1 if the user has the specified permission, 0 otherwise.
     * http://wiki.developers.facebook.com/index.php/Users.hasAppPermission
     *
     * @param string $ext_perm  The perm to check for.
     * @param string $uid       The facebook userid to check.
     *
     * @return integer  1 or 0
     * @throws Horde_Service_Facebook_Exception
     */
    public function &hasAppPermission($ext_perm, $uid = null)
    {
        if (empty($uid) && !$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.hasAppPermission requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('ext_perm' => $ext_perm);
        if (empty($uid)) {
            $params['session_key'] = $this->_facebook->auth->getSessionKey();
        } else {
            $params['uid'] = $uid;
        }

        return $this->_facebook->callMethod('facebook.users.hasAppPermission', $params);
    }

    /**
     * Returns whether or not the user corresponding to the current
     * session object has the give the app basic authorization.
     *
     * @param string $uid  Facebook userid
     *
     * @throws Horde_Service_Facebook_Exception
     * @return boolean  true if the user has authorized the app
     */
    public function &isAppUser($uid = null)
    {
        if (empty($uid) && !!$this->_facebook->auth->getSessionKey()) {
            $params = array('session_key' => $this->_facebook->auth->getSessionKey());
        } elseif (!empty($uid)) {
            $params = array('uid' => $uid);
        } else {
            throw new Horde_Service_Facebook_Exception('users.isAppUser requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->callMethod('facebook.users.isAppUser', $params);
    }

    /**
     * Returns whether or not the user corresponding to the current
     * session object is verified by Facebook. See the documentation
     * for Users.isVerified for details.
     *
     * @throws Horde_Service_Facebook_Exception
     * @return boolean  true if the user is verified
     */
    public function &isVerified()
    {
       if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.isVerified requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->callMethod('facebook.users.isVerified', array('session_key' => $this->_facebook->auth->getSessionKey()));
    }

    /**
     * Sets the users' current status message. Message does NOT contain the
     * word "is" , so make sure to include a verb.
     *
     * Example: setStatus("is loving the API!")
     * will produce the status "Luke is loving the API!"
     *
     * @param string  $status      text-only message to set
     * @param string  $uid         user to set for (defaults to the
     *                             logged-in user)
     * @param boolean $clear       whether or not to clear the status, instead
     *                             of setting it
     * @param boolean $includeVerb If true, the word "is" will *not* be
     *                             prepended to the status message
     *
     * @return boolean
     */
    public function &setStatus($status, $uid = null, $clear = false, $includeVerb = true)
    {
        if (empty($uid) && !$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.setStatus requires a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }
        $params  = array('status' => $status,
                         'clear' => $clear,
                         'status_includes_verb' => $includeVerb);

        if (!empty($uid)) {
            $params['uid'] = $uid;
        } else {
            $params['session_key']  = $skey;
        }

        return $this->_facebook->callMethod('facebook.users.setStatus', $params);
    }

    /**
     * Get user's status
     *
     * @param string  $uid
     * @param integer $limit
     *
     * @return mixed
     */
    public function &getStatus($uid = null, $limit = 1)
    {
        if (empty($uid) && !$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.setStatus requires a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('session_key' => $skey, 'limit' => $limit);
        if (!empty($user)) {
            $params['uid'] = $user;
        }

        return $this->_facebook->callMethod('Status.get', $params);
    }

}