<?php
/**
 * Folks Notification Class.
 *
 * $Id: facebook.php 1469 2009-03-12 10:51:11Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Notification_facebook extends Folks_Notification {

    /**
     * FB object
     */
    private $_fb;

    /**
     * FB connection parameters
     */
    private $_fbp;

    /**
     * Returns method human name
     */
    public function getName()
    {
        return _("Facebook");
    }

    /**
     * Checks if a driver is available for a certain notification type
     *
     * @param string $type Notification type
     *
     * @return boolean
     */
    public function isAvailable($type)
    {
        // Check FB installation
        if (!$GLOBALS['conf']['facebook']['enabled']) {
            return false;
        }

        // Chacke FB user config
        $fbp = unserialize($GLOBALS['prefs']->getValue('facebook'));
        if (!$fbp || empty($fbp['uid'])) {
            return false;
        }

        return true;
    }

    /**
     * Notify user
     *
     * @param mixed  $user        User or array of users to send notification to
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     * @param array  $attachments Attached files
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function notify($user, $subject, $body, $attachments = array())
    {
        if (!$this->_loadFB()) {
            return $this->_fb;
        }

        try {
            $message = $this->_formatBody($subject, $body);
            $result = $this->_fb->notifications->send(array($this->_fbp['uid']), $message, 'user_to_user');
        } catch (Horde_Service_Facebook_Exception $e) {
            return PEAR::raiseError($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * Notify user
     *
     * @param mixed  $user        User or array of users to send notification to
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     * @param array  $attachments Attached files
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function notifyFriends($user, $subject, $body, $attachments = array())
    {
        if (!$this->_loadFB()) {
            return $this->_fb;
        }

        try {
            $friends = $this->_fb->friends->get(null, $this->_fbp['uid']);
        } catch (Horde_Service_Facebook_Exception $e) {
            return PEAR::raiseError($e->getMessage(), $e->getCode());
        }

        try {
            $message = $this->_formatBody($subject, $body);
            $result = $this->_fb->notifications->send($friends, $message, 'user_to_user');
        } catch (Horde_Service_Facebook_Exception $e) {
            return PEAR::raiseError($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * Load FB content
     */
    private function _loadFB()
    {
        if ($this->_fb) {
            return true;
        }

        // Check FB installation
        if (!$GLOBALS['conf']['facebook']['enabled']) {
            $this->_fb = PEAR::raiseError(_("No Facebook integration exists."));
            return false;
        }

        // Check FB user config
        $this->_fbp = unserialize($GLOBALS['prefs']->getValue('facebook'));
        if (!$this->_fbp || empty($this->_fbp['uid'])) {
            $this->_fb = PEAR::raiseError(sprintf(_("Could not find authorization for %s to interact with your Facebook account."), $GLOBALS['registry']->get('name', 'horde')));
            return false;
        }

        // Create FB Object
        $this->_fb = new Horde_Service_Facebook($GLOBALS['conf']['facebook']['key'],
                                                $GLOBALS['conf']['facebook']['secret'],
                                                array('http_client' => new Horde_Http_Client(),
                                                      'http_request' => $GLOBALS['injector']->getInstance('Horde_Controller_Request')));

        // Set Auth user
        $this->_fb->auth->setUser($this->_fbp['uid'], $this->_fbp['sid'], 0);

        return true;
    }

    /**
     * Format notification content
     *
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     *
     * @return string Formatted message
     */
    private function _formatBody($subject, $body)
    {
        return '<b>' . $subject . ':</b> '
                . $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($body, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL));
    }
}
