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

require_once __DIR__ . '/../../lib/base.php';
require_once FOLKS_BASE . '/lib/Forms/AddFriend.php';
require_once FOLKS_BASE . '/edit/tabs.php';

$title = _("Add friend");

// Load driver
require_once FOLKS_BASE . '/lib/Friends.php';
$friends = Folks_Friends::singleton();

// Perform action
$user = Horde_Util::getFormData('user');
if ($user) {
    if ($friends->isFriend($user)) {
        $result = $friends->removeFriend($user);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(sprintf(_("User \"%s\" was removed from your friend list."), $user), 'horde.success');
            Horde::url('edit/friends/index.php')->redirect();
        }
    } else {
        $result = $friends->addFriend($user);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } elseif ($friends->needsApproval($user)) {
            $title = sprintf(_("%s added you as a friend on %s"),
                                        $GLOBALS['registry']->getAuth(),
                                        $GLOBALS['registry']->get('name', 'horde'));
            $body = sprintf(_("User %s added you to his firends list on %s. \nTo approve, go to: %s \nTo reject, go to: %s \nTo see to his profile, go to: %s \n"),
                            $GLOBALS['registry']->getAuth(),
                            $registry->get('name', 'horde'),
                            Horde::url('edit/friends/approve.php', true, -1)->add('user', $GLOBALS['registry']->getAuth()),
                            Horde::url('edit/friends/reject.php', true, -1)->add('user', $GLOBALS['registry']->getAuth()),
                            Folks::getUrlFor('user', $GLOBALS['registry']->getAuth(), true, -1));
            $result = $friends->sendNotification($user, $title, $body);
            if ($result instanceof PEAR_Error) {
                $notification->push($result);
            } else {
                $notification->push(sprintf(_("A confirmation was send to \"%s\"."), $user), 'horde.warning');
            }
            Horde::url('edit/friends/index.php')->redirect();
        } else {
            $notification->push(sprintf(_("User \"%s\" was added as your friend."), $user), 'horde.success');
            Horde::url('edit/friends/index.php')->redirect();
        }
    }
}

$friend_form = new Folks_AddFriend_Form($vars, _("Add or remove user"), 'blacklist');

$page_output->addScriptFile('tables.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require FOLKS_TEMPLATES . '/menu.inc';
echo $tabs->render('add');
require FOLKS_TEMPLATES . '/edit/header.php';
require FOLKS_TEMPLATES . '/edit/add.php';
require FOLKS_TEMPLATES . '/edit/footer.php';
$page_output->footer();
