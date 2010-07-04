<?php
if (!empty($GLOBALS['conf']['facebook']['enabled'])) {
    $block_name = _("My Facebook Stream");
}

/**
 * Block for displaying the current user's Facebook stream, with the ability to
 * filter it using the same Facebook filters available on facebook.com.  Also
 * provides ability to update the current user's status.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_Horde_fb_stream extends Horde_Block {

    /**
     * Whether this block has changing content.
     */
    var $updateable = true;

    /**
     * @var string
     */
    var $_app = 'horde';

    /**
     * @var Horde_Service_Facebook
     */
    private $_facebook;

    /**
     * @var array holding user's facebook settings
     */
    private $_fbp;

    /**
     * Const'r - instantiate the facebook client.
     *
     * @see Horde_Block
     */
    public function __construct($params = array(), $row = null, $col = null)
    {
        $GLOBALS['injector']->addBinder('Facebook', new Horde_Core_Binder_Facebook());
        try {
            $this->_facebook = $GLOBALS['injector']->getInstance('Facebook');
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }
        $this->_fbp = unserialize($GLOBALS['prefs']->getValue('facebook'));

        if (!empty($this->_fbp['sid'])) {
            $this->_facebook->auth->setUser($this->_fbp['uid'], $this->_fbp['sid'], 0);
        }
        parent::__construct($params, $row, $col);
    }

    function _params()
    {
        $filters = array();

        if (!empty($this->_fbp['sid'])) {
            // Use a raw fql query to reduce the amount of data that is
            // returned.
            $fql = 'SELECT filter_key, name FROM stream_filter WHERE uid="'
                . $this->_fbp['uid'] . '"';

            try {
                $stream_filters = $this->_facebook->fql->run($fql);
                foreach ($stream_filters as $filter) {
                    $filters[$filter['filter_key']] = $filter['name'];
                }
            } catch (Horde_Service_Facebook_Exception $e) {}
        }

        return array(
            'filter' => array('type' => 'enum',
                              'name' => _("Filter"),
                              'default' => 'nf',
                              'values' => $filters),
            'count' => array('type' => 'int',
                            'name' => _("Maximum number of entries to display"),
                            'default' => ''),
            'notifications' => array('type' => 'boolean',
                                     'name' => _("Show notifications"),
                                     'default' => true),
            'height' => array(
                 'name' => _("Height of map (width automatically adjusts to block)"),
                 'type' => 'int',
                 'default' => 250),
            );
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return Horde::externalUrl('http://facebook.com', true) . _("My Facebook Stream") . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content.
     */
    function _content()
    {
        $csslink = $GLOBALS['registry']->get('themesuri', 'horde') . '/facebook.css';
        $endpoint = Horde::url('services/facebook.php', true);
        $spinner = '$(\'loading\')';
        $html = <<<EOF
        <script type="text/javascript">
        function updateStatus(statusText, inputNode)
        {
            {$spinner}.toggle();
            params = new Object();
            params.actionID = 'updateStatus';
            params.statusText = statusText;
            new Ajax.Updater({success:'currentStatus'},
                 '$endpoint',
                 {
                     method: 'post',
                     parameters: params,
                     onComplete: function() {inputNode.value = '';{$spinner}.toggle()},
                     onFailure: function() {{$spinner}.toggle()}
                 }
           );
        }
        function addLike(post_id)
        {
            {$spinner}.toggle();
            params = new Object();
            params.actionID = 'addLike';
            params.post_id = post_id;
            new Ajax.Updater({success:'fb' + post_id},
                 '$endpoint',
                 {
                     method: 'post',
                     parameters: params,
                     onComplete: function() {{$spinner}.toggle()},
                     onFailure: function() {{$spinner}.toggle()}
                 }
           );

           return false;
        }

        </script>
EOF;

        $facebook = $this->_facebook;
        $fbp = $this->_fbp;

        // If no prefs exist -------
        if (empty($fbp['sid'])) {
            return sprintf(_("You have not properly connected your Facebook account with Horde. You should check your Facebook settings in your %s."), Horde::getServiceLink('options', 'horde')->add('group', 'facebook')->link() . _("preferences") . '</a>');
        }

        // Get stream
        try {
            $stream = $facebook->streams->get('', array(), '', '', $this->_params['count'], $this->_params['filter']);
        } catch (Horde_Service_Facebook_Exception $e) {
            $html .= sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), Horde::getServiceLink('options', 'horde')->add('group', 'facebook')->link() . _("preferences") . '</a>');
            return $html;
        }

        //Do we want notifications too?
        if (!empty($this->_params['notifications'])) {
            try {
                $notifications = $facebook->notifications->get();
            } catch (Horde_Service_Facebook_Exception $e) {
                $html .= sprintf(_("There was an error making the request: %s"), $e->getMessage());
                $html .= sprintf(_("You can also check your Facebook settings in your %s."), Horde::getServiceLink('options', 'horde')->add('group', 'facebook')->link() . _("preferences") . '</a>');
                return $html;
            }
        }

        $posts = $stream['posts'];
        $profiles = array();
        // Sure would be nice if fb returned these keyed properly...
        foreach ($stream['profiles'] as $profile) {
            $profiles[(string)$profile['id']] = $profile;
        }

        // Bring in the Facebook CSS
        $html .= '<link href="' . $csslink . '" rel="stylesheet" type="text/css" />';
        $html .= '<div style="float:left;padding-left: 8px;padding-right:8px;">';

        // User's current status and input box to change it.
        $fql = 'SELECT first_name, last_name, status, pic_square_with_logo from user where uid=' . $fbp['uid'] . ' LIMIT 1';
        try {
            $status = $facebook->fql->run($fql);
        } catch (Horde_Service_Facebook_Exception $e) {
            $html .= sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), Horde::getServiceLink('options', 'horde')->add('group', 'facebook')->link() . _("preferences") . '</a>');
            return $html;
        }
        $status = array_pop($status);
        if (empty($status['status']['message'])) {
            $status['status']['message'] = _("What's on your mind?");
            $class = 'fbemptystatus';
        } else {
            $class = '';
        }

        if (!empty($notifications)) {
            $html .= '<div class="fbinfobox">' . _("New Messages:")
                . ' ' . $notifications['messages']['unread']
                . ' ' . _("Pokes:") . ' ' . $notifications['pokes']['unread']
                . ' ' . _("Friend Requests:") . ' ' . count($notifications['friend_requests'])
                . ' ' . _("Event Invites:") . ' ' . count($notifications['event_invites']) . '</div>';
        }

        $html .= '<div class="fbgreybox fbboxfont"><img style="float:left;" src="' . $status['pic_square_with_logo'] . '" /><div id="currentStatus" class="' . $class . '" style="margin-left:55px;">' . $status['status']['message'] . '</div>';
        try {
            //TODO: We could probably cache this perm somehow - maybe in the session?
            if ($facebook->users->hasAppPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM)) {
                $html .= '<input style="width:100%;margin-top:4px;margin-bottom:4px;" type="text" class="fbinput" id="newStatus" name="newStatus" />'
                . '<div><a class="button" onclick="updateStatus($F(\'newStatus\'), $(\'newStatus\'));" href="#">' . _("Update") . '</a></div>'
                . Horde::img('loading.gif', '', array('id' => 'loading', 'style' => 'display:none;'));
            }
        } catch (Horde_Service_Facebook_Exception $e) {
            $html .= sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), Horde::link($endpoint) . _("preferences") . '</a>');
            return $html;
        }
        $html .= '</div>'; // Close the fbgreybox node that wraps the status
        // Build the stream feed.
        $html .= '<div style="height:' . (empty($this->_params['height']) ? 300 : $this->_params['height']) . 'px;overflow-y:auto;">';
        foreach ($posts as $post) {
            $html .= '<div class="fbstreamstory">';
            $html .= '<div class="fbstreampic"><img style="float:left;" src="' . $profiles[(string)$post['actor_id']]['pic_square'] . '" /></div>';

            // fbstreambody wraps all content except the actor's image. This
            // displays the actor's name and any message he/she added.
            $html .= ' <div class="fbstreambody">'
                . Horde::externalUrl($profiles[(string)$post['actor_id']]['url'], true)
                . $profiles[(string)$post['actor_id']]['name'] . '</a> '
                . (empty($post['message']) ? '' : $post['message']);

            // Parse any attachments
            if (!empty($post['attachment'])) {
                $html .= '<div class="fbattachment">';
                if (!empty($post['attachment']['media']) && count($post['attachment']['media'])) {
                    $html .= '<div class="fbmedia' . (count($post['attachment']['media']) > 1 ? ' fbmediawide' : '') . '">';
                    // Decide what mediaitem css class to use for padding and
                    // display the media items.
                    $multiple = false;
                    $single = count($post['attachment']['media']) == 1;
                    foreach ($post['attachment']['media'] as $item) {
                        $link = Horde::externalUrl($item['href'], true);
                        $img = '<img src="' . htmlspecialchars($item['src']) . '" />';
                        if ($single) {
                            $html .= '<div class="fbmediaitem fbmediaitemsingle">' . $link . $img . '</a></div>';
                        } else {
                            $html .= '<div class="fbmediaitem' . ($multiple ? ' fbmediaitemmultiple' : '') . '">' . $link . $img . '</a></div>';
                            $multiple = true;
                        }
                    }
                    $html .= '</div>';  // Close the fbmedia node
                }

                // Attachment properties.
                if (!empty($post['attachment']['name'])) {
                    $link = Horde::externalUrl($post['attachment']['href'], true);
                    $html .= '<div class="fbattachmenttitle">' . $link . $post['attachment']['name'] . '</a></div>';
                }
                if (!empty($post['attachment']['caption'])) {
                    $html .= '<div class="fbattachmentcaption">' . $post['attachment']['caption'] . '</div>';
                }
                if (!empty($post['attachment']['description'])) {
                    $html .= '<div class="fbattachmentcopy">' . $post['attachment']['description'] . '</div>';
                }

                $html .= '</div>'; // Close the fbattachemnt node.
            }

            // Build the likes string to display.
            if (empty($post['likes']['user_likes']) && !empty($post['likes']['can_like'])) {
                $like = '<a href="#" onclick="return addLike(\'' . $post['post_id'] . '\');">' . _("Like") . '</a>';
            } else {
                $like = '';
            }
            $html .= '<div class="fbstreaminfo">' . sprintf(_("Posted %s"), Horde_Date_Utils::relativeDateTime($post['created_time'], $GLOBALS['prefs']->getValue('date_format'), $GLOBALS['prefs']->getValue('twentyFour') ? "%H:%M %P" : "%I %M %P")) . ' ' . sprintf(_("Comments: %d"), $post['comments']['count']) . '</div>';
            $html .= '<div class="fbstreaminfo" id="fb' . $post['post_id'] . '">';
            if (!empty($post['likes']['user_likes']) && !empty($post['likes']['count'])) {
                $html .= sprintf(ngettext("You and %d other person likes this", "You and %d other people like this", $post['likes']['count'] - 1), $post['likes']['count'] - 1);
            } elseif (!empty($post['likes']['user_likes'])) {
                $html .= _("You like this");
            } elseif (!empty($post['likes']['count'])) {
                $html .= sprintf(ngettext("%d person likes this", "%d persons like this", $post['likes']['count']), $post['likes']['count']) . (!empty($like) ? ' ' . $like : '');
            } elseif (!empty($like)) {
                $html .= $like;
            }
            $html .= '</div>'; // Close the fbstreaminfo node that wraps the like
            $html .= '</div></div>'; // Close the fbstreambody, fbstreamstory nodes
            $html .= '<div class="fbcontentdivider">&nbsp;</div>';
        }
        $html .= '</div></div>'; // fbbody end

        return $html;
    }

}
