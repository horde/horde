<?php
/**
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

$friends = Folks_Friends::singleton(null, array('user' => $user));
$result = $friends->removeFriend($GLOBALS['registry']->getAuth());
if ($result instanceof PEAR_Error) {
    $notification->push($result);
    Horde::url('edit/friends/index.php')->redirect();
}

$notification->push(sprintf(_("User \"%s\" was rejected as a friend."), $user), 'horde.success');

$title = sprintf(_("%s rejected you as a friend on %s"),
                    $GLOBALS['registry']->getAuth(),
                    $registry->get('name', 'horde'));

$body = sprintf(_("User %s rejected you as a friend on %s.. \nTo see to his profile, go to: %s \n"),
                $GLOBALS['registry']->getAuth(),
                $registry->get('name', 'horde'),
                Folks::getUrlFor('user', $GLOBALS['registry']->getAuth(), true, -1));

$friends->sendNotification($user, $title, $body);

Horde::url('edit/friends/index.php')->redirect();
