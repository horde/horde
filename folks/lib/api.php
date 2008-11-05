<?php
/**
 * Folks api
 *
 * Copyright 2008 Obala d.o.o (www.obala.si)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/folks/LICENSE.
 *
 * $Id: api.php 979 2008-10-08 08:31:13Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

$_services['commentCallback'] = array(
    'args' => array('id' => 'string'),
    'type' => 'string'
);

$_services['removeUserData'] = array(
    'args' => array('user' => 'string'),
    'type' => 'boolean'
);

$_services['hasComments'] = array(
    'args' => array(),
    'type' => 'boolean'
);

$_services['getOnlineUsers'] = array(
    'args' => array(),
    'type' => 'array'
);

$_services['getProfile'] = array(
    'args' => array(),
    'type' => 'array'
);

$_services['getFriends'] = array(
    'args' => array('user' => 'string'),
    'type' => 'array'
);

$_services['addFriend'] = array(
    'args' => array('friend' => 'string'),
    'type' => 'boolean'
);

$_services['getBlacklist'] = array(
    'args' => array('user' => 'string'),
    'type' => 'array'
);

$_services['addBlacklisted'] = array(
    'args' => array('user' => 'string'),
    'type' => 'boolean'
);

$_services['removeBlacklisted'] = array(
    'args' => array('user' => 'string'),
    'type' => 'boolean'
);

$_services['isBlacklisted'] = array(
    'args' => array('user' => 'string'),
    'type' => 'boolean'
);

$_services['show'] = array(
    'link' => '%application%/user.php?user=|user|'
);

$_services['listTimeObjectCategories'] = array(
    'type' => '{urn:horde}stringArray'
);

$_services['listTimeObjects'] = array(
    'args' => array('start' => 'int', 'end' => 'int'),
    'type' => '{urn:horde}hashHash'
);

$_services['logActivity'] = array(
    'args' => array('activity_message' => 'string', 'scope' => 'string', 'user' => 'string'),
    'type' => 'boolean'
);

$_services['getActivity'] = array(
    'args' => array('user' => 'user_name'),
    'type' => 'boolean'
);

$_services['authenticate'] = array(
    'args' => array('userID' => 'string', 'credentials' => '{urn:horde}hash', 'params' => '{urn:horde}hash'),
    'checkperms' => false,
    'type' => 'boolean'
);

$_services['userExists'] = array(
    'args' => array('userId' => 'string'),
    'type' => 'boolean'
);

$_services['addUser'] = array(
    'args' => array('userId' => 'string')
);

if (Auth::isAdmin()) {
    $_services['userList'] = array(
        'type' => '{urn:horde}stringArray'
    );

    $_services['removeUser'] = array(
        'args' => array('userId' => 'string')
    );
}

/**
 * Callback for comment API.
 *
 * @param int $id       Internal data identifier.
 * @param string $type  Type of data to retreive (title, owner...).
 * @param array $params  Parameter to be passed to callback function
 */
function _folks_commentCallback($id, $type = 'title', $params = null)
{
    static $info;

    if (!empty($info[$id][$type])) {
        return $info[$id][$type];
    }

    require_once dirname(__FILE__) . '/base.php';

    switch ($type) {

    case 'owner':
        return $id;

    case 'link':
        return Folks::getUrlFor('user', $id);

    case 'messages':

        // Update comments count
        $result = $GLOBALS['folks_driver']->updateComments($id);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // Update activity log
        $link = '<a href="' . Folks::getUrlFor('user', $id) . '">' . $id . '</a>';
        return $GLOBALS['folks_driver']->logActivity(sprintf(_("Commented user %s."), $link), 'folks:comments');

        return true;

    default:
        return $id;
    }
}

/**
 * Comments are enebled
 */
function _folks_hasComments()
{
    return $GLOBALS['conf']['comments']['allow'];
}

/**
 * Get online users
 */
function _folks_getOnlineUsers()
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['folks_driver']->getOnlineUsers();
}

/**
 * Get user profile
 *
 * @param string $user User to get profile for
 */
function _folks_getProfile($user = null)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['folks_driver']->getProfile($user);
}


/**
 * Get user friends
 *
 * @param string $user  Username to get friends for
 *
 * @return array of users
 */
function _folks_getFriends($user = null)
{
    require_once dirname(__FILE__) . '/Friends.php';

    $friends = Folks_Friends::singleton(null, null, $user);

    return $friends->getFriends();
}

/**
 * Add user to our friend list
 *
 * @param string $friend   Friend's usersame
 *
 * @return true or PEAR_Error
 */
function _folks_addFriend($user)
{
    require_once dirname(__FILE__) . '/Friends.php';

    $friends = Folks_Friends::singleton(null);

    return $friends->addFriend($user);
}

/**
 * Remove user from a fiend list
 *
 * @param string $friend   Friend's usersame
 *
 * @return true or PEAR_Error
 */
function _folks_removeFriend($user)
{
    require_once dirname(__FILE__) . '/Friends.php';

    $friends = Folks_Friends::singleton(null);

    return $friends->removeFriend($user);
}

/**
 * Get user blacklist
 *
 * @param string $user  Username to get blacklist for
 *
 * @return array of users
 */
function _folks_getBlacklist()
{
    require_once dirname(__FILE__) . '/Friends.php';

    $friends = Folks_Friends::singleton(null);

    return $friends->getBlacklist();
}

/**
 * Add user to a blacklist list
 *
 * @param string $user   Usersame
 */
function _folks_addBlacklisted($user)
{
    require_once dirname(__FILE__) . '/Friends.php';

    $friends = Folks_Friends::singleton(null);

    return $friends->addBlacklisted($user);
}

