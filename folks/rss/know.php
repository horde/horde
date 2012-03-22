<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

$folks_authentication = 'none';
require_once __DIR__ . '/../lib/base.php';

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
if (!$GLOBALS['registry']->getAuth() &&
    (!isset($_SERVER['PHP_AUTH_USER']) ||
     !$auth->authenticate($_SERVER['PHP_AUTH_USER'], array('password' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null)))) {
    header('WWW-Authenticate: Basic realm="Letter RSS Interface"');
    header('HTTP/1.0 401 Unauthorized');
    echo '401 Unauthorized';
    exit;
}

require_once FOLKS_BASE . '/lib/Friends.php';
$friends_driver = Folks_Friends::singleton();

// Get friends
$my_list = $friends_driver->getFriends();
if ($my_list instanceof PEAR_Error) {
    $notification->push($my_list);
    $my_list = array();
}

// Get all friends of frends and make a top list of common users
$users = array();
foreach ($my_list as $friend) {
    $friends = Folks_Friends::singleton(null, array('user' => $friend));
    $friend_friends = $friends->getFriends();
    if ($friend_friends instanceof PEAR_Error) {
        continue;
    }
    foreach ($friend_friends as $friend_friend) {
        if ($friend_friend == $GLOBALS['registry']->getAuth() ||
            in_array($friend_friend, $my_list)) {
            continue;
        } elseif (isset($users[$friend_friend])) {
            $users[$friend_friend] += 1;
        } else {
            $users[$friend_friend] = 0;
        }
    }
}

arsort($users);
$users = array_slice($users, 0, 20, true);
$users = array_keys($users);

$title = _("People you might know");
$link = Folks::getUrlFor('list', 'online', true);
$rss_link = Horde::url('rss/friends.php', true);

require FOLKS_TEMPLATES . '/feed/feed.php';
