<?php
/**
 * Special prefs handling for the 'facebookmanagement' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Horde
 */
class Horde_Prefs_Special_Facebook implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification, $page_output, $prefs, $registry;

        try {
            $facebook = $injector->getInstance('Horde_Service_Facebook');
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        $page_output->addThemeStylesheet('facebook.css');

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        $t->set('app_name', $registry->get('name', 'horde'));
        // Ensure we have authorized horde.
        try {
            $session_uid = $facebook->auth->getLoggedInUser();
            $fbp = unserialize($prefs->getValue('facebook'));
            $uid = $fbp['uid'];
            // Verify the userid matches the one we expect for the session
            if ($fbp['uid'] != $session_uid) {
                $haveSession = false;
            } else {
                $haveSession = true;
            }
        } catch (Horde_Service_Facebook_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            $haveSession = false;
            $prefs->setValue('facebook', serialize(array('uid' => '', 'sid' => 0)));
        }

        // Get a token generator
        $token = $GLOBALS['injector']->getInstance('Horde_Token');

        // We have a session, build the template.
        if (!empty($haveSession)) {
            try {
                $perms = $facebook->users->getAppPermissions();
                $t->set('have_publish', !empty($perms[Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM]));
                $t->set('have_read', !empty($perms[Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM]));
                $t->set('have_friends', !empty($perms[Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_ABOUT]));
            } catch (Horde_Service_Facebook_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
            }

            // Get user info. FB recommends using the FB photo and styling.
            $fql = 'SELECT first_name, last_name, status, pic_with_logo, current_location FROM user WHERE uid IN (' . $uid . ')';
            try {
                $user_info = $facebook->fql->run($fql);
            } catch (Horde_Service_Facebook_Exception $e) {
                $notification->push(_("Temporarily unable to connect with Facebook, Please try again."), 'horde.alert');
            }

            // Get a state token.
            $state = $token->get();

            // FB Perms links
            $cburl = Horde::url('services/facebook', true);
            $t->set('have_session', true);
            $t->set('user_pic_url', $user_info[0]['pic_with_logo']);
            $t->set('user_name', $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name']);

            $url = $facebook->auth->getOAuthUrl($cburl, array(Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM));
            $t->set('publish_url', $url);

            // User read perms
            $url = $facebook->auth->getOAuthUrl($cburl, array(
                Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_ABOUT,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_BIRTHDAY,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_EVENTS,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_HOMETOWN,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_LOCATION,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_PHOTOS), $state);
            $t->set('read_url', Horde::signQueryString($url));

            // Friend read perms
            $url = $facebook->auth->getOAuthUrl($cburl, array(
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_ABOUT,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_BIRTHDAY,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_HOMETOWN,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_LOCATION,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_PHOTOS), $state);
            $t->set('friends_url', Horde::signQueryString($url));

            return $t->fetch(HORDE_TEMPLATES . '/prefs/facebook.html');
        }

        /* No existing session */
        $state = $token->get();
        $t->set('have_session', false);
        $t->set('authUrl', $facebook->auth->getOAuthUrl(Horde::url('services/facebook', true), array(), $state));

        return $t->fetch(HORDE_TEMPLATES . '/prefs/facebook.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification, $prefs;

        try {
            $facebook = $injector->getInstance('Horde_Service_Facebook');
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }
        try {
            switch ($ui->vars->fbactionID) {
            case 'revokeApplication':
                $prefs->setValue(
                    'facebook',
                    array('uid' => '', 'sid' => ''));
                break;

            case 'revokePublish':
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM);
                break;

            case 'revokeRead':
                $facebook->batchBegin();
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_ABOUT);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_HOMETOWN);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_LOCATION);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_PHOTOS);
                $facebook->batchEnd();
                $facebook->batchBegin();
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_BIRTHDAY);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_EVENTS);
                $facebook->batchEnd();
                break;

            case 'revokeFriends':
                $facebook->batchBegin();
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_ABOUT);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_BIRTHDAY);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_HOMETOWN);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_LOCATION);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_PHOTOS);
                $facebook->batchEnd();
                break;
            }
        } catch (Horde_Service_Facebook_Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
        }

        return false;
    }

}
