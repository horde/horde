<?php
/**
 * Groups methods
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Groups extends Horde_Service_Facebook_Base
{
    /**
     * Returns groups according to the filters specified.
     *
     * @param string $uid  User associated with groups.
     *
     * @return array  An array of group objects
     */
    public function get($uid)
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callGraphApi($uid . '/groups');
    }

    /**
     * Returns the membership list of a group.
     *
     * @param integer $gid  Group id
     *
     * @return array  An array with four membership lists, with keys 'members',
     *                'admins', 'officers', and 'not_replied'
     */
    public function getMembers($gid)
    {
        // Session key is *required*
        if ($this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callGraphApi($gid . '/members');
    }

}