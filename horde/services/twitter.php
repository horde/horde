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
         $view = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
         $view->addHelper('Tag');

         /* links */
        $body = Horde_Text_Filter::filter($tweet->text, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL));
        $view->body = preg_replace("/[@]+([A-Za-z0-9-_]+)/", "<a href=\"http://twitter.com/\\1\" target=\"_blank\">\\0</a>", $body);

        /* If this is a retweet, use the original author's profile info */
        if (!empty($tweet->retweeted_status)) {
            $tweetObj = $tweet->retweeted_status;
        } else {
            $tweetObj = $tweet;
        }

        /* These are all referencing the *original* tweet */
        $view->profileLink = Horde::externalUrl('http://twitter.com/' . htmlspecialchars($tweetObj->user->screen_name), true);
        $view->profileImg = $tweetObj->user->profile_image_url;
        $view->authorName = htmlspecialchars($tweetObj->user->screen_name, ENT_COMPAT, $GLOBALS['registry']->getCharset());
        $view->authorFullname = htmlspecialchars($tweetObj->user->name, ENT_COMPAT, $GLOBALS['registry']->getCharset());
        $view->createdAt = $tweetObj->created_at;
        $view->clientText = Horde_Text_Filter::filter($tweet->source, 'xss', array());
        $view->tweet = $tweet;
        $oldest = $tweet->id;
        $html .= $view->render('twitter_tweet');
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
