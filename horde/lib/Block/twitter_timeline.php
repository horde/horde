<?php
/**
 * The Horde_Block_twitter_timeline class provides a bare-bones twitter client
 * as a horde block.
 *
 * Still @TODO:
 *  - configure block to show friendTimeline, specific user, public timeline,
 *    'mentions' for current user etc..
 *  - implement retweet (waiting on twitter API to turn this on).
 *  - keep track of call limits and either dynamically alter update time or
 *    at least provide feedback to user.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Horde_Block
 */

if (!empty($GLOBALS['conf']['twitter']['enabled'])) {
    $block_name = _("Twitter Timeline");
}

class Horde_Block_Horde_twitter_timeline extends Horde_Block
{
    /**
     * Whether this block has changing content. We dissallow autoupdating for
     * sites not using OAuth since the per-hour limits are then based on IP
     * address.
     *
     */
    var $updateable = true;

    /**
     *
     * @ Horde_Service_Twitter
     */
    var $_twitter;

    /**
     * Twitter profile information returned from verify_credentials
     *
     * @var Object
     */
    var $_profile;

    /**
     *
     * @var string
     */
    var $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        try {
            $twitter = $this->_getTwitterObject();
        } catch (Horde_Exception $e) {
            return _("Twitter Timeline");
        }
        try {
            $this->_profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
            if (!empty($this->_profile)) {
                $username = $this->_profile->screen_name;
                return sprintf(_("Twitter Timeline for %s"), $username);
            }
        } catch (Horde_Service_Twitter_Exception $e) {
            if (empty($this->_params['username'])) {
                return _("Twitter Timeline");
            }
        }

