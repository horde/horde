<?php
/**
 * Folks facebook firends implementation
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_facebook extends Folks_Friends {

    /**
     * FB connection parameters
     */
    private $_facebook;
    private $_sid;
    private $_uid;

    /**
     * Get user friends
     *
     * @return array of users
     */
    protected function _getFriends()
    {
        if (!$this->_loadFB) {
            return $this->_fb;
        }

        try {
            $friends = $this->_fb->friends->get(null, $this->_uid);
        } catch (Horde_Service_Facebook_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return $friends;
    }

    /**
     * Get avaiable groups
     */
    public function getGroups()
    {
        if (!$this->_loadFB) {
            return $this->_fb;
        }

        try {
            $groups = $this->_fb->friends->getLists();
        } catch (Horde_Service_Facebook_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return $groups;
    }


    /**
     * Load FB content
     */
    private function _loadFB()
    {
        if ($this->_fb) {
            return true;
        }

        if (!$conf['facebook']['enabled']) {
            $this->_fb = PEAR::raiseError(_("No Facebook integration exists."));
            return false;
        }

        // Check FB user config
        $fbp = unserialize($prefs->getValue('facebook'));
        if (!$fbp || empty($fbp['uid'])) {
            $this->_fb = PEAR::raiseError(_("User has no link."));
            return false;
        }

        $context = array('http_client' => new Horde_Http_Client(),
                         'http_request' => $GLOBALS['injector']->getInstance('Horde_Controller_Request'));

        $this->_fb = new Horde_Service_Facebook($conf['facebook']['key'],
                                               $conf['facebook']['secret'],
                                               $context);

        $this->_fb->auth->setUser($fbp['uid'], $fbp['sid'], 0);

        return true;
    }
}
