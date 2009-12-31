<?php
/**
 * Callback page for Twitter integration.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
new Horde_Application();

if (empty($GLOBALS['conf']['twitter']['enabled'])) {
    $horde_url = Horde::url($registry->get('webroot', 'horde') . '/index.php');
    header('Location: ' . $horde_url);
    exit;
}

/* Create a cahce object for the twitter client */
$cache = Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'],
                                Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));

/* Using OAuth or Http Basic? */
if (!empty($GLOBALS['conf']['twitter']['key']) && !empty($GLOBALS['conf']['twitter']['secret'])) {
    /* Keys - these are obtained when registering for the service */
    $consumer_key = $GLOBALS['conf']['twitter']['key'];
    $consumer_secret = $GLOBALS['conf']['twitter']['secret'];

    /* Parameters required for the Horde_Oauth_Consumer */
    $params = array('key' => $consumer_key,
                    'secret' => $consumer_secret,
                    'requestTokenUrl' => Horde_Service_Twitter::REQUEST_TOKEN_URL,
                    'authorizeTokenUrl' => Horde_Service_Twitter::USER_AUTHORIZE_URL,
                    'accessTokenUrl' => Horde_Service_Twitter::ACCESS_TOKEN_URL,
                    'signatureMethod' => new Horde_Oauth_SignatureMethod_HmacSha1());

    /* Create the Consumer */
    $oauth = new Horde_Oauth_Consumer($params);

    /* Create the Twitter client */
    $twitter = new Horde_Service_Twitter(array('oauth' => $oauth, 'cache' => $cache));

    /* See if we have an existing token for the current user */
    $token = unserialize($prefs->getValue('twitter'));

    /* Check for an existing token */
    if (!empty($token['key']) && !empty($token['secret'])) {
        $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
        $twitter->auth->setToken($auth_token);
    }
} elseif (!empty($_SESSION['horde']['twitterblock']['username']) && !empty($_SESSION['horde']['twitterblock']['password'])) {
    $twitter = new Horde_Service_Twitter(array('username' => $_SESSION['horde']['twitterblock']['username'],
                                               'password' => $_SESSION['horde']['twitterblock']['password'],
                                               'cache' => $cache));
}

/* See if we are here for any actions */
$action = Horde_Util::getPost('actionID');
switch ($action) {
case 'revokeInfinite':
    $twitter->account->endSession();
    $prefs->setValue('twitter', 'a:0:{}');
    echo '<script type="text/javascript">location.href="' . Horde::selfUrl(false) . '?app=horde&nomenu=0&group=twitter";</script>';
    exit;
case 'updateStatus':
    $result = $twitter->statuses->update(Horde_Util::getPost('statusText'), Horde_Util::getPost('inReplyTo', ''));
    header('Content-Type: application/json');
    echo $result;
    exit;
}

/* No requested action, check to see if we have a valid token */
if (!empty($auth_token)) {
    $profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
} elseif (!empty($_SESSION['twitter_request_secret'])) {
     /* No existing auth token, maybe we are in the process of getting it? */
    try {
        $auth_token = $twitter->auth->getAccessToken(new Horde_Controller_Request_Http(),
                                                     $_SESSION['twitter_request_secret']);
    } catch (Horde_Service_Twitter_Exception $e) {
        echo '<div class="fberrorbox">' . sprintf(_("Error connecting to Twitter: %s Details have been logged for the administrator."), $e->getMessage()) . '</div>';
        echo '</form>';
        require HORDE_TEMPLATES . '/common-footer.inc';
        exit;
    }
    /* Clear the temporary request secret */
    $_SESSION['twitter_request_secret'] = '';
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
            echo '<div class="fberrorbox">' . sprintf(_("Error connecting to Twitter: %s Details have been logged for the administrator."), $e->getMessage()) . '</div>';
            echo '</form>';
            require HORDE_TEMPLATES . '/common-footer.inc';
            exit;
        }
        if (!empty($profile->error)) {
            echo $profile->error;
            die;
        }
        if (!empty($profile)) {
            require HORDE_TEMPLATES . '/common-header.inc';
            echo '<script type="text/javascript">window.opener.location.reload(true);window.close();</script>';
        }
    }
}

// Start rendering the prefs page
$chunk = Horde_Util::nonInputVar('chunk');
Horde_Prefs_Ui::generateHeader('horde', null, 'twitter', $chunk);
$csslink = $GLOBALS['registry']->get('themesuri', 'horde') . '/facebook.css';
echo '<link href="' . $csslink . '" rel="stylesheet" type="text/css" />';

/* Could not find a valid auth token, and we are not in the process of getting one */
if (empty($profile)) {
    try {
        $results = $twitter->auth->getRequestToken();
    } catch (Horde_Service_Twitter_Exception $e) {
        echo '<div class="fberrorbox">' . sprintf(_("Error connecting to Twitter: %s Details have been logged for the administrator."), $e->getMessage()) . '</div>';
        echo '</form>';
        require HORDE_TEMPLATES . '/common-footer.inc';
        exit;
    }
    $_SESSION['twitter_request_secret'] = $results->secret;
    echo '<div class="fberrorbox">' . sprintf(_("Could not find authorization for %s to interact with your Twitter account."), $registry->get('name')) . '</div>';
    $link = Horde::link(Horde::externalUrl($twitter->auth->getUserAuthorizationUrl($results), false), '', 'fbbutton', '', 'openTwitterWindow(); return false;');
    echo '<script type="text/javascript">function openTwitterWindow() {' . Horde::popupJs(Horde::externalUrl($twitter->auth->getUserAuthorizationUrl($results), false), array('urlencode' => true)) . '}</script>';
    echo sprintf(_("Login to Twitter and authorize the %s application:"), $registry->get('name')) . $link . 'Twitter</a>';
} else {
    /* We know we have a good Twitter token here, so check for any actions... */
    echo '<div class="fbbluebox" style="float:left">';
    echo '<span><img src="' . $profile->profile_image_url. '" alt="' . htmlspecialchars($profile->screen_name) . '" /></span>';
    echo '<span><div>' . htmlspecialchars($profile->name) . '</div><div>' . htmlspecialchars($profile->location) . '</div></span>';
    echo '</div><div class="clear">&nbsp;</div>';
    echo '<div class="fbbluebox">' . sprintf(_("%s can interact with your Twitter account."), $registry->get('name'));
    echo ' <div class="fbaction"><input type="submit" class="fbbutton" value="' . _("Disable") . '" onclick="document.prefs.actionID.value=\'revokeInfinite\'; return true" /></div></div>';
}

echo '</form>';
require HORDE_TEMPLATES . '/common-footer.inc';