        return sprintf(_("Twitter Timeline"));
    }

    /**
     * @TODO verify if we have oauth support - if so, don't show these...
     * @see framework/Block/Horde_Block#_params()
     */
    function _params()
    {
        if (empty($GLOBALS['conf']['twitter']['key']) &&
            empty($GLOBALS['conf']['twitter']['secret'])) {

            return array(
                'username' => array(
                    'type' => 'text',
                    'name' => _("Twitter Username"),
                    'required' => true,
                ),
                'password' => array(
                    'type' => 'password',
                    'name' => _("Twitter Password"),
                    'required' => true,
                )
            );
        } else {
            return null;
        }
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        global $conf;

        try {
            $twitter = $this->_getTwitterObject();
        }  catch (Horde_Exception $e) {
            return sprintf(_("There was an error contacting Twitter: %s"), $e->getMessage());
        }

        /* Get a unique ID in case we have multiple Twitter blocks. */
        $instance = md5(mt_rand());

        /* Fetch the stream data */
        try {
            $stream = Horde_Serialize::unserialize($twitter->statuses->friendsTimeline(), Horde_Serialize::JSON);
        } catch (Horde_Service_Twitter_Exception $e) {
            $msg = Horde_Serialize::unserialize($e->getMessage(), Horde_Serialize::JSON);
            return sprintf(_("There was an error contacting Twitter: %s"), $msg->error);
        }
        /* Latest status */
        if (empty($this->_profile->status)) {
            // status might not be set if only updating the block via ajax
            try {
              $this->_profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
              if (empty($this->_profile)) {
                  return _("Temporarily unable to contact Twitter. Please try again later.");
              }
            } catch (Horde_Service_Twitter_Exception $e) {
                $msg = Horde_Serialize::unserialize($e->getMessage(), Horde_Serialize::JSON);
                return sprintf(_("There was an error contacting Twitter: %s"), $msg);
            }
        }

        $latestStatus = htmlspecialchars($this->_profile->status->text, ENT_COMPAT, Horde_Nls::getCharset());

        // Bring in the Facebook CSS
        $csslink = $GLOBALS['registry']->get('themesuri', 'horde') . '/facebook.css';
        $defaultText = _("What are you working on now?");
        $html = '<link href="' . $csslink . '" rel="stylesheet" type="text/css" />';
        $html .= '<div class="fbbody" style="float:left;padding-left: 8px;padding-right:8px;">'
           . '<input style="width:98%;margin-top:4px;margin-bottom:4px;" type="text" id="' . $instance . '_newStatus" name="' . $instance . '_newStatus" value="' . $defaultText . '" />'
           . '<div class="fbaction"><a class="fbbutton" onclick="Horde.twitter.updateStatus($F(\'' . $instance . '_newStatus\'));" href="#">' . _("Update") . '</a><span id="' . $instance . '_inReplyTo"></span></div>'
           . Horde::img('loading.gif', '', array('id' => $instance . '_loading', 'style' => 'display:none;'));
        $html .= '<div id="currentStatus" class="fbemptystatus" style="margin-left:10px;margin-top:10px;">' . sprintf(_("Latest: %s - %s"), $latestStatus, Horde_Date_Utils::relativeDateTime(strtotime($this->_profile->status->created_at), $GLOBALS['prefs']->getValue('date_format'), ($GLOBALS['prefs']->getValue('twentyFour') ? "%H:%M %P" : "%I %M %P"))) . '</div>';
        $html .= '<div id="twitter_body' . $instance . '">';
        $filter = Horde_Text_Filter::factory('Text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        foreach ($stream as $tweet) {
            /* links */
            $body = Horde_Text_Filter::filter($tweet->text, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL));
            $body = preg_replace("/[@]+([A-Za-z0-9-_]+)/", "<a href=\"http://twitter.com/\\1\" target=\"_blank\">\\0</a>", $body);
            $profileLink = Horde::externalUrl('http://twitter.com/' . htmlspecialchars($tweet->user->screen_name), true);
            $appText = Horde_Text_Filter::filter($tweet->source, 'xss', array());
            $html .= '<div class="fbstreamstory">';
            $html .= '<div style="float:left;text-align:center;width:70px;margin-right:5px;">' . $profileLink
                . '<img src="' . $tweet->user->profile_image_url . '" alt="' . htmlspecialchars($tweet->user->screen_name) . '" title="' . htmlspecialchars($tweet->user->name) . '" />'
                . '</a><div style="overflow:hidden;">' . $profileLink . htmlspecialchars($tweet->user->screen_name, ENT_COMPAT, Horde_Nls::getCharset()) . '</a></div></div>';
            $html .= ' <div class="fbstreambody">';
            $html .=  $body;
            $html .= '<div class="fbstreaminfo">' . sprintf(_("Posted %s via %s"), Horde_Date_Utils::relativeDateTime(strtotime($tweet->created_at), $GLOBALS['prefs']->getValue('date_format')), $appText) . '</div>';
            $html .= '<div class="fbstreaminfo">' . Horde::link('#', '', '', '', 'Horde.twitter.buildReply(\'' . $tweet->id . '\', \'' . $tweet->user->screen_name . '\', \'' . $tweet->user->name . '\')') .  _("Reply") . '</a>';
            $html .= '</div><div class="clear">&nbsp;</div></div>';
        }
        $html .= '</div>';
        $endpoint = Horde::url('services/twitter.php', true);
        $spinner = '$(\'' . $instance . '_loading\')';
        $inputNode = '$(\'' . $instance . '_newStatus\')';
        $inReplyToNode = '$(\'' . $instance . '_inReplyTo\')';
        $inReplyToText = _("In reply to:");
        $contentNode = 'twitter_body' . $instance;
        $justNowText = _("Just now...");

        $html .= <<<EOF
        <script type="text/javascript">
        var Horde = window.Horde || {};
        Horde.twitter = {
            inReplyTo: '',

            updateStatus: function(statusText) {
                {$inputNode}.stopObserving('blur');
                {$spinner}.toggle();
                params = new Object();
                params.actionID = 'updateStatus';
                params.statusText = statusText;
                params.inReplyTo = this.inReplyTo;
                new Ajax.Request('$endpoint', {
                    method: 'post',
                    parameters: params,
                    onComplete: function(response) {
                        this.updateCallback(response.responseJSON);
                    }.bind(this),
                    onFailure: function() {
                        {$spinner}.toggle();
                        this.inReplyTo = '';
                    }
                });
            },

            buildReply: function(id, userid, usertext) {
                this.inReplyTo = id;
                {$inputNode}.value = '@' + userid + ' ';
                {$inReplyToNode}.update(' {$inReplyToText} ' + usertext);
                {$inputNode}.focus();
            },

            updateCallback: function(response) {
               this.buildTweet(response);
               {$inputNode}.value = '{$defaultText}';
               {$spinner}.toggle();
               this.inReplyTo = '';
               {$inReplyToNode}.update('');
            },

            buildTweet: function(response) {
                var tweet = new Element('div', {'class':'fbstreamstory'});
                var tPic = new Element('div', {'style':'float:left'}).update(
                    new Element('a', {'href': 'http://twitter.com/' + response.user.screen_name}).update(
                        new Element('img', {'src':response.user.profile_image_url})
                    )
                );
                var tBody = new Element('div', {'class':'fbstreambody'}).update(response.text);
                tBody.appendChild(new Element('div', {'class':'fbstreaminfo'}).update('{$justNowText}'));
                tweet.appendChild(tPic);
                tweet.appendChild(tBody);

                $('{$contentNode}').insert({top:tweet});
            },

            clearInput: function() {
                {$inputNode}.value = '';
            }
        };

        document.observe('dom:loaded', function() {
            {$inputNode}.observe('focus', function() {Horde.twitter.clearInput()});
            {$inputNode}.observe('blur', function() {
                if (!{$inputNode}.value.length) {
                    {$inputNode}.value = '{$defaultText}';
                }
            });
        });
        </script>
EOF;
        $html .= '</div>';
        return $html;
    }

    private function _getTwitterObject()
    {
        if (!empty($this->_twitter)) {
            return $this->_twitter;
        }

        $cache = Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'],
                                        Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));

        if (!empty($GLOBALS['conf']['twitter']['key']) &&
            !empty($GLOBALS['conf']['twitter']['secret'])) {

            // Using OAuth but make sure the user has gotten a token
            $token = unserialize($GLOBALS['prefs']->getValue('twitter'));
            if (empty($token['key']) && empty($token['secret'])) {
                // No token - should we allow the user to use http basic if
                // horde is configured to use oauth?
                throw new Horde_Exception(_("You must give Horde access to your Twitter account."));
            }

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
            $twitter = new Horde_Service_Twitter(array('oauth' => $oauth,
                                                       'cache' => $cache));
            $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
            $twitter->auth->setToken($auth_token);

            $this->_twitter = $twitter;
            return $twitter;

        } elseif (!empty($this->_params['username']) ||
                  !empty($this->_params['password'])) {

            // Store the username and password in the session to enable
            // services/twitterapi.php's functionality.
            $_SESSION['horde']['twitterblock']['username'] = $this->_params['username'];
            $_SESSION['horde']['twitterblock']['password'] = $this->_params['password'];
            $twitter = new Horde_Service_Twitter(array('username' => $this->_params['username'],
                                                       'password' => $this->_params['password'],
                                                       'cache' => $cache));

            $this->_twitter = $twitter;
            return $twitter;
        }

        throw new Horde_Exception(_("Must configure a Twitter username and password to use this block."));
    }

}
