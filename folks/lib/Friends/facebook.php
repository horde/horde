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

        try {
            $facebook = $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
        } catch (Horde_Exception $e) {
            $error = PEAR::raiseError($e->getMessage(), $e->getCode());
            Horde::logMessage($error, 'ERR');

            return $error;
        }
        $this->_fb->auth->setUser($fbp['uid'], $fbp['sid'], 0);

        return true;
    }
}
