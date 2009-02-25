<?php
/**
 * Users methods for Horde_Service_Facebook
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
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
        return $this->_facebook->call_method('facebook.users.getInfo',
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
     * Data returned from here should not be used for rendering to application
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
        return $this->_facebook->call_method('facebook.users.getStandardInfo',
            array('uids' => $uids, 'fields' => $fields));
    }

    /**
    * Returns the user corresponding to the current session object.
    *
    * @throws Horde_Service_Facebook_Exception
    * @return integer  User id
    */
    public function &getLoggedInUser()
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.getLoggedInUser requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->call_method('facebook.users.getLoggedInUser',
            array('session_key' => $this->_facebook->auth->getSessionKey()));
    }

    /**
     * Returns 1 if the user has the specified permission, 0 otherwise.
     * http://wiki.developers.facebook.com/index.php/Users.hasAppPermission
     *
     * @throws Horde_Service_Facebook_Exception
     * @return integer  1 or 0
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

        return $this->_facebook->call_method('facebook.users.hasAppPermission', $params);
    }

    /**
     * Returns whether or not the user corresponding to the current
     * session object has the give the app basic authorization.
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

        return $this->_facebook->call_method('facebook.users.isAppUser', $params);
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

        return $this->call_method('facebook.users.isVerified', array('session_key' => $this->_facebook->auth->getSessionKey()));
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
    public function &users_setStatus($status, $uid = null, $clear = false, $includeVerb = true)
    {
        if (empty($uid) && !$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.setStatus requires a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }
        $params  = array('status' => $status,
                         'clear' => $clear,
                         'status_includes_verb' => $includeVerb);

        if (empty($uid)) {
            $params['uid'] = $uid;
        } else {
            $params['session_key']  = $this->_facebook->auth->getSessionKey();
        }

        return $this->_facebook->call_method('facebook.users.setStatus', $params);
    }

}