<?php
/**
 * Block for displaying the current user's Facebook stream, with the ability to
 * filter it using the same Facebook filters available on facebook.com.  Also
 * provides ability to update the current user's status.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde
 */
class Horde_Block_FbStream extends Horde_Core_Block
{
    /**
     * @var Horde_Service_Facebook
     */
    private $_facebook;

    /**
     */
    public function __construct($app, $params = array())
    {
        try {
            $this->_facebook = $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
        } catch (Horde_Exception $e) {
            $this->enabled = false;
            return;
        }
        parent::__construct($app, $params);
        $this->_name = _("My Facebook Stream");
    }

    /**
     */
    protected function _params()
    {
        $fbp = unserialize($GLOBALS['prefs']->getValue('facebook'));
        $filters = array();
        if (!empty($fbp['sid'])) {
            $fql = 'SELECT filter_key, name FROM stream_filter WHERE uid="'
                . $fbp['uid'] . '"';
            try {
                $stream_filters = $this->_facebook->fql->run($fql);
                foreach ($stream_filters as $filter) {
                    $filters[$filter['filter_key']] = $filter['name'];
                }
            } catch (Horde_Service_Facebook_Exception $e) {
            }
        }

        return array(
            'filter' => array(
                'type' => 'enum',
                'name' => _("Filter"),
                'default' => 'nf',
                'values' => $filters
            ),
            'count' => array(
                'type' => 'int',
                'name' => _("Maximum number of entries to display"),
                'default' => '20'
            ),
            'notifications' => array(
                'type' => 'boolean',
                'name' => _("Show notifications"),
                'default' => true
            ),
            'height' => array(
                 'name' => _("Height of stream content (width automatically adjusts to block)"),
                 'type' => 'int',
                 'default' => 250
            ),
        );
    }

    /**
     */
    protected function _title()
    {
        return Horde::externalUrl('http://facebook.com', true) . $this->getName() . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content.
     */
    protected function _content()
    {
        $instance = hash('md5', mt_rand());
        $endpoint = Horde::url('services/facebook/', true);
        $html = '';

        /* Init facebook driver, exit early if no prefs exist */
        $facebook = $this->_facebook;
        if (!($facebook->auth->getSessionKey())) {
            return sprintf(_("You have not properly connected your Facebook account with Horde. You should check your Facebook settings in your %s."), Horde::getServiceLink('prefs', 'horde')->add('group', 'facebook')->link() . _("preferences") . '</a>');
        }
        $fbp = unserialize($GLOBALS['prefs']->getValue('facebook'));

        /* Add the client javascript / initialize it */
        $GLOBALS['injector']->getInstance('Horde_Themes_Css')->addThemeStylesheet('facebook.css');
        Horde::addScriptFile('facebookclient.js');
        $script = <<<EOT
            var Horde = window.Horde || {};
            Horde['{$instance}_facebook'] = new Horde_Facebook({
               spinner: '{$instance}_loading',
               endpoint: '{$endpoint}',
               content: '{$instance}_fbcontent',
               status: '{$instance}_currentStatus',
               notifications: '{$instance}_fbnotifications',
               getmore: '{$instance}_getmore',
               'input': '{$instance}_newStatus',
               'button': '{$instance}_button',
               instance: '{$instance}',
               'filter': '{$this->_params['filter']}',
               'count': '{$this->_params['count']}'
            });
EOT;
        Horde::addInlineScript($script, 'dom');

        /* Build the UI */
        $html .= '<div style="padding: 8px 8px 0 8px">';

        /* Build the Notification Section */
        if (!empty($this->_params['notifications'])) {
            $html .= '<div class="fbinfobox" id="' . $instance . '_fbnotifications"></div>';
        }

        /* User's current status and input box to change it. */
        $fql = 'SELECT first_name, last_name, status, pic_square_with_logo from user where uid=' . $fbp['uid'] . ' LIMIT 1';
        try {
            $status = $facebook->fql->run($fql);
        } catch (Horde_Service_Facebook_Exception $e) {
            $html = sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), Horde::getServiceLink('prefs', 'horde')->add('group', 'facebook')->link() . _("preferences") . '</a>');

            return $html;
        }

        $status = array_pop($status);
        if (empty($status['status']['message'])) {
            $status['status']['message'] = _("What's on your mind?");
            $class = 'fbemptystatus';
        } else {
            $class = '';
        }
        $html .= '<div class="fbgreybox fbboxfont">'
            . '<img style="float:left;" src="' . $status['pic_square_with_logo'] . '" />'
            . '<div id="' . $instance . '_currentStatus" class="' . $class . '" style="margin-left:55px;">'
            . $status['status']['message']
            . '</div>';

        try {
            if ($facebook->users->hasAppPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM)) {
                $html .= '<input style="width:100%;margin-top:4px;margin-bottom:4px;" type="text" class="fbinput" id="' . $instance . '_newStatus" name="newStatus" />'
                    . '<div><a class="button" href="#" id="' . $instance . '_button">' . _("Update") . '</a></div>'
                    . Horde::img('loading.gif', '', array('id' => $instance. '_loading', 'style' => 'display:none;'));
            }
        } catch (Horde_Service_Facebook_Exception $e) {
            $html .= sprintf(_("There was an error making the request: %s"), $e->getMessage());
            $html .= sprintf(_("You can also check your Facebook settings in your %s."), Horde::link($endpoint) . _("preferences") . '</a>');
            return $html;
        }
        $html .= '</div>'; // Close the fbgreybox node that wraps the status


       // Build the stream feed.
        $html .= '<br /><div id="' . $instance . '_fbcontent" style="height:' . (empty($this->_params['height']) ? 300 : $this->_params['height']) . 'px;overflow-y:auto;overflow-x:hidden;"></div><br />';
        $html .= '<div class="hordeSmGetmore"><input type="button" id="' . $instance . '_getmore" class="button"  value="' . _("Get More") . '"></div>';

        $html .= '</div>'; // fbbody end

        return $html;
    }

}
