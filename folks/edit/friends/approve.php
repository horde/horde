<?php
/**
 * $Id: approve.php 974 2008-10-07 19:46:00Z duck $
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
require_once FOLKS_BASE . '/lib/Friends.php';

if (!$registry->isAuthenticated()) {
    $registry->authenticateFailure('folks');
}

$user = Horde_Util::getGet('user');
if (empty($user)) {
    $notification->push(_("You must supply a username."));
    Horde::url('edit/friends/index.php')->redirect();
}

$friends = Folks_Friends::singleton();
$result = $friends->approveFriend($user);
if ($result instanceof PEAR_Error) {
    $notification->push($result);
    $notification->push($result->getDebugInfo());
    Horde::url('edit/friends/index.php')->redirect();
}

$notification->push(sprintf(_("User \"%s\" was confirmed as a friend."), $user), 'horde.success');

$title = sprintf(_("%s approved you as a friend on %s"),
                    $GLOBALS['registry']->getAuth(),
                    $registry->get('name', 'horde'));

$body = sprintf(_("User %s confirmed you as a friend on %s.. \nTo see to his profile, go to: %s \n"),
                $GLOBALS['registry']->getAuth(),
                $registry->get('name', 'horde'),
                Folks::getUrlFor('user', $GLOBALS['registry']->getAuth(), true, -1));

$friends->sendNotification($user, $title, $body);

$link = '<a href="' . Folks::getUrlFor('user', $user) . '">' . $user . '</a>';
$folks_driver->logActivity(sprintf(_("Added user %s as a friend."), $link));

Horde::url('edit/friends/index.php')->redirect();
