<?php
/**
 * Users methods for Horde_Service_Facebook
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Users extends Horde_Service_Facebook_Base
{
    /**
     * Local cache of requested permissions
     *
     * @var array
     */
    protected $_permissions = array();

    /**
     * Local cache of requested user information
     *
     * @var array
     */
    protected $_users = array();

    /**
     * Return a URL to the facebook page of the requested user.
     *
     * @param string $uid  The Facebook user id.
     *
     * @return string  The URL to the user's Facebook page.
     */
    public function getProfileLink($uid)
    {
        return $this->_facebook->getFacebookUrl() . '/' . $uid;
    }

    /**
     * Return a URL to the user's thumbnail image.
     *
     * @param string $uid  The Facebook user id.
     *
     * @return string  The URL to the user's Facebook thumbnail.
     */
    public function getThumbnail($uid)
    {
        return $this->_facebook->getFacebookUrl('graph') . '/' . $uid . '/picture';
    }

    /**
     * Returns the requested info fields for the requested set of users.
     *
     * @param string $uids   A comma-separated list of user ids
     * @param array $fields  An array of fields to return. If empty, all fields
     *                       are returned.
     *
     * @todo Better cache handling.
     *
     * @return object  The user information as a stdClass.
     */
    public function getInfo($uid = null, array $fields = array())
    {
        if (empty($uid)) {
            $uid = 'me';
        }
        if (!empty($fields)) {
            $params = array('fields' => implode(',', $fields));
        } else {
            $params = array();
        }
        $key = md5($uid . implode(',', $fields));
        if (empty($this->_users[$key])) {
            $this->_users[$key] = $this->_facebook->callGraphApi($uid, $params);
        }

        return $this->_users[$key];
    }

    /**
     * Return the list of the current application permissions for the specified
     * user.
     *
     * @param string $uid  The uid to request permissions for. If null, the
     *                     currently authenticated user is used.
     *
     * @return array  An array of permissions where the keys are permission
     *                names and the values are '1' for yes or '0' for no.
     */
    public function getAppPermissions($uid = null)
    {
        if (empty($uid) && !$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('users.hasAppPermission requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        if (empty($uid)) {
            $uid = 'me';
        }

        if (empty($this->_permissions[$uid])) {
            $results = $this->_facebook->callGraphApi($uid . '/permissions');
            if (!empty($results) && !empty($results->data)) {
                return $this->_permissions[$uid] = (array)$results->data[0];
            }
        } else {
            return $this->_permissions[$uid];
        }
    }

}