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

$friends = $friends_driver->getFriends();
if ($friends instanceof PEAR_Error) {
    $friends = array();
}

$online = $folks_driver->getOnlineUsers();
if ($online instanceof PEAR_Error) {
    $online = array();
}

$users = array();
foreach ($friends as $friend) {
    if (array_key_exists($friend, $online)) {
        $users[] = $friend;
    }
}


$title = _("Online friends");
$link = Folks::getUrlFor('list', 'online', true);
$rss_link = Horde::url('rss/friends.php', true);

require FOLKS_TEMPLATES . '/feed/feed.php';
