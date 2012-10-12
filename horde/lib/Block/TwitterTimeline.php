<?php
/**
 * A bare-bones twitter client in a Horde block.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Horde
 */
class Horde_Block_TwitterTimeline extends Horde_Core_Block
{
    /**
     * @var Horde_Service_Twitter
     */
    private $_twitter;

    /**
     * Twitter profile information returned from verify_credentials
     *
     * @var Object
     */
    private $_profile;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->enabled = !empty($GLOBALS['conf']['twitter']['enabled']);
        $this->_name = _("Twitter Timeline");
    }

    /**
     */
    protected function _title()
    {
        try {
            $twitter = $this->_getTwitterObject();
        } catch (Horde_Exception $e) {
            return $this->getName();
        }
        try {
            $this->_profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
            if (!empty($this->_profile)) {
                $username = $this->_profile->screen_name;
                return sprintf(_("Twitter Timeline for %s"), $username);
            }
        } catch (Horde_Service_Twitter_Exception $e) {}

        return $this->getName();
    }

    /**
     */
    protected function _params()
    {
        return array(
            'height' => array(
                 'name' => _("Height of stream content (width automatically adjusts to block)"),
                 'type' => 'int',
                 'default' => 350
             ),
            'refresh_rate' => array(
                 'name' => _("Number of seconds to wait to refresh"),
                 'type' => 'int',
                 'default' => 300
             )
        );
    }

    /**
     */
    protected function _content()
    {
        global $page_output;

        /* Get the twitter driver */
        try {
            $twitter = $this->_getTwitterObject();
        }  catch (Horde_Exception $e) {
            throw new Horde_Exception(sprintf(_("There was an error contacting Twitter: %s"), $e->getMessage()));
        }

        /* Get a unique ID in case we have multiple Twitter blocks. */
        $instance = (string)new Horde_Support_Randomid();

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
        $defaultText = addslashes(_("What are you working on now?"));
        $endpoint = Horde::url('services/twitter/', true);
        $inReplyToNode = $instance . '_inReplyTo';
        $inReplyToText = addslashes(_("In reply to:"));
        $justNowText = addslashes(_("Just now..."));
        $refresh = empty($this->_params['refresh_rate']) ? 300 : $this->_params['refresh_rate'];

        /* Add the client javascript / initialize it */
        $page_output->addScriptFile('twitterclient.js', 'horde');
        $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
        $script = <<<EOT
            Horde = window.Horde = window.Horde || {};
            Horde['twitter{$instance}'] = new Horde_Twitter({
               instanceid: '{$instance}',
               getmore: '{$instance}_getmore',
               input: '{$instance}_newStatus',
               spinner: '{$instance}_loading',
               content: '{$instance}_stream',
               contenttab: '{$instance}_contenttab',
               mentiontab: '{$instance}_mentiontab',
               mentions: '{$instance}_mentions',
               endpoint: '{$endpoint}',
               inreplyto: '{$inReplyToNode}',
               refreshrate: {$refresh},
               counter: '{$instance}_counter',
               strings: { inreplyto: '{$inReplyToText}', defaultText: '{$defaultText}', justnow: '{$justNowText}' }
            });
EOT;
        $page_output->addInlineScript($script, true);

        /* Get the user's most recent tweet */


        /* Build the UI */
        $view = new Horde_View(array('templatePath' => HORDE_TEMPLATES . '/block'));
        $view->addHelper('Tag');
        $view->instance = $instance;
        $view->defaultText = $defaultText;
        $view->loadingImg = Horde::img('loading.gif', '', array('id' => $instance . '_loading', 'style' => 'display:none;'));
        $view->latestStatus = !empty($this->_profile->status) ? htmlspecialchars($this->_profile->status->text) : '';
        $view->latestDate = !empty($this->_profile->status) ?  Horde_Date_Utils::relativeDateTime(strtotime($this->_profile->status->created_at), $GLOBALS['prefs']->getValue('date_format'), ($GLOBALS['prefs']->getValue('twentyFour') ? "%H:%M" : "%I:%M %P")) : '';
        $view->bodyHeight = empty($this->_params['height']) ? 350 : $this->_params['height'];

        return $view->render('twitter-layout');
    }

    /**
     */
    private function _getTwitterObject()
    {
        $token = unserialize($GLOBALS['prefs']->getValue('twitter'));
        if (empty($token['key']) && empty($token['secret'])) {
            $pref_link = $GLOBALS['registry']->getServiceLink('prefs', 'horde')->add('group', 'twitter')->link();
            throw new Horde_Exception(sprintf(_("You have not properly connected your Twitter account with Horde. You should check your Twitter settings in your %s."), $pref_link . _("preferences") . '</a>'));
        }

        $this->_twitter = $GLOBALS['injector']->getInstance('Horde_Service_Twitter');
        $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
        $this->_twitter->auth->setToken($auth_token);

        return $this->_twitter;
    }

}
