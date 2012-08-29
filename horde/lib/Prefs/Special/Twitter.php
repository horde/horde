<?php
/**
 * Special prefs handling for the 'twittermanagement' preference.
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
class Horde_Prefs_Special_Twitter implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $prefs, $registry, $session;

        $twitter = $injector->getInstance('Horde_Service_Twitter');
        $token = unserialize($prefs->getValue('twitter'));

        /* Check for an existing token */
        if (!empty($token['key']) && !empty($token['secret'])) {
            $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
            $twitter->auth->setToken($auth_token);
        }
        try {
            $profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
        } catch (Horde_Service_Twitter_Exception $e) {}

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        /* Could not find a valid auth token, and we are not in the process of getting one */
        if (empty($profile)) {
            try {
                $results = $twitter->auth->getRequestToken();
            } catch (Horde_Service_Twitter_Exception $e) {
                $t->set('error', sprintf(_("Error connecting to Twitter: %s Details have been logged for the administrator."), $e->getMessage()), true);
                exit;
            }
            $session->store($results->secret, false, 'twitter_request_secret');
            $t->set('appname', $registry->get('name'));
            $t->set('link', Horde::link(Horde::externalUrl($twitter->auth->getUserAuthorizationUrl($results), false), '', 'button', '', 'openTwitterWindow(); return false;') . 'Twitter</a>');
            $t->set('popupjs', Horde::popupJs(Horde::externalUrl($twitter->auth->getUserAuthorizationUrl($results), false), array('urlencode' => true)));
        } else {
            $t->set('haveSession', true, true);
            $t->set('profile_image_url', $profile->profile_image_url);
            $t->set('profile_screenname', htmlspecialchars($profile->screen_name));
            $t->set('profile_name', htmlspecialchars($profile->name));
            $t->set('profile_location', htmlspecialchars($profile->location));
            $t->set('appname', $registry->get('name'));
        }

        return $t->fetch(HORDE_TEMPLATES . '/prefs/twitter.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $prefs;

        $twitter = $injector->getInstance('Horde_Service_Twitter');
        $token = unserialize($prefs->getValue('twitter'));

        /* Check for an existing token */
        if (!empty($token['key']) && !empty($token['secret'])) {
            $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
            $twitter->auth->setToken($auth_token);
        }

        switch ($ui->vars->twitteractionID) {
        case 'revokeInfinite':
            $twitter->account->endSession();
            $prefs->setValue('twitter', 'a:0:{}');
            echo '<script type="text/javascript">location.href="' . Horde::url('services/prefs.php', true)->add(array('group' => 'twitter', 'app'  => 'horde')) . '";</script>';
            exit;
        }

        return false;
    }

}
