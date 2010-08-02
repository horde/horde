<?php
/**
 * Folks_Friends:: defines an API for implementing storage backends for
 * Friends.
 *
 * $Id: Friends.php 1248 2009-01-30 15:04:49Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

class Folks_Friends {

    /**
     * Friends instances
     */
    static private $instances = array();

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * String containing user
     *
     * @var string
     */
    protected $_user;

    /**
     * String cache reference
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Attempts to return a concrete Folks_Friends instance based on $friends.
     *
     * @param string $friends  The type of the concrete Folks_Friends subclass
     *                        to return.  The class name is based on the
     *                        storage Friends ($friends).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Folks_Friends  The newly created concrete Folks_Friends
     *                          instance, or false on an error.
     */
    private static function factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['friends']['driver'];
        }

        if ($params === null && isset($GLOBALS['conf']['friends']['params'])) {
            $params = $GLOBALS['conf']['friends']['params'];
        }

        $driver = basename($driver);

        $class = 'Folks_Friends_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Friends/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

    /**
     * Singleton for driver object
     *
     * @param string $friends  The type of the concrete Folks_Friends subclass
     *                        to return.  The class name is based on the
     *                        storage Friends ($friends).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     */
    static public function singleton($driver = null, $params = null)
    {
        if (empty($params['user'])) {
            $params['user'] = $GLOBALS['registry']->getAuth();
        }

        $signature = $driver . ':' . $params['user'];
        if (!array_key_exists($signature, self::$instances)) {
            self::$instances[$signature] = self::factory($driver, $params);
        }

        return self::$instances[$signature];
    }

    /**
     * Construct object
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     */
    protected function __construct($params)
    {
        $this->_user = empty($params['user']) ? $GLOBALS['registry']->getAuth() : $params['user'];

        $this->_cache = $GLOBALS['injector']->getInstance('Horde_Cache');
    }

    /**
     * Queries the current object to find out if it supports the given
     * capability.
     *
     * @param string $capability  The capability to test for.
     *
     * @return boolean  Whether or not the capability is supported.
     */
    public function hasCapability($capability)
    {
        return !empty($this->_capabilities[$capability]);
    }

    /**
     * Check if a users requies his approval to be added as a friend
     *
     * @param string $user   Usersame
     *
     * @return boolean
     */
    public function needsApproval($user)
    {
        if ($GLOBALS['prefs']->isLocked('friends_approval')) {
            return (boolean)$GLOBALS['prefs']->getValue('friends_approval');
        }

        $prefs = $GLOBALS['injector']->getInstance('Horde_Prefs')->getPrefs('folks', array(
            'cache' => false,
            'user' => $GLOBALS['registry']->convertUsername($user, true)
        ));

        return (boolean)$prefs->getValue('friends_approval');
    }

    /**
     * Send user a nofication or approve request
     *
     * @param string $user   Usersame
     * @param string $title   Title of notification
     * @param string $body   Content of notification
     *
     * @return boolean
     */
    public function sendNotification($user, $title, $body)
    {
        $to = Folks::getUserEmail($user);
        if ($to instanceof PEAR_Error) {
            return $to;
        }

        $result = Folks::sendMail($to, $title, $body);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        if (!$GLOBALS['registry']->hasInterface('letter')) {
            return true;
        }

        return $GLOBALS['registry']->callByPackage(
            'letter', 'sendMessage', array($user,
                                           array('title' => $title,
                                                 'content' => $body)));
    }

    /**
     * Get user blacklist
     *
     * @return array of users blacklist
     */
    public function getBlacklist()
    {
        static $blacklist;

        if (is_array($blacklist)) {
            return $blacklist;
        }

        $blacklist = $this->_cache->get('folksBlacklist' . $this->_user, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($blacklist) {
            return unserialize($blacklist);
        } else {
            $blacklist = $this->_getBlacklist();
            if ($blacklist instanceof PEAR_Error) {
                return $blacklist;
            }
            $this->_cache->set('folksBlacklist' . $this->_user, serialize($blacklist));
            return $blacklist;
        }
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    public function addBlacklisted($user)
    {
        if ($this->_user == $user) {
            return PEAR::raiseError(_("You cannot add yourself to your blacklist."));
        }

        // Check if users exits
        $auth = $GLOBALS['injector']->getInstance('Horde_Auth')->getAuth();
        if (!$auth->exists($user)) {
            return PEAR::raiseError(sprintf(_("User \"%s\" does not exits"), $user));
        }

        // Do not allow to blacklist adminstrators
        if (in_array($user, $this->_getAdmins())) {
            return PEAR::raiseError(sprintf(_("You cannot add \"%s\" to your blacklist."), $user));
        }

        $result = $this->_addBlacklisted($user);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $this->_cache->expire('folksBlacklist' . $this->_user);

        return true;
    }

    /**
     * Remove user from blacklist list
     *
     * @param string $user   Usersame
     */
    public function removeBlacklisted($user)
    {
        $result = $this->_removeBlacklisted($user);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $this->_cache->expire('folksBlacklist' . $this->_user);

        return true;
    }

    /**
     * Check if user is on blacklist
     *
     * @param string $user User to check
     *
     * @return boolean
     */
    public function isBlacklisted($user)
    {
        $blacklist = $this->getBlacklist();
        if ($blacklist instanceof PEAR_Error) {
            return $blacklist;
        }

        return in_array($user, $blacklist);
    }

    /**
     * Add user to a friend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group    Group to add friend to
     */
    public function addFriend($friend, $group = null)
    {
        $friend = strtolower($friend);

        if ($this->_user == $friend) {
            return PEAR::raiseError(_("You cannot add yourself as your own friend."));
        }

        // Check if users exits
        $auth = $GLOBALS['injector']->getInstance('Horde_Auth')->getAuth();
        if (!$auth->exists($friend)) {
            return PEAR::raiseError(sprintf(_("User \"%s\" does not exits"), $friend));
        }

        // Check if user exists in group
        $friends = $this->getFriends();
        if ($friends instanceof PEAR_Error) {
            return $friends;
        }  elseif (in_array($friend, $friends)) {
            return PEAR::raiseError(sprintf(_("User \"%s\" is already in fiend list"), $friend));
        }

        // Check if user is frend but has not confmed us yet
        $friends = $this->waitingApprovalFrom();
        if ($friends instanceof PEAR_Error) {
            return $friends;
        }  elseif (in_array($friend, $friends)) {
            return PEAR::raiseError(sprintf(_("User \"%s\" is already in fiend list, but we are waiting his/her approval."), $friend));
        }

        // Add friend to backend
        $result = $this->_addFriend($friend, $group);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // If we do not need an approval just expire cache
        if (!$this->needsApproval($friend)) {
            $this->_cache->expire('folksFriends' . $this->_user . $group);
        }

        return true;
    }

    /**
     * Remove user from a fiend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group   Group to remove friend from
     */
    public function removeFriend($friend, $group = null)
    {
        $this->_cache->expire('folksFriends' . $this->_user . $group);

        return $this->_removeFriend($friend, $group);
    }

    /**
     * Get user friends
     *
     * @param string $user   Username
     * @param string $group  Get friens only from this group
     *
     * @return array of users (in group)
     */
    public function getFriends($group = null)
    {
        static $friends;

        if (is_array($friends)) {
            return $friends;
        }

        $friends = $this->_cache->get('folksFriends' . $this->_user . $group, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($friends) {
            return unserialize($friends);
        } else {
            $friends = $this->_getFriends($group);
            if ($friends instanceof PEAR_Error) {
                return $friends;
            }
            $this->_cache->set('folksFriends' . $this->_user . $group, serialize($friends));
            return $friends;
        }
    }

    /**
     * Get friends that does not confirm the current user yet
     */
    public function waitingApprovalFrom()
    {
        return array();
    }

    /**
     * User that we do not confirm them user yet
     */
    public function waitingApprovalFor()
    {
        return array();
    }

    /**
     * Approve our friend to add us to his userlist
     *
     * @param string $friend  Friend username
     */
    public function approveFriend($friend)
    {
        $result = $this->_approveFriend($friend);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $this->_cache->expire('folksFriends' . $friend);
        $this->_cache->expire('folksFriends' . $this->_user);

        return true;
    }

    /**
     * Approve our friend to add us to his userlist
     *
     * @param string $friend  Friedn username
     */
    protected function _approveFriend($friend)
    {
        return true;
    }

    /**
     * Check if user is on blacklist
     *
     * @param string $user User to check
     *
     * @return boolean
     */
    public function isFriend($user)
    {
        if ($user == $this->_user) {
            return true;
        }

        $friends = $this->getFriends();
        if ($friends instanceof PEAR_Error) {
            return $friends;
        }

        return in_array($user, $friends);
    }

    /**
     * Return all friends of out frends
     * and make a top list of common users
     *
     * @param int $limit Users
     *
     * @return array users
     */
    public function getPossibleFriends($limit = 0)
    {
        $possibilities = array();

        $my_list = $this->getFriends();
        if ($my_list instanceof PEAR_Error) {
            return $my_list;
        }

        foreach ($my_list as $friend) {
            $friends = Folks_Friends::singleton(null, array('user' => $friend));
            $friend_friends = $friends->getFriends();
            if ($friend_friends instanceof PEAR_Error) {
                continue;
            }
            foreach ($friend_friends as $friend_friend) {
                if ($friend_friend == $this->_user ||
                    in_array($friend_friend, $my_list)) {
                    continue;
                } elseif (isset($possibilities[$friend_friend])) {
                    $possibilities[$friend_friend] += 1;
                } else {
                    $possibilities[$friend_friend] = 0;
                }
            }
        }

        arsort($possibilities);

        if ($limit) {
            $possibilities = array_slice($possibilities, 0, $limit, true);
            $possibilities = array_keys($possibilities);
        }

        return $possibilities;
    }

    /**
     * Get users who have us on their friendlist
     *
     * @return array users
     */
    public function friendOf()
    {
        return false;
    }

    /**
     * Get user owning group
     *
     * @param integer Get group ID
     *
     * @param string Owner
     */
    public function getGroupOwner($group)
    {
        return $this->_user;
    }

    /**
     * Get user groups
     */
    public function getGroups()
    {
        $groups = $this->_cache->get('folksGroups' . $this->_user, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($groups) {
            return unserialize($groups);
        } else {
            $groups = $this->_getGroups();
            if ($groups instanceof PEAR_Error) {
                return $groups;
            }
            $this->_cache->set('folksGroups' . $this->_user, serialize($groups));
            return $groups;
        }
    }

    /**
     * Get administartor usernames
     */
    private function _getAdmins()
    {
        if (!$GLOBALS['injector']->getInstance('Horde_Perms')->exists('folks:admin')) {
            return array();
        }

        $permission = $GLOBALS['injector']->getInstance('Horde_Perms')->getPermission('folks:admin');

        return array_merge($permission->getUserPermissions(PERM_DELETE),
                            $GLOBALS['conf']['auth']['admins']);
    }
}
