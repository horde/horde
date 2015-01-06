<?php
/**
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael J Rubinsky <mrubinsk.horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

/**
 * Defines the AJAX actions used in the Twitter client.
 *
 * @author   Michael J Rubinsky <mrubinsk.horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */
class Horde_Ajax_Application_TwitterHandler extends Horde_Core_Ajax_Application_Handler
{
    /**
     * Update the twitter timeline.
     *
     * @return array  An hash containing the following keys:
     *                - o: The id of the oldest tweet
     *                - n: The id of the newest tweet
     *                - c: The HTML content
     */
    public function twitterUpdate()
    {
        global $conf;

        if (empty($conf['twitter']['enabled'])) {
            return _("Twitter not enabled.");
        }

        switch ($this->vars->actionID) {
        case 'getPage':
            return $this->_doTwitterGetPage();
        }

    }

    /**
     * Retweet a tweet. Expects the following in $this->vars:
     *   - tweetId:  The tweet id to retweet.
     *   - i:
     *
     * @return string  The HTML to render the newly retweeted tweet.
     */
    public function retweet()
    {
        $twitter = $this->_getTwitterObject();
        try {
            $tweet = json_decode($twitter->statuses->retweet($this->vars->tweetId));
            $html = $this->_buildTweet($tweet)->render('twitter_tweet');
            return $html;
        } catch (Horde_Service_Twitter_Exception $e) {
            $this->_twitterError($e);
        }
    }

    /**
     * Favorite a tweet. Expects:
     *  - tweetId:
     *
     * @return stdClass
     */
    public function favorite()
    {
        $twitter = $this->_getTwitterObject();
        try {
            return json_decode($twitter->favorites->create($this->vars->tweetId));
        } catch (Horde_Service_Twitter_Exception $e) {
            $this->_twitterError($e);
        }
    }

    /**
     * Unfavorite a tweet. Expects:
     *  - tweetId:
     */
    public function unfavorite()
    {
        $twitter = $this->_getTwitterObject();
        try {
            return json_decode($twitter->favorites->destroy($this->vars->tweetId));
        } catch (Horde_Service_Twitter_Exception $e) {
            $this->_twitterError($e);
        }
    }

    /**
     * Update twitter status. Expects:
     *  - inReplyTo:
     *  - statusText:
     *
     * @return string  The HTML text of the new  tweet.
     */
    public function updateStatus()
    {
        $twitter = $this->_getTwitterObject();
        if ($inreplyTo = $this->vars->inReplyTo) {
            $params = array('in_reply_to_status_id', $inreplyTo);
        } else {
            $params = array();
        }
        try {
            $tweet = json_decode($twitter->statuses->update($this->vars->statusText, $params));
            return $this->_buildTweet($tweet)->render('twitter_tweet');
        } catch (Horde_Service_Twitter_Exception $e) {
            $this->_twitterError($e);
        }
    }

    /**
     *
     * @return Horde_Service_Twitter
     */
    protected function _getTwitterObject()
    {
        $twitter = $GLOBALS['injector']->getInstance('Horde_Service_Twitter');
        $token = unserialize($GLOBALS['prefs']->getValue('twitter'));
        if (!empty($token['key']) && !empty($token['secret'])) {
            $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
            $twitter->auth->setToken($auth_token);
        }

        return $twitter;
    }

