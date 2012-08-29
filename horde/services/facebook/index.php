<?php
/**
 * Endpoint for Facebook integration.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

function build_post($post, $uid, $instance)
{
    global $facebook;

    $postView = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
    $postView->actorImgUrl = $facebook->users->getThumbnail($post->from->id);
    $postView->actorProfileLink = Horde::externalUrl(
        $facebook->users->getProfileLink($post->from->id), true);
    $postView->actorName = $post->from->name;
    $postView->message = empty($post->message) ? '' : $post->message;
    $postView->likes = $post->likes->count;
    $postView->postId = $post->id;
    $postView->privacy = $post->privacy;
    $postView->postInfo = sprintf(
        _("Posted %s"),
        Horde_Date_Utils::relativeDateTime(
            $post->created_time,
            $GLOBALS['prefs']->getValue('date_format'),
            $GLOBALS['prefs']->getValue('twentyFour') ? "%H:%M %P" : "%I %M %P"))
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

// Get the facebook client.
try {
    $facebook = $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
} catch (Horde_Exception $e) {
    Horde::url('index.php', false, array('app' => 'horde'))->redirect();
}

// Url to return to after processing.
$return_url = $registry->getServiceLink('prefs', 'horde')
      ->add(array('group' => 'facebook'));

// See why we are here. A $code indicates the user has *just* authenticated the
// application and we now need to obtain the auth_token.
if ($code = Horde_Util::getFormData('code')) {
    $state = Horde_Util::getFormData('state');
    $token = $injector->getInstance('Horde_Token');
    if (!$token->isValid($state, '', -1, false)) {
        $notification->push(_("Unable to validate the request token. Please try your request again."));
        $return_url->redirect();
    }
    try {
        $sessionKey = $facebook->auth->getSessionKey(
            $code, Horde::url('services/facebook', true));
        if ($sessionKey) {
            // Store in user prefs
            $sid =  $sessionKey;
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

if ($error = Horde_Util::getFormData('error')) {
    if (Horde_Util::getFormData('error_reason') == 'user_denied') {
        $notification->push(_("You have denied the requested permissions."), 'horde.warning');
    } else {
        $notification->push(_("There was an error with the requested permissions"), 'horde.error');
    }
    $return_url->redirect();
}

if ($action = Horde_Util::getPost('actionID')) {
    switch ($action) {
    case 'getStream':
        try {
            $count = Horde_Util::getPost('count');
            $filter = Horde_Util::getPost('filter');
            $options = array(
                'since' => Horde_Util::getPost('oldest'),
                'until' => Horde_Util::getPost('newest')
            );
            $stream = $facebook->streams->getStream($filter, $options);
        } catch (Horde_Service_Facebook_Exception $e) {
            $html = sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), $return_url->link() . _("preferences") . '</a>');

            return $html;
        }

        // Notifications
        $n_html = '';
        if (Horde_Util::getPost('notifications')) {
            try {
                $notifications = $facebook->notifications->get();
                $n_html =  _("New Messages:")
                . ' ' . $notifications['messages']['unread']
                . ' ' . _("Pokes:") . ' ' . $notifications['pokes']['unread']
                . ' ' . _("Friend Requests:") . ' ' . count($notifications['friend_requests'])
                . ' ' . _("Event Invites:") . ' ' . count($notifications['event_invites']);
            } catch (Horde_Service_Facebook_Exception $e) {
                $html = sprintf(_("There was an error making the request: %s"), $e->getMessage());
                $html .= sprintf(_("You can also check your Facebook settings in your %s."), $return_url->link() . _("preferences") . '</a>');

                return $html;
            }
        }

        // Parse the posts.
        $posts = $stream->data;
        $profiles = array();
        $newest = new Horde_Date($posts[0]->created_time);
        $oldest = new Horde_Date($posts[count($posts) -1]->created_time);
        $newest = $newest->timestamp();
        $oldest = $oldest->timestamp();
        $instance = Horde_Util::getPost('instance');

        // Build the view for each story.
        $html = '';
        $uid = $facebook->auth->getLoggedInUser();
        foreach ($posts as $post) {
            $html .= build_post($post, $uid, $instance);
        }

        /* Build response structure */
        $result = array(
            'o' => $oldest,
            'n' => $newest,
            'c' => $html,
            'nt' => $n_html
        );
        header('Content-Type: application/json');
        echo Horde_Serialize::serialize($result, Horde_Serialize::JSON);
        exit;

    case 'updateStatus':
        // This is an AJAX action, so just echo the result and return.
        $status = Horde_Util::getPost('statusText');
        if ($results = $facebook->streams->post('me', $status)) {
            $uid = $facebook->auth->getLoggedInUser();
            echo build_post($facebook->streams->getPost($results), $uid, Horde_Util::getPost('instance'));
            exit;
        } else {
            echo _("Status unable to be set.");
        }
        exit;

    case 'addLike':
        $id = Horde_Util::getPost('post_id');
        if ($facebook->streams->addLike($id)) {
            $fql = 'SELECT post_id, likes FROM stream WHERE post_id="' . $id . '"';
            try {
                $post = $facebook->fql->run($fql);
            } catch (Horde_Service_Facebook_Exception $e) {
                // Already set the like by the time we are here, so just indicate
                // that.
                echo _("You like this");
                exit;
            }
            $post = current($post);
            $likes = $post['likes'];
            if ($likes['count'] > 1) {
                $html = sprintf(ngettext("You and %d other person likes this", "You and %d other people like this", $likes['count'] - 1), $likes['count'] - 1);
            } else {
                $html = _("You like this");
            }
            echo $html;
        } else {
            echo _("Unable to set like.");
        }
        exit;
    }
}

