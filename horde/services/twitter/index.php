<?php
/**
 * Callback page for Twitter integration.
 *
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk.horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

function _outputError($e)
{
    global $notification, $page_output;

    Horde::log($e, 'INFO');
    $body = ($e instanceof Exception) ? $e->getMessage() : $e;
    if (($errors = json_decode($body, true)) && isset($errors['errors'])) {
        $errors = $errors['errors'];
    } else {
        $errors = array(array('message' => $body));
    }
    $notification->push(_("Error connecting to Twitter. Details have been logged for the administrator."), 'horde.error', array('sticky'));
    foreach ($errors as $error) {
        $notification->push($error['message'], 'horde.error', array('sticky'));
    }
    $page_output->header();
    $notification->notify(array('listeners' => 'status'));
    $page_output->footer();
    exit;
}

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

if (empty($conf['twitter']['enabled'])) {
    Horde::url('index.php', false, array('app' => 'horde'))->redirect();
}

$twitter = $injector->getInstance('Horde_Service_Twitter');

/* See if we have an existing token for the current user */
$token = unserialize($prefs->getValue('twitter'));

/* Check for an existing token */
if (!empty($token['key']) && !empty($token['secret'])) {
    $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
    $twitter->auth->setToken($auth_token);
}

$page_output->topbar = $page_output->sidebar = false;

/* Check to see if we have a valid token */
if (!empty($auth_token)) {
    try {
        $profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
    } catch (Horde_Service_Twitter_Exception $e) {
        _outputError($e);
    }
} elseif ($r_secret = $session->retrieve('twitter_request_secret')) {
    /* No existing auth token, maybe we are in the process of getting it? */
    try {
        $auth_token = $twitter->auth->getAccessToken($injector->getInstance('Horde_Controller_Request'), Horde_Util::getFormData('oauth_verifier'));
    } catch (Horde_Service_Twitter_Exception $e) {
        _outputError($e);
    }

    /* Clear the temporary request secret */
    $session->purge('twitter_request_secret');
    if ($auth_token === false || empty($auth_token)) {
        // We had a request secret, but something went wrong. maybe navigated
        // back here between requests?
        // fall through? Display message? What?....
        //'echo';
        //
    } else {
        /* Successfully obtained an auth token, save it to prefs etc... */
        $prefs->setValue('twitter', serialize(array('key' => $auth_token->key,
                                                    'secret' => $auth_token->secret)));
        /* Now try again */
        $twitter->auth->setToken($auth_token);
        try {
            $profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
        } catch (Horde_Service_Twitter_Exception $e) {
            _outputError($e);
        }
        if (!empty($profile->error)) {
            _outputError($profile->error);
        }
        if (!empty($profile)) {
            $page_output->header();
            echo '<script type="text/javascript">window.opener.location.reload(true);window.close();</script>';
            $page_output->footer();
            exit;
        }
    }
}
