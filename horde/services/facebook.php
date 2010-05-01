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

$GLOBALS['injector']->addBinder('Facebook', new Horde_Core_Binder_Facebook());
try {
    $facebook = $GLOBALS['injector']->getInstance('Facebook');
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
    }
} else {
    // Require the rest of the actions to be POST only since following them
    // could change the user's state.
    $action = Horde_Util::getPost('actionID');

    switch ($action) {
    case 'revokeInfinite':
        // Revoke the offline_access permission.
        $fbp = unserialize($prefs->getValue('facebook'));
        if (!$fbp) {
            // Something wrong
        }
        $facebook->auth->setUser($fbp['uid'], $fbp['sid']);
        $facebook->auth->revokeExtendedPermission(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_OFFLINE,
            $facebook->auth->getUser());
        break;

    case 'revokeApplication':
        $fbp = unserialize($prefs->getValue('facebook'));
        if (!$fbp) {
            // Something wrong
        }
        $facebook->auth->setUser($fbp['uid'], $fbp['sid']);
        $facebook->auth->revokeAuthorization();
        // Clear prefs.
        $prefs->setValue('facebook', array('uid' => '',
                                           'sid' => ''));

        break;
    case 'revokePublish':
        $fbp = unserialize($prefs->getValue('facebook'));
        if (!$fbp) {
            // Something wrong
        }
        $facebook->auth->setUser($fbp['uid'], $fbp['sid']);
        $facebook->auth->revokeExtendedPermission(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM,
            $facebook->auth->getUser());
        break;
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

// If we have a good session, see about our extended permissions
if (!empty($haveSession)) {
    try {
        $have_offline = $facebook->users->hasAppPermission(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_OFFLINE, $uid);
        $have_publish = $facebook->users->hasAppPermission(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM, $uid);
        $have_read = $facebook->users->hasAppPermission(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM, $uid);
    } catch (Horde_Service_Facebook_Exception $e) {
        $error = $e->getMessage();
    }
}

// Start rendering the prefs page
// TODO: This won't work - prefs handling must be moved to the new
// preferences framework.
//$csslink = $registry->get('themesuri', 'horde') . '/facebook.css';

if (!empty($haveSession)) {
    // If we are here, we have a valid session. Facebook strongly suggests to
    // place the user's profile picture with the small Facebook icon on it,
    // along with the user's full name (as it appears on Facebook) on the page
    // to clarify this to the user. They also suggest using Facebook CSS rules
    // for Facebook related notices and content.
    $fql = 'SELECT first_name, last_name, status, pic_with_logo, current_location FROM user WHERE uid IN (' . $uid . ')';

    try {
        $user_info = $facebook->fql->run($fql);
    } catch (Horde_Service_Facebook_Exception $e) {
        $notify->push(_("Temporarily unable to connect with Facebook, Please try again."), 'horde.alert');
    }
    // url to revoke authorization
    $url = Horde_Util::addParameter(Horde::selfUrl(true), array('action' => 'revokeApplication'));
    $url = Horde::signQueryString($url);

    echo '<div class="fbbluebox" style="float:left">';
    echo '<span><img src="' . $user_info[0]['pic_with_logo'] . '" /></span>';
    echo '<span>' . $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name'] . '</span>';
    echo '</div>';
    echo '<div class="clear">&nbsp;</div>';

    // Offline access links
    if (!empty($have_app) && empty($have_offline) ) {
        // Url for offline_access perms
        $url = $facebook->auth->getExtendedPermUrl(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_OFFLINE,
            Horde_Util::addParameter(Horde::url('services/facebook.php', true), 'action', 'authsuccess'));

        echo '<div class="fbbluebox">'
            . sprintf(_("%s can interact with your Facebook account, but you must login to Facebook manually each time."), $registry->get('name'))
            . '<div class="fbaction">' .  _("Authorize an infinite session")
            . ': <a class="fbbutton" href="'. $url . '">Facebook</a></div></div>';

    } elseif (!empty($have_app)) {
        // Have offline_access, provide a means to revoke it from within Horde
        echo '<div class="fbbluebox">'
            . _("Infinite sessions enabled.")
            . '<div class="fbaction"><input type="submit" class="fbbutton" value="' . _("Disable") . '" onclick="document.prefs.actionID.value=\'revokeInfinite\'; return true" /></div></div>';
    }

    // Publish links
    if (!empty($have_publish)) {
        // Auth'd the publish API.
        echo '<div class="fbbluebox">' . _("Publish enabled.")
            . '<div class="fbaction"><input type="submit" class="fbbutton" value="' . _("Disable") . '" onclick="document.prefs.actionID.value=\'revokePublish\'; return true" /></div></div>';
    } else {
        $url = $facebook->auth->getExtendedPermUrl(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM,
            Horde::url('services/facebook.php', true));
        echo '<div class="fbbluebox">'
            . sprintf(_("%s cannot set your status messages or publish other content to Facebook."), $registry->get('name'))
            . '<div class="fbaction">'
            . _("Authorize Publish:")
            . ' <a class="fbbutton" href="' . $url . '">Facebook</a></div></div>';
    }

    // Read links
    if (!empty($have_read)) {
        echo '<div class="fbbluebox">' . _("Read enabled.")
            . '<div class="fbaction"><input type="submit" class="fbbutton" value="' . _("Disable") . '" onclick="document.prefs.actionID.value=\'revokeRead\'; return true" /></div></div>';
    } else {
        $url = $facebook->auth->getExtendedPermUrl(
            Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM,
            Horde::url('services/facebook.php', true));
        echo '<div class="fbbluebox">'
            . sprintf(_("%s cannot read your stream messages."), $registry->get('name'))
            . '<div class="fbaction">'
            . _("Authorize Read:")
            . ' <a class="fbbutton" href="' . $url . '">Facebook</a></div></div>';
    }

} else {
    // No valid session information at all
    echo '<div class="fberrorbox">'
        . sprintf(_("Could not find authorization for %s to interact with your Facebook account."), $registry->get('name'))
        . '</div>';
        $url = $facebook->auth->getLoginUrl(Horde::url('services/facebook.php', true));

        if (!empty($error)) {
            echo $error;
        }
        echo sprintf(_("Login to Facebook and authorize the %s application:"), $registry->get('name'))
            . '<a class="fbbutton" href="' . $url . '">Facebook</a>';
}
