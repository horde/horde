<?php
/**
 * The Horde_Block_twitter_timeline class provides a bare-bones twitter client
 * as a horde block.
 *
 * Still @TODO:
 *  - configure block to show friendTimeline, specific user, public timeline,
 *    'mentions' for current user etc..
 *  - keep track of call limits and either dynamically alter update time or
 *    at least provide feedback to user.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
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
     * Whether this block has changing content. Set this to false since we
     * handle the updates via AJAX on our own.
     *
     */
    var $updateable = false;

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
        return array(
            'height' => array(
                 'name' => _("Height of map (width automatically adjusts to block)"),
                 'type' => 'int',
                 'default' => 250),
            'refresh_rate' => array(
                 'name' => _("Number of seconds to wait to refresh"),
                 'type' => 'int',
                 'default' => 300)
        );
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        global $conf;

        /* Get the twitter driver */
        try {
            $twitter = $this->_getTwitterObject();
        }  catch (Horde_Exception $e) {
            return sprintf(_("There was an error contacting Twitter: %s"), $e->getMessage());
        }

        /* Get a unique ID in case we have multiple Twitter blocks. */
        $instance = md5(mt_rand());

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

        /* Build values to pass to the javascript twitter client */
        $defaultText = _("What are you working on now?");
        $endpoint = Horde::url('services/twitter.php', true);
        $spinner = $instance . '_loading';
        $inputNode = $instance . '_newStatus';
        $inReplyToNode = $instance . '_inReplyTo';
        $inReplyToText = _("In reply to:");
        $contentNode = 'twitter_body' . $instance;
        $justNowText = _("Just now...");
        $refresh = empty($this->_params['refresh_rate']) ? 300 : $this->_params['refresh_rate'];

        /* Add the client javascript / initialize it */
        Horde::addScriptFile('twitterclient.js');
        $script = <<<EOT
            var Horde = window.Horde || {};
            Horde.twitter = new Horde_Twitter({
               input: '{$instance}_newStatus',
               spinner: '{$instance}_loading',
               content: 'twitter_body{$instance}',
               endpoint: '{$endpoint}',
               inreplyto: '{$inReplyToNode}',
               refreshrate: {$refresh},
               strings: { inreplyto: '{$inReplyToText}', defaultText: '{$defaultText}', justnow: '{$justNowText}' }
            });
EOT;
        Horde::addInlineScript($script, 'dom');

        /* Get the user's most recent tweet */
        $latestStatus = htmlspecialchars($this->_profile->status->text, ENT_COMPAT, Horde_Nls::getCharset());

        // Bring in the Facebook CSS
        $csslink = $GLOBALS['registry']->get('themesuri', 'horde') . '/facebook.css';

        /* Build the UI */
        $html = '<link href="' . $csslink . '" rel="stylesheet" type="text/css" />';
        $html .= '<div style="float:left;padding-left: 8px;padding-right:8px;">'
           . '<input style="width:98%;margin-top:4px;margin-bottom:4px;" type="text" id="' . $instance . '_newStatus" name="' . $instance . '_newStatus" value="' . $defaultText . '" />'
           . '<div><a class="button" onclick="Horde.twitter.updateStatus($F(\'' . $instance . '_newStatus\'));" href="#">' . _("Update") . '</a><span id="' . $instance . '_inReplyTo"></span></div>'
           . Horde::img('loading.gif', '', array('id' => $instance . '_loading', 'style' => 'display:none;'));
        $html .= '<div id="currentStatus" class="fbemptystatus" style="margin-left:10px;margin-top:10px;">' . sprintf(_("Latest: %s - %s"), $latestStatus, Horde_Date_Utils::relativeDateTime(strtotime($this->_profile->status->created_at), $GLOBALS['prefs']->getValue('date_format'), ($GLOBALS['prefs']->getValue('twentyFour') ? "%H:%M %P" : "%I %M %P"))) . '</div>';
        $html .= '<div style="height:' . (empty($this->_params['height']) ? 250 : $this->_params['height']) . 'px;overflow-y:auto;" id="twitter_body' . $instance . '">';
        $filter = Horde_Text_Filter::factory('Text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        $html .= '</div>';
        $html .= '<div class="control fbgetmore"><a href="#" onclick="Horde.twitter.getOlderEntries();return false;">' . _("Get More") . '</a></div>';
        $html .= '</div>';

        return $html;
    }

    private function _getTwitterObject()
    {
        $token = unserialize($GLOBALS['prefs']->getValue('twitter'));
        if (empty($token['key']) && empty($token['secret'])) {
            $pref_link = Horde::link(Horde::url('services/twitter.php', true));
            throw new Horde_Exception(sprintf(_("You have not properly connected your Twitter account with Horde. You should check your Twitter settings in your %s."), $pref_link . _("preferences") . '</a>'));
        }

        $this->_twitter = $GLOBALS['injector']->getInstance('Horde_Service_Twitter');
        $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
        $this->_twitter->auth->setToken($auth_token);

        return $this->_twitter;
    }

}
