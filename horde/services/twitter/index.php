<?php
/**
 * Callback page for Twitter integration.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael J. Rubinsky <mrubinsk.horde.org>
 * @package @horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

if (empty($conf['twitter']['enabled'])) {
    Horde::url('index.php', false, array('app' => 'horde'))->redirect();
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
    if ($inreplyTo = Horde_Util::getPost('inReplyTo')) {
        $params = array('in_reply_to_status_id', $inreplyTo);
    } else {
        $params = array();
    }
    try {
        $result = $twitter->statuses->update(Horde_Util::getPost('statusText'), $params);
        header('Content-Type: application/json');
        echo $result;
    } catch (Horde_Service_Twitter_Exception $e) {
        header('HTTP/1.1: 500');
    }
    exit;

case 'retweet':
    try {
        $result = $twitter->statuses->retweet(Horde_Util::getPost('tweetId'));
        header('Content-Type: application/json');
        echo $result;
    } catch (Horde_Service_Twitter_Exception $e) {
        header('HTTP/1.1: 500');
    }
    exit;

case 'getPage':
    try {
        $params = array();
        if ($max = Horde_Util::getPost('max_id')) {
            $params['max_id'] = $max;
        } elseif ($since = Horde_Util::getPost('since_id')) {
            $params['since_id'] = $since;
        }
        $instance = Horde_Util::getPost('i');
        if (Horde_Util::getPost('mentions', null)) {
            $stream = Horde_Serialize::unserialize($twitter->statuses->mentions($params), Horde_Serialize::JSON);
        } else {
            $params['include_entities'] = 1;
            $stream = Horde_Serialize::unserialize($twitter->statuses->homeTimeline($params), Horde_Serialize::JSON);
        }
    } catch (Horde_Service_Twitter_Exception $e) {
        //header('HTTP/1.1: 500');
        echo sprintf(_("Unable to contact Twitter. Please try again later. Error returned: %s"), $e->getMessage());
        exit;
    }
    $html = '';
    if (count($stream)) {
        $newest = $stream[0]->id_str;
    } else {
        $newest = $params['since_id'];
        $oldest = 0;
    }

    $view = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
    $view->addHelper('Tag');
    foreach ($stream as $tweet) {

        /* Don't return the max_id tweet, since we already have it */
        if (!empty($params['max_id']) && $params['max_id'] == $tweet->id_str) {
            continue;
        }

        $filter = $injector->getInstance('Horde_Core_Factory_TextFilter');

        // Links and media
        $map = array();
        $previews = array();

        foreach ($tweet->entities->urls as $link) {
            $replace = '<a href="' . $link->url . '" title="' . $link->expanded_url . '">' . htmlspecialchars($link->display_url) . '</a>';
            $map[$link->indices[0]] = array($link->indices[1], $replace);
        }
        if (!empty($tweet->entities->media)) {
            foreach ($tweet->entities->media as $picture) {
                $replace = '<a href="' . $picture->url . '" title="' . $picture->expanded_url . '">' . htmlentities($picture->display_url) . '</a>';
                $map[$picture->indices[0]] = array($picture->indices[1], $replace);
                $previews[] = ' <a href="#" onclick="return Horde[\'twitter' . $instance . '\'].showPreview(\'' . $picture->media_url . ':small\');"><img src="' . Horde_Themes::img('mime/image.png') . '" /></a>';
            }
        }
        if (!empty($tweet->entities->user_mentions)) {
            foreach ($tweet->entities->user_mentions as $user) {
                $replace = ' <a title="' . $user->name . '" href="http://twitter.com/' . $user->screen_name . '">@' . htmlentities($user->screen_name) . '</a>';
                $map[$user->indices[0]] = array($user->indices[1], $replace);
            }
        }
        if (!empty($tweet->entities->hastags)) {
            foreach ($tweet->entities->hashtags as $hashtag) {
                $replace = ' <a href="http://twitter.com/search?q=#' . urlencode($hashtag->text) . '">#' . htmlentities($hashtag->text) . '</a>';
                $map[$hashtag->indices[0]] = array($hashtag->indices[1], $replace);
            }
        }
        $body = '';
        $pos = 0;
        while ($pos <= strlen($tweet->text) -1) {
            if (!empty($map[$pos])) {
                $entity = $map[$pos];
                $body .= $entity[1];
                $pos = $entity[0];
            } else {
                $body .= substr($tweet->text, $pos, 1);
                ++$pos;
            }
        }
        foreach ($previews as $preview) {
            $body .= $preview;
        }
        $view->body = $body;

        /* If this is a retweet, use the original author's profile info */
        if (!empty($tweet->retweeted_status)) {
            $tweetObj = $tweet->retweeted_status;
        } else {
            $tweetObj = $tweet;
        }

        /* These are all referencing the *original* tweet */
        $view->profileLink = Horde::externalUrl('http://twitter.com/' . htmlspecialchars($tweetObj->user->screen_name), true);
        $view->profileImg = $tweetObj->user->profile_image_url;
        $view->authorName = htmlspecialchars($tweetObj->user->screen_name);
        $view->authorFullname = htmlspecialchars($tweetObj->user->name);
        $view->createdAt = $tweetObj->created_at;
        $view->clientText = $filter->filter($tweet->source, 'xss');
        $view->tweet = $tweet;
        $view->instanceid = $instance;
        $oldest = $tweet->id_str;
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
    try {
        $profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
    } catch (Horde_Service_Twitter_Exception $e) {}
} elseif ($r_secret = $session->retrieve('twitter_request_secret')) {
     /* No existing auth token, maybe we are in the process of getting it? */
    try {
        $auth_token = $twitter->auth->getAccessToken($GLOBALS['injector']->getInstance('Horde_Controller_Request'), Horde_Util::getFormData('oauth_verifier'));
    } catch (Horde_Service_Twitter_Exception $e) {
        echo '<div class="fberrorbox">' . sprintf(_("Error connecting to Twitter: %s Details have been logged for the administrator."), $e->getMessage()) . '</div>';
        echo '</form>';
        $page_output->footer();
        exit;
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
            echo '<div class="fberrorbox">' . sprintf(_("Error connecting to Twitter: %s Details have been logged for the administrator."), $e->getMessage()) . '</div>';
            echo '</form>';
            $page_output->footer();
            exit;
        }
        if (!empty($profile->error)) {
            echo $profile->error;
            die;
        }
        if (!empty($profile)) {
            $page_output->header();
            echo '<script type="text/javascript">window.opener.location.reload(true);window.close();</script>';
            $page_output->footer();
        }
    }
}
