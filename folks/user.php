<?php
 /**
 * $Id: user.php 1076 2008-11-29 09:33:19Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/lib/base.php';

// Load profile
$user = Horde_Util::getFormData('user', $GLOBALS['registry']->getAuth());
$profile = $folks_driver->getProfile($user);
if ($profile instanceof PEAR_Error) {
    $notification->push($profile);
    Folks::getUrlFor('list', 'list')->redirect();
}

// Load its friend list
require_once FOLKS_BASE . '/lib/Friends.php';
$friends_driver = Folks_Friends::singleton(null, array('user' => $user));

// Log user view
$folks_driver->logView($user);

// Get user activity
if ($profile['activity_log'] == 'all' ||
    $registry->isAuthenticated() && (
        $profile['activity_log'] == 'authenticated' ||
        $profile['activity_log'] == 'friends' && $friends_driver->isFriend($user))
    ) {
    $profile['activity_log'] = $folks_driver->getActivity($user);
    if ($profile['activity_log'] instanceof PEAR_Error) {
        $notification->push($profile);
        $profile['activity_log'] = array();
    }
} else {
    $profile['activity_log'] = array();
}

// Prepare an process activity form
if ($user == $GLOBALS['registry']->getAuth()) {
    require_once FOLKS_BASE . '/lib/Forms/Activity.php';
    $vars = Horde_Variables::getDefaultVariables();
    $form = new Folks_Activity_Form($vars, _("What are you doing right now?"), 'short');
    if ($form->validate()) {
        $result = $form->execute();
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(_("Activity successfully posted"), 'horde.success');
            Horde::url('user.php')->redirect();
        }
    }
}

Horde::addScriptFile('stripe.js', 'horde');

$title = sprintf(_("%s's profile"), $user);
require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

switch ($profile['user_status']) {

case 'inactive':
    require FOLKS_TEMPLATES . '/user/inactive.php';
break;

case 'deleted':
case 'deactivated':
    require FOLKS_TEMPLATES . '/user/deleted.php';
break;

case 'private':
    require FOLKS_TEMPLATES . '/user/private.php';
break;

case 'public_authenticated':
    if ($registry->isAuthenticated()) {
        require FOLKS_TEMPLATES . '/user/user.php';
    } else {
        require FOLKS_TEMPLATES . '/user/authenticated.php';
    }
break;

case 'public_friends':
    if ($friends_driver->isFriend($user)) {
        require FOLKS_TEMPLATES . '/user/user.php';
    } else {
        require FOLKS_TEMPLATES . '/user/friends.php';
    }
break;

default:
    require FOLKS_TEMPLATES . '/user/user.php';
break;
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