    /**
     * Helper method to build a view object for a tweet.
     *
     * @param  stdClass $tweet  The tweet object.
     *
     * @return Horde_View  The view object, populated with tweet data.
     */
    protected function _buildTweet($tweet)
    {
        global $injector, $registry;

        $view = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
        $view->addHelper('Tag');
        $view->ajax_uri = $registry->getServiceLink('ajax', $registry->getApp());
        $filter = $injector->getInstance('Horde_Core_Factory_TextFilter');
        $instance = $this->vars->i;

        // Links and media
        $map = $previews = array();
        foreach ($tweet->entities->urls as $link) {
            $replace = '<a target="_blank" href="' . $link->url . '" title="' . $link->expanded_url . '">' . htmlspecialchars($link->display_url) . '</a>';
            $map[$link->indices[0]] = array($link->indices[1], $replace);
        }
        if (!empty($tweet->entities->media)) {
            foreach ($tweet->entities->media as $picture) {
                $replace = '<a target="_blank" href="' . $picture->url . '" title="' . $picture->expanded_url . '">' . htmlentities($picture->display_url,  ENT_COMPAT, 'UTF-8') . '</a>';
                $map[$picture->indices[0]] = array($picture->indices[1], $replace);
                $previews[] = ' <a href="#" onclick="return Horde[\'twitter' . $instance . '\'].showPreview(\'' . $picture->media_url . ':small\');"><img src="' . Horde_Themes::img('mime/image.png') . '" /></a>';
            }
        }
        if (!empty($tweet->entities->user_mentions)) {
            foreach ($tweet->entities->user_mentions as $user) {
                $replace = ' <a target="_blank" title="' . $user->name . '" href="http://twitter.com/' . $user->screen_name . '">@' . htmlentities($user->screen_name,  ENT_COMPAT, 'UTF-8') . '</a>';
                $map[$user->indices[0]] = array($user->indices[1], $replace);
            }
        }
        if (!empty($tweet->entities->hashtags)) {
            foreach ($tweet->entities->hashtags as $hashtag) {
                $replace = ' <a target="_blank" href="http://twitter.com/search?q=#' . urlencode($hashtag->text) . '">#' . htmlentities($hashtag->text, ENT_COMPAT, 'UTF-8') . '</a>';
                $map[$hashtag->indices[0]] = array($hashtag->indices[1], $replace);
            }
        }
        $body = '';
        $pos = 0;
        while ($pos <= Horde_String::length($tweet->text) - 1) {
            if (!empty($map[$pos])) {
                $entity = $map[$pos];
                $body .= $entity[1];
                $pos = $entity[0];
            } else {
                $body .= Horde_String::substr($tweet->text, $pos, 1);
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
        $view->profileImg = $GLOBALS['browser']->usingSSLConnection() ? $tweetObj->user->profile_image_url_https : $tweetObj->user->profile_image_url;
        $view->authorName = '@' . htmlspecialchars($tweetObj->user->screen_name);
        $view->authorFullname = htmlspecialchars($tweetObj->user->name);
        $view->createdAt = $tweetObj->created_at;
        $view->clientText = $filter->filter($tweet->source, 'xss');
        $view->tweet = $tweet;
        $view->instanceid = $instance;

        return $view;
    }

    /**
     * Helper method for getting a slice of tweets.
     *
     * Expects the following in $this->vars:
     *  - max_id:
     *  - since_id:
     *  - i:
     *  - mentions:
     *
     * @return [type] [description]
     */
    protected function _doTwitterGetPage()
    {
        $twitter = $this->_getTwitterObject();
        try {
            $params = array('include_entities' => 1);
            if ($max = $this->vars->max_id) {
                $params['max_id'] = $max;
            } elseif ($since = $this->vars->since_id) {
                $params['since_id'] = $since;
            }
            if ($this->vars->mentions) {
                $stream = Horde_Serialize::unserialize($twitter->statuses->mentions($params), Horde_Serialize::JSON);
            } else {
                $stream = Horde_Serialize::unserialize($twitter->statuses->homeTimeline($params), Horde_Serialize::JSON);
            }
        } catch (Horde_Service_Twitter_Exception $e) {
            $this->_twitterError($e);
            return;
        }
        if (count($stream)) {
            $newest = $stream[0]->id_str;
        } else {
            $newest = $params['since_id'];
            $oldest = 0;
        }

        $view = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
        $view->addHelper('Tag');
        $html = '';
        foreach ($stream as $tweet) {
            /* Don't return the max_id tweet, since we already have it */
            if (!empty($params['max_id']) && $params['max_id'] == $tweet->id_str) {
                continue;
            }
            $view = $this->_buildTweet($tweet);
            $oldest = $tweet->id_str;
            $html .= $view->render('twitter_tweet');
        }

        $result = array(
            'o' => $oldest,
            'n' => $newest,
            'c' => $html
        );

        return $result;
    }

    protected function _twitterError($e)
    {
        global $notification;

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
    }

}
