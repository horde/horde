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

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

try {
    $facebook = $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
} catch (Horde_Exception $e) {
    Horde::url('index.php', false, array('app' => 'horde'))->redirect();
}

$return_url = Horde::getServiceLink('prefs', 'horde')
      ->add(array('group' => 'facebook'));

/* See why we are here. A $code indicates the user has *just* authenticated the
 * application and we now need to obtain the auth_token.*/
if ($code = Horde_Util::getFormData('code')) {
    try {
        $sessionKey = $facebook->auth->getSessionKey($code, Horde::url('services/facebook', true));
        if ($sessionKey) {
            // Remember in user prefs
            $sid =  $sessionKey;
            $uid = $facebook->auth->getLoggedInUser();
            $prefs->setValue('facebook', serialize(array('uid' => (string)$uid, 'sid' => $sid)));
            $notification->push(_("Succesfully connected your Facebook account or updated permissions."), 'horde.success');
        } else {
            $notification->push(_("There was an error obtaining your Facebook session. Please try again later."), 'horde.error');
        }
    } catch (Horde_Service_Facebook_Exception $e) {
        $notification->push(_("Temporarily unable to connect with Facebook, Please try again."), 'horde.error');
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
            $stream = $facebook->streams->get('', array(), Horde_Util::getPost('oldest'), Horde_Util::getPost('newest'), $count, $filter);
        } catch (Horde_Service_Facebook_Exception $e) {
            $html = sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), $return_url->link() . _("preferences") . '</a>');

            return $html;
        }

        /* Do we want notifications too? */
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

        /* Start parsing the posts */
        $posts = $stream['posts'];
        $profiles = array();
        $newest = $posts[0]['created_time'];
        $oldest = $posts[count($posts) -1]['created_time'];
        $instance = Horde_Util::getPost('instance');

        /* Sure would be nice if fb returned these keyed properly... */
        foreach ($stream['profiles'] as $profile) {
            $profiles[(string)$profile['id']] = $profile;
        }

        /* Build Horde_View for each story */
        $html = '';
        foreach ($posts as $post) {
                $postView = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
                $postView->actorImgUrl = $profiles[(string)$post['actor_id']]['pic_square'];
                $postView->actorProfileLink =  Horde::externalUrl($profiles[(string)$post['actor_id']]['url'], true);
                $postView->actorName = $profiles[(string)$post['actor_id']]['name'];
                $postView->message = empty($post['message']) ? '' : $post['message'];
                $postView->attachment = empty($post['attachment']) ? null : $post['attachment'];
                $postView->likes = $post['likes'];
                $postView->postId = $post['post_id'];
                $postView->postInfo = sprintf(_("Posted %s"), Horde_Date_Utils::relativeDateTime($post['created_time'], $GLOBALS['prefs']->getValue('date_format'), $GLOBALS['prefs']->getValue('twentyFour') ? "%H:%M %P" : "%I %M %P")) . ' ' . sprintf(_("Comments: %d"), $post['comments']['count']);

                /* Build the 'Likes' string. */
                if (empty($post['likes']['user_likes']) && !empty($post['likes']['can_like'])) {
                    $like = '<a href="#" onclick="Horde[\'' . $instance . '_facebook\'].addLike(\'' . $post['post_id'] . '\');return false;">' . _("Like") . '</a>';
                } else {
                    $like = '';
                }
                if (!empty($post['likes']['user_likes']) && !empty($post['likes']['count'])) {
                    $likes = sprintf(ngettext("You and %d other person likes this", "You and %d other people like this", $post['likes']['count'] - 1), $post['likes']['count'] - 1);
                } elseif (!empty($post['likes']['user_likes'])) {
                    $likes = _("You like this");
                } elseif (!empty($post['likes']['count'])) {
                    $likes = sprintf(ngettext("%d person likes this", "%d persons like this", $post['likes']['count']), $post['likes']['count']) . (!empty($like) ? ' ' . $like : '');
                } else {
                    $likes = $like;
                }
                $postView->likesInfo = $likes;
                $html .= $postView->render('facebook_story');
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
        if ($facebook->users->setStatus($status)) {
            echo htmlspecialchars($status);
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

