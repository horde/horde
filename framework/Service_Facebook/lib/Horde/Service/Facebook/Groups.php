<?php
/**
 * Groups methods
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Groups extends Horde_Service_Facebook_Base
{
    /**
     * Returns groups according to the filters specified.
     *
     * @param integer $uid   (Optional) User associated with groups.  A null
     *                       parameter will default to the session user.
     * @param string  $gids  (Optional) Comma-separated group ids to query. A null
     *                       parameter will get all groups for the user.
     *
     * @return array  An array of group objects
     */
    public function &get($uid, $gids)
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->callMethod('facebook.groups.get',
            array('uid' => $uid, 'gids' => $gids, 'session_key' => $skey));
    }

    /**
     * Returns the membership list of a group.
     *
     * @param integer $gid  Group id
     *
     * @return array  An array with four membership lists, with keys 'members',
     *                'admins', 'officers', and 'not_replied'
     */
    public function &getMembers($gid)
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->callMethod('facebook.groups.getMembers',
             array('gid' => $gid, 'session_key' => $skey));
    }

}