/**
 * Remove user from a blacklist list
 *
 * @param string $user   Usersame
 */
function _folks_removeBlacklisted($user)
{
    require_once dirname(__FILE__) . '/Friends.php';

    $friends = Folks_Friends::singleton(null);

    return $friends->removeBlacklisted($user);
}

/**
 * Are we blackisted by user this user?
 *
 * @param string $user  Username to get blacklist for
 *
 * @return array of users
 */
function _folks_isBlacklisted($user = null)
{
    if (empty($user)) {
        $user = Auth::getAuth();
    }

    require_once dirname(__FILE__) . '/Friends.php';

    $friends = Folks_Friends::singleton(null, null, $user);

    return $friends->isBlacklisted(Auth::getAuth());
}

/**
 * Users categories
 */
function _folks_listTimeObjectCategories()
{
    return array('birthday_friends' => _("Friends Birthdays"),
                    'birthday_all' => _("Users Birthdays"));
}

/**
 * Lists users with birthdays/goout dates as time objects.
 *
 * @param array $categories  The time categories (from listTimeObjectCategories) to list.
 * @param Horde_Date $start       The start date of the period.
 * @param Horde_Date $end         The end date of the period.
 */
function _folks_listTimeObjects($categories, $start, $end)
{
    require_once dirname(__FILE__) . '/base.php';

    $friends = array();
    $objects = array();

    foreach ($categories as $category) {
        $what = substr($category, 0, strpos($category, '_', 2));
        $criteria = array($what => array('from' => $start->timestamp(),
                                        'to' => $end->timestamp()));

        $users = $GLOBALS['folks_driver']->getUsers($criteria, 0, 500);
        if ($users instanceof PEAR_Error) {
            return array();
        }

        if (empty($friends)
            && ($category == 'birthday_friends')) {
            $friends = $GLOBALS['folks_driver']->getFriends(Auth::getAuth());
        }

        foreach ($users as $user) {

            if ($category == 'birthday_friends' &&
                    !array_key_exists($user['user_uid'], $friends)) {
                continue; // skip non friends
            }

            $age = Folks::calcAge($user['user_birthday']);
            $desc = $age['age'] . ' (' . $age['sign'] . ')';
            $user['user_birthday'] = date('Y') . substr($user['user_birthday'], 4);
            $from = strtotime($user['user_birthday']);
            $to = strtotime($user['user_birthday']) + 1;

            $objects[$user['user_uid']] = array(
                'title' => $user['user_uid'],
                'description' => $desc,
                'id' => $user['user_uid'],
                'start' => date('Y-m-d\TH:i:s', $from),
                'end' => date('Y-m-d\TH:i:s', $to),
                'params' => array('user' => $user['user_uid']));
        }
    }

    return $objects;
}

/**
 * Log user's activity
 *
 *
 * @param string $message    Activity message
 * @param string $scope    Scope
 * @param string $user    $user
 *
 * @return boolean  True on success or a PEAR_Error object on failure.
 */
function _folks_logActivity($message, $scope, $user = null)
{
    if (empty($user)) {
        $user = Auth::getAuth();
    } elseif ($user !== Auth::getAuth() && !Auth::isAdmin('admin:' . $scope)) {
        return PEAR::raiseError(_("You cannot log activities for other users."));
    }

    // Do not load the whole applcation
    // require_once dirname(__FILE__) . '/base.php';
    // return $GLOBALS['folks_driver']->logActivity($message, $scope, $user)

    $_db = DB::connect($GLOBALS['conf']['sql']);
    $query = 'INSERT INTO folks_activity'
            . ' (user_uid, activity_message, activity_scope, activity_date) '
            . ' VALUES (?, ?, ?, ?)';
    return $_db->query($query, array($user, $message, $scope, $_SERVER['REQUEST_TIME']));
}

/**
 * Get user's activity
 *
 * @param string $user    Username
 * @param int $limit    Number of actions to return
 *
 * @return array    Activity log
 */
function _folks_getActivity($user, $limit = 10)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['folks_driver']->getActivity($user, $limit);
}

/**
 * Authenticate a givern user
 *
 * @param string $userID       Username
 * @param array  $credentials  Array of criedentials (password requied)
 * @param array  $params       Additional params
 *
 * @return boolean  Whether IMP authentication was successful.
 */
function _folks_authenticate($userID, $credentials, $params)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['folks_driver']->comparePassword($userID, $credentials['password']);
}

/**
 * Check if a user exists
 *
 * @param string $userID       Username
 *
 * @return boolean  True if user exists
 */
function _folks_userExists($userId)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['folks_driver']->userExists($userId);
}

/**
 * Lists all users in the system.
 *
 * @return array  The array of userIds, or a PEAR_Error object on failure.
 */
function _folks_userList()
{
    require_once dirname(__FILE__) . '/base.php';

    $users = array();
    foreach ($GLOBALS['folks_driver']->getUsers() as $user) {
        $users[] = $user['user_uid'];
    }

    return $users;
}

/**
 * Adds a set of authentication credentials.
 *
 * @param string $userId  The userId to add.
 *
 * @return boolean  True on success or a PEAR_Error object on failure.
 */
function _folks_addUser($userId)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['folks_driver']->addUser($userId);
}

/**
 * Deletes a set of authentication credentials.
 *
 * @param string $userId  The userId to delete.
 *
 * @return boolean  True on success or a PEAR_Error object on failure.
 */
function _folks_removeUser($userId)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['folks_driver']->deleteUser($userId);
}

/**
 * Deletes a user and its data
 *
 * @param string $userId  The userId to delete.
 *
 * @return boolean  True on success or a PEAR_Error object on failure.
 */
function _folks_removeUserData($user)
{
    return _folks_removeUser($user);
}
