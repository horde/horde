<?php
/**
 * Callback page for Twitter integration.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde');

if (empty($conf['twitter']['enabled'])) {
    $horde_url = Horde::url($registry->get('webroot', 'horde') . '/index.php');
    header('Location: ' . $horde_url);
    exit;
}

$twitter = $GLOBALS['injector']->getInstance('Horde_Service_Twitter');

/* See if we have an existing token for the current user */
$token = unserialize($prefs->getValue('twitter'));

/* Check for an existing token */
if (!empty($token['key']) && !empty($token['secret'])) {
    $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
    $twitter->auth->setToken($auth_token);
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

case 'retweet':
    $result = $twitter->statuses->retweet(Horde_Util::getPost('tweetId'));
    header('Content-Type: application/json');
    echo $result;
    exit;

case 'getPage':
    try {
        $params = array();
        if ($max = Horde_Util::getPost('max_id')) {
            $params['max_id'] = $max;
        } elseif ($since = Horde_Util::getPost('since_id')) {
            $params['since_id'] = $since;
        }
        
        $stream = Horde_Serialize::unserialize($twitter->statuses->homeTimeline($params), Horde_Serialize::JSON);
    } catch (Horde_Service_Twitter_Exception $e) {
        echo sprintf(_("Unable to contact Twitter. Please try again later. Error returned: %s"), $e->getMessage());
        exit;
    }
    $html = '';
    $newest = $stream[0]->id;
    foreach ($stream as $tweet) {
        /* links */
        $body = Horde_Text_Filter::filter($tweet->text, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL));
        $body = preg_replace("/[@]+([A-Za-z0-9-_]+)/", "<a href=\"http://twitter.com/\\1\" target=\"_blank\">\\0</a>", $body);

        /* If this is a retweet, use the original author's profile info */
        if (!empty($tweet->retweeted_status)) {
            $tweetObj = $tweet->retweeted_status;
        } else {
            $tweetObj = $tweet;
        }

        /* These are all referencing the *original* tweet */
        $profileLink = Horde::externalUrl('http://twitter.com/' . htmlspecialchars($tweetObj->user->screen_name), true);
        $profileImg = $tweetObj->user->profile_image_url;
        $authorName = htmlspecialchars($tweetObj->user->screen_name, ENT_COMPAT, Horde_Nls::getCharset());
        $authorFullname = htmlspecialchars($tweetObj->user->name, ENT_COMPAT, Horde_Nls::getCharset());
        $createdAt = $tweetObj->created_at;

        $appText = Horde_Text_Filter::filter($tweet->source, 'xss', array());
        $html .= '<div class="fbstreamstory">';
        $html .= '<div style="float:left;text-align:center;width:70px;margin-right:5px;">' . $profileLink
            . '<img src="' . $profileImg . '" alt="' . $authorName . '" title="' . $authorFullname . '" />'
            . '</a><div style="overflow:hidden;">' . $profileLink . $authorName . '</a></div></div>';
        $html .= ' <div class="fbstreambody">';
        $html .=  $body;
        $html .= '<div class="fbstreaminfo">' . sprintf(_("Posted %s via %s"), Horde_Date_Utils::relativeDateTime(strtotime($createdAt), $GLOBALS['prefs']->getValue('date_format')), $appText) . '</div>';

        /* Specify the retweeted status */
        if (!empty($tweet->retweeted_status)) {
            $html .= '<div class="fbstreaminfo">' . sprintf(_("Retweeted by %s"), Horde::externalUrl('http://twitter.com/' . htmlspecialchars($tweet->user->screen_name), true)) . htmlspecialchars($tweet->user->screen_name) . '</a></div>';
        }

        $html .= '<div class="fbstreaminfo">' . Horde::link('#', '', '', '', 'Horde.twitter.buildReply(\'' . $tweet->id . '\', \'' . $tweet->user->screen_name . '\', \'' . $tweet->user->name . '\')') .  _("Reply") . '</a>';
        $html .= '&nbsp;|&nbsp;' . Horde::link('#', '', '', '', 'Horde.twitter.retweet(\'' . $tweet->id . '\')') . _("Retweet") . '</a>';
        $html .= '</div><div class="clear">&nbsp;</div></div></div>';
        $oldest = $tweet->id;
    }

    $result = array(
        'o' => $oldest,
        'n' => $newest,
        'c' => $html
    );
    header('Content-Type: application/json');
    echo Horde_Serialize::serialize($result, Horde_Serialize::JSON);
    exit;
}

/* No requested action, check to see if we have a valid token */
if (!empty($auth_token)) {
    $profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
} elseif (!empty($_SESSION['twitter_request_secret'])) {
     /* No existing auth token, maybe we are in the process of getting it? */
    try {
        $auth_token = $twitter->auth->getAccessToken(new Horde_Controller_Request_Http(), $_SESSION['twitter_request_secret']);
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
    echo '<div class="fbbluebox fbboxfont" style="float:left">';
    echo '<span><img src="' . $profile->profile_image_url. '" alt="' . htmlspecialchars($profile->screen_name) . '" /></span>';
    echo '<span><div>' . htmlspecialchars($profile->name) . '</div><div>' . htmlspecialchars($profile->location) . '</div></span>';
    echo '</div><div class="clear">&nbsp;</div>';
    echo '<div class="fbbluebox fbboxfont">' . sprintf(_("%s can interact with your Twitter account."), $registry->get('name'));
    echo ' <div class="fbaction"><input type="submit" class="fbbutton" value="' . _("Disable") . '" onclick="document.prefs.actionID.value=\'revokeInfinite\'; return true" /></div></div>';
}
