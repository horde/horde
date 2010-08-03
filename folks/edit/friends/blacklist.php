<?php
/**
 * $Id: blacklist.php 1234 2009-01-28 18:44:02Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/../../lib/base.php';
require_once FOLKS_BASE . '/lib/Forms/AddFriend.php';
require_once FOLKS_BASE . '/edit/tabs.php';

$title = _("Blacklist");

// Load driver
require_once FOLKS_BASE . '/lib/Friends.php';
$friends = Folks_Friends::singleton();

// Perform action
$user = Horde_Util::getGet('user');
if ($user) {
    if ($friends->isBlacklisted($user)) {
        $result = $friends->removeBlacklisted($user);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(sprintf(_("User \"%s\" was removed from your blacklist."), $user), 'horde.success');
            Horde::applicationUrl('edit/friends/blacklist.php')->redirect();
        }
    } else {
        $result = $friends->addBlacklisted($user);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(sprintf(_("User \"%s\" was added to your blacklist."), $user), 'horde.success');
            Horde::applicationUrl('edit/friends/blacklist.php')->redirect();
        }
    }
}

// Get blacklist
$list = $friends->getBlacklist();
if ($list instanceof PEAR_Error) {
    $notification->push($list);
    $blacklist = array();
}

// Users online
$online = $folks_driver->getOnlineUsers();
if ($online instanceof PEAR_Error) {
    return $online;
}

// Get groups
$groups = $friends->getGroups();
if ($groups instanceof PEAR_Error) {
    $notification->push($groups);
    $groups = array();
}

// Prepare actions
$actions = array(
    array('url' => Horde::applicationUrl('edit/friends/blacklist.php'),
          'img' => Horde::img('delete.png'),
          'id' => 'user',
          'name' => _("Remove")),
    array('url' => Horde::applicationUrl('user.php'),
          'img' => Horde::img('user.png'),
          'id' => 'user',
          'name' => _("View profile")));

$friend_form = new Folks_AddFriend_Form($vars, _("Add or remove user"), 'blacklist');

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('blacklist');
require FOLKS_TEMPLATES . '/edit/header.php';
require FOLKS_TEMPLATES . '/edit/friends.php';
$friend_form->renderActive();
require FOLKS_TEMPLATES . '/edit/footer.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
