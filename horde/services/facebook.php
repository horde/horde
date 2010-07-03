<?php
/**
 * Callback page for Facebook integration, that doubles as a Prefs page as well.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde');

try {
    $facebook = $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
} catch (Horde_Exception $e) {
    $horde_url = Horde::url($registry->get('webroot', 'horde') . '/index.php');
    header('Location: ' . $horde_url);
}

// See why we are here.
if ($token = Horde_Util::getFormData('auth_token')) {
    // Assume we are here for a successful authentication if we have a
    // auth_token. It *must* be allowed to be in GET since that's how FB
    // sends it. This is the *only* time we will be able to capture these values.
    try {
        $haveSession = $facebook->auth->validateSession(true, true);
    } catch (Horde_Service_Facebook_Exception $e) {
        $notification->push(_("Temporarily unable to connect with Facebook, Please try again."), 'horde.alert');
    }
    if ($haveSession) {
        // Remember in user prefs
        $sid =  $facebook->auth->getSessionKey();
        $uid = $facebook->auth->getUser();
        $prefs->setValue('facebook', serialize(array('uid' => $uid, 'sid' => $sid)));
        $notification->push(_("Succesfully connected your Facebook account."), 'horde.success');
        $url = Horde::url('services/prefs.php', true)->add(array('group' => 'facebook', 'app'  => 'horde'));
        header('Location: ' . $url);
    }
} else {
    // Require the rest of the actions to be POST only since following them
    // could change the user's state.
    $action = Horde_Util::getPost('actionID');
    switch ($action) {
    case 'updateStatus':
        // Set the user's status
        $fbp = unserialize($prefs->getValue('facebook'));
        if (!$fbp) {
            // Something wrong
        }
        $facebook->auth->setUser($fbp['uid'], $fbp['sid']);
        // This is an AJAX action, so just echo the result and return.
        $status = Horde_Util::getPost('statusText');
        if ($facebook->users->setStatus($status)) {
            echo htmlspecialchars($status);
        } else {
            echo _("Status unable to be set.");
        }

        exit;
    case 'addLike':
        // Add a "like"
        $fbp = unserialize($prefs->getValue('facebook'));
        if (!$fbp) {
            //??
        }
        $facebook->auth->setUser($fbp['uid'], $fbp['sid']);
        $id = Horde_Util::getPost('post_id');
        if ($facebook->streams->addLike($id)) {
            $fql = 'SELECT post_id, likes FROM stream WHERE post_id="' . $id . '"';
            try {
                $post = $facebook->fql->run($fql);
            } catch (Horde_Service_Facebook_Exception $e) {
                // Already set the like by the time we are here, so just indicate
                // that.
                echo _("You like this");
                exit;
            }
            $post = current($post);
            $likes = $post['likes'];
            if ($likes['count'] > 1) {
                $html = sprintf(ngettext("You and %d other person likes this", "You and %d other people like this", $likes['count'] - 1), $likes['count'] - 1);
            } else {
                $html = _("You like this");
            }
            echo $html;
        } else {
            echo _("Unable to set like.");
        }
        exit;
    }
}

// No $uid here means we don't have any stored session information. We purposely
// don't rely on anything in cookies at this point since there's no way of
// knowing for sure that any valid Facebook cookie would be for the user we
// want to attach to this Horde account.
if (empty($uid)) {
    $fbp = unserialize($prefs->getValue('facebook'));
    $uid = !empty($fbp['uid']) ? $fbp['uid'] : 0;
    $sid = !empty($fbp['sid']) ? $fbp['sid'] : 0;
}

// OK, we have a uid either from prefs or a new authorize app request.
// Let's go the extra mile and make 100% sure the user has authorized the
// Horde application. (This might fail, for instance, if the user had auth'd it
// in the past (so we have a uid), but decided to revoke the auth.
if (!empty($uid)) {
    try {
        $have_app = $facebook->users->isAppUser($uid);
    } catch (Horde_Service_Facebook_Exception $e) {
        $error = $e->getMessage();
    }
}

// At this point, we know if we have a user that has authorized the application,
// Check to be sure that if we have a session_key, that it is still good.
if (!empty($have_app) && !empty($sid)) {
    $facebook->auth->setUser($uid, $sid, 0);
    try {
        // Get the userid associated with this session. Will throw an exception
        // if the session is invalid (which we catch below).
        $session_uid = $facebook->auth->getLoggedInUser();
        if ($uid != $session_uid) {
            // This should never happen.
            $haveSession = false;
        } else {
            $haveSession = true;
        }
    } catch (Horde_Service_Facebook_Exception $e) {
        // Something wrong with the session.
        $haveSession = false;
        $prefs->setValue('facebook', serialize(array('uid' => $uid, 'sid' => 0)));
    }
}

