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
 * Defines the AJAX actions used in the Facebook client.
 *
 * @author   Michael J Rubinsky <mrubinsk.horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */
class Horde_Ajax_Application_FacebookHandler extends Horde_Core_Ajax_Application_Handler
{
    /**
     * - count:
     * - filter:
     * - oldest:
     * - newest:
     * - notifications:
     * - instance:
     *
     *
     * @return [type] [description]
     */
    public function facebookGetStream()
    {
        $facebook = $this->_getFacebookObject();
        $url = $GLOBALS['registry']->getServiceLink('prefs', 'horde')
            ->add(array('group' => 'facebook'));

        try {
            $options = array(
                'since' => $this->vars->oldest,
                'until' => $this->vars->newest
            );
            $stream = $facebook->streams->getStream($this->vars->filter, $options);
        } catch (Horde_Service_Facebook_Exception $e) {
            $html = sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), $url->link() . _("preferences") . '</a>');

            return $html;
        }

        // Notifications
        $n_html = '';
        if ($this->vars->notifications) {
            try {
                $notifications = $facebook->notifications->get();
                $n_html =  _("New Messages:")
                . ' ' . $notifications['messages']['unread']
                . ' ' . _("Pokes:") . ' ' . $notifications['pokes']['unread']
                . ' ' . _("Friend Requests:") . ' ' . count($notifications['friend_requests'])
                . ' ' . _("Event Invites:") . ' ' . count($notifications['event_invites']);
            } catch (Horde_Service_Facebook_Exception $e) {
                $html = sprintf(_("There was an error making the request: %s"), $e->getMessage());
                $html .= sprintf(_("You can also check your Facebook settings in your %s."), $url->link() . _("preferences") . '</a>');

                return $html;
            }
        }

        // Parse the posts.
        $posts = $stream->data;
        $newest = new Horde_Date($posts[0]->created_time);
        $oldest = new Horde_Date($posts[count($posts) -1]->created_time);
        $newest = $newest->timestamp();
        $oldest = $oldest->timestamp();

        // Build the view for each story.
        $html = '';
        foreach ($posts as $post) {
            $html .= $this->_buildPost($post);
        }

        return array(
            'o' => $oldest,
            'n' => $newest,
            'c' => $html,
            'nt' => $n_html
        );
    }

    /**
     * Update Facebook status.
     *  - statusText:
     *  - instance:
     *
     * @return string  HTML for the post.
     */
    public function facebookUpdateStatus()
    {
        $facebook = $this->_getFacebookObject();
        if ($results = $facebook->streams->post('me', $this->vars->statusText)) {
            return $this->_buildPost($facebook->streams->getPost($results));
        }
        return _("Status unable to be set.");
    }

    /**
     * Like a status
     *   - post_id:
     *
     * @return [type] [description]
     */
    public function facebookAddLike()
    {
        $facebook = $this->_getFacebookObject();
        if ($facebook->streams->addLike($this->vars->post_id)) {
            $fql = 'SELECT post_id, likes FROM stream WHERE post_id="' . $this->vars->post_id . '"';
            try {
                $post = $facebook->fql->run($fql);
            } catch (Horde_Service_Facebook_Exception $e) {
                // Already set the like by the time we are here, so just indicate
                // that.
                return _("You like this");
            }

            $post = current($post);
            $likes = $post['likes'];
            if ($likes['count'] > 1) {
                $html = sprintf(ngettext("You and %d other person likes this", "You and %d other people like this", $likes['count'] - 1), $likes['count'] - 1);
            } else {
                $html = _("You like this");
            }
            return $html;
        }

        return _("Unable to set like.");
    }

    protected function _getFacebookObject()
    {
        try {
            return $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
        } catch (Horde_Exception $e) {
            $this->_error($e);
        }
    }

    /**
     * Build the Horde_View object for a FB Post.
     *
     * @param stdClass $post  The Facebook post object.
     *
     * @return string  The HTML to render the $post.
     */
    protected function _buildPost($post)
    {
        global $prefs;

        $facebook = $this->_getFacebookObject();
        $instance = $this->vars->instance;
        $uid = $facebook->auth->getLoggedInUser();

        $postView = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
        $postView->actorImgUrl = $facebook->users->getThumbnail($post->from->id);
        $postView->actorProfileLink = Horde::externalUrl(
            $facebook->users->getProfileLink($post->from->id),
            true
        );
        $postView->actorName = $post->from->name;
        $postView->message = empty($post->message) ? '' : $post->message;
        $postView->likes = $post->likes->count;
        $postView->postId = $post->id;
        $postView->privacy = $post->privacy;
        $postView->postInfo = sprintf(
            _("Posted %s"),
            Horde_Date_Utils::relativeDateTime(
                $post->created_time,
                $prefs->getValue('date_format'),
                $prefs->getValue('twentyFour') ? "%H:%M %P" : "%I %M %P"))
            . ' ' . sprintf(_("Comments: %d"), $post->comments->count);

        $postView->type = $post->type;
        if (!empty($post->picture)) {
            $postView->attachment = new stdClass();
            $postView->attachment->image = $post->picture;
            if (!empty($post->link)) {
                $postView->attachment->link = Horde::externalUrl($post->link, true);
            }
            if (!empty($post->name)) {
                $postView->attachment->name = $post->name;
            }
            if (!empty($post->caption)) {
                $postView->attachment->caption = $post->caption;
            }
            if (!empty($post->icon)) {
                $postView->icon = $post->icon;
            }
            if (!empty($post->description)) {
                $postView->attachment->description = $post->description;
            }
        }
        if (!empty($post->place)) {
            $postView->place = array(
                'name' => $post->place->name,
                'link' => Horde::externalUrl($facebook->getFacebookUrl() . '/' . $post->place->id, true),
                'location' => $post->place->location
            );
        }
        if (!empty($post->with_tags)) {
            $postView->with = array();
            foreach ($post->with_tags->data as $with) {
                $postView->with[] = array(
                    'name' => $with->name,
                    'link' => Horde::externalUrl($facebook->users->getProfileLink($with->id), true)
                );
            }
        }

        // Actions
        $like = '';
        foreach ($post->actions as $availableAction) {
            if ($availableAction->name == 'Like') {
                $like = '<a href="#" onclick="Horde[\'' . $instance . '_facebook\'].addLike(\'' . $post->id . '\');return false;">' . _("Like") . '</a>';
            }
        }
        $likes = '';
        if ($post->likes->count) {
            foreach ($post->likes->data as $likeData) {
                if ($likeData->id == $uid &&
                    $post->likes->count > 1) {
                    $likes = sprintf(ngettext("You and %d other person likes this", "You and %d other people like this", $post->likes->count - 1), $post->likes->count - 1);
                    break;
                } elseif ($likeData->id == $uid) {
                    $likes = _("You like this");
                    break;
                }
            }
            if (empty($likes)) {
                $likes = sprintf(ngettext("%d person likes this", "%d persons like this", $post->likes->count), $post->likes->count) . (!empty($like) ? ' ' . $like : '');
            } else {
                $likes = $likes . !empty($like) ? ' ' . $like : '';
            }
        } else {
            $likes = $like;
        }
        $postView->likesInfo = $likes;

        return $postView->render('facebook_story');
    }

    protected function _error($e)
    {
        global $notification;

        Horde::log($e, 'INFO');
        $body = ($e instanceof Exception) ? $e->getMessage() : $e;
        if (($errors = json_decode($body, true)) && isset($errors['errors'])) {
            $errors = $errors['errors'];
        } else {
            $errors = array(array('message' => $body));
        }
        $notification->push(_("Error connecting to Facebook. Details have been logged for the administrator."), 'horde.error', array('sticky'));
        foreach ($errors as $error) {
            $notification->push($error['message'], 'horde.error', array('sticky'));
        }
    }

}
