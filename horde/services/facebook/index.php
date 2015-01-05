<?php
/**
 * Endpoint for Facebook integration.
 *
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

// Get the facebook client.
try {
    $facebook = $injector->getInstance('Horde_Service_Facebook');
} catch (Horde_Exception $e) {
    Horde::url('index.php', false, array('app' => 'horde'))->redirect();
}

// Url to return to after processing.
$return_url = $registry->getServiceLink('prefs', 'horde')
      ->add(array('group' => 'facebook'));

// See why we are here. A $code indicates the user has *just* authenticated the
// application and we now need to obtain the auth_token.
$vars = $injector->getInstance('Horde_Variables');
if (isset($vars->code)) {
    $token = $injector->getInstance('Horde_Token');
    if (!$token->isValid($vars->state, '', -1, false)) {
        $notification->push(_("Unable to validate the request token. Please try your request again."));
        $return_url->redirect();
    }
    try {
        $sessionKey = $facebook->auth->getSessionKey(
            $vars->code, Horde::url('services/facebook', true));
        if ($sessionKey) {
            // Store in user prefs
            $sid = $sessionKey;
            $uid = $facebook->auth->getLoggedInUser();
            $prefs->setValue('facebook', serialize(array('uid' => (string)$uid, 'sid' => $sid)));
            $notification->push(
                _("Succesfully connected your Facebook account or updated permissions."),
                'horde.success');
        } else {
            $notification->push(
                _("There was an error obtaining your Facebook session. Please try again later."),
                'horde.error');
        }
    } catch (Horde_Service_Facebook_Exception $e) {
        $notification->push(
            _("Temporarily unable to connect with Facebook, Please try again."),
            'horde.error');
    }
    $return_url->redirect();
}

if (isset($vars->error)) {
    if ($vars->error_reason == 'user_denied') {
        $notification->push(_("You have denied the requested permissions."), 'horde.warning');
    } else {
        $notification->push(_("There was an error with the requested permissions"), 'horde.error');
    }
    $return_url->redirect();
}
