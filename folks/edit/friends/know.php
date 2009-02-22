<?php
/**
 * $Id: friends.php 976 2008-10-07 21:24:47Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/../../lib/base.php';;
require_once FOLKS_BASE . '/edit/tabs.php';

$title = _("People you might know");

// Load driver
require_once FOLKS_BASE . '/lib/Friends.php';
$friends = Folks_Friends::singleton();

// Get friends
$my_list = $friends->getFriends();
if ($my_list instanceof PEAR_Error) {
    $notification->push($my_list);
    $my_list = array();
}

// Get all friends of frends and make a top list of common users
$possibilities = array();
foreach ($my_list as $friend) {
    $friends = Folks_Friends::singleton(null, array('user' => $friend));
    $friend_friends = $friends->getFriends();
    if ($friend_friends instanceof PEAR_Error) {
        continue;
    }
    foreach ($friend_friends as $friend_friend) {
        if ($friend_friend == Auth::getAuth() ||
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
$list = array_slice($possibilities, 0, 20, true);
$list = array_keys($list);

// Prepare actions
$actions = array(
    array('url' => Horde::applicationUrl('edit/friends/add.php'),
          'img' => Horde::img('delete.png', '', '', $registry->getImageDir('horde')),
          'id' => 'user',
          'name' => _("Add")),
    array('url' => Horde::applicationUrl('user.php'),
          'img' => Horde::img('user.png', '', '', $registry->getImageDir('horde')),
          'id' => 'user',
          'name' => _("View profile")));
if ($registry->hasInterface('letter')) {
    $actions[] = array('url' => $registry->callByPackage('letter', 'compose', ''),
                        'img' => Horde::img('letter.png', '', '', $registry->getImageDir('letter')),
                        'id' => 'user_to',
                        'name' => _("Send message"));
}

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('friends');
require FOLKS_TEMPLATES . '/edit/friends.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';