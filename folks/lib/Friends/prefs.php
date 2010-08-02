<?php
/**
 * Folks internal firends implementaton
 *
 * NOTE: You must add this prefs to your application
 *
 *  $_prefs['whitelist'] = array(
 *      'value' => '',
 *      'locked' => false,
 *      'shared' => false,
 *     'type' => 'implicit'
 *  );
 *
 *  $_prefs['blacklist'] = array(
 *      'value' => '',
 *      'locked' => false,
 *      'shared' => false,
 *      'type' => 'implicit'
 *  );
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_prefs extends Folks_Friends {

    /**
     * Add user to a friend list
     *
     * @param string $friend   Friend's usersame
     */
    protected function _addFriend($friend)
    {
        return $this->_addrem_lists('whitelist', $friend);
    }

    /**
     * Remove user from a fiend list
     *
     * @param string $friend   Friend's usersame
     */
    public function removeFriend($friend)
    {
        return $this->_addrem_lists('whitelist', $friend);
    }

    /**
     * Get user friends
     *
     * @return array of users
     */
    public function getFriends()
    {
        return $this->_lists('whitelist');
    }

    /**
     * Get user blacklist
     *
     * @return array of users blacklist
     */
    public function getBlacklist()
    {
        return $this->_lists('blacklist');
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _addBlacklisted($user)
    {
        return $this->_addrem_lists('blacklist', $user);
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    public function removeBlacklisted($user)
    {
        return $this->_addrem_lists('blacklist', $user);
    }

    /**
     * Returns array of usernames in a list of false if list is empty
     * splits list by any number of commas or space characters
     * which include " ", \r, \t, \n and \f
     *
     * @param string $type List type to retreive
     * @param string $user Username to check
     *
     * @return array $list array fo usernames
     */
    private function _lists($type, $user = null)
    {
        if (empty($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }

        $u_prefs = $GLOBALS['injector']->getInstance('Horde_Prefs')->getPrefs($GLOBALS['registry']->getApp(), array(
            'user' => $user
        ));

        $list = $u_prefs->getValue($type);

        if ($list) {
            $users = preg_split("/[\s,]+/", $list, -1, PREG_SPLIT_NO_EMPTY);
            if (sizeof($users) > 0) {
                $list = array();
                foreach ($users as $value) {
                   $list[$value] = $value;
                }
                return $list;
            }
        }

        return array();
    }

    /**
     * Add/remove a user from a list
     *
     * @param string $type of the list
     * @param string $user user to applay
     */
    private function _addrem_lists($type, $user)
    {
        global $prefs;

        $list = $prefs->getValue($type);

        if ($list) {
            $users = preg_split("/[\s,]+/", $list, -1, PREG_SPLIT_NO_EMPTY);
            if (in_array($user, $users)) {
                $key = array_search($user, $users);
                unset($users[$key]);
                sort($users);
                $prefs->setValue($type, implode($users, ' '));
            } else {
                $users[] = $user;
                sort($users);
                $prefs->setValue($type, implode($users, ' '));
            }
        } else {
            $prefs->setValue($type, $user);
        }

        return false;
    }

    /**
     * Get avaiable groups
     */
    public function getGroups()
    {
        return array('whitelist' => _("Friends"));
    }
}
