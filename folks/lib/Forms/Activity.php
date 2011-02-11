<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Activity_Form extends Horde_Form {

    /**
     */
    function __construct($vars, $title, $name)
    {
        parent::__construct($vars, $title, $name);

        if ($name == 'long') {
            $this->addVariable(_("Activity"), 'activity', 'longText', true, false, null, array(4));
        } else {
            $this->addVariable(_("Activity"), 'activity', 'text', true, false, null, array('', 80));
        }

        $this->setButtons(_("Post"));
    }

    /**
     */
    function execute()
    {
        $message = trim(strip_tags($this->_vars->get('activity')));

        if (empty($message)) {
            return PEAR::raiseError(_("You cannot post an empty activity message."));
        }

        $filters = array('text2html', 'bbcode', 'highlightquotes', 'emoticons');
        $filters_params = array(array('parselevel' => Horde_Text_Filter_Text2html::MICRO),
                                array(),
                                array(),
                                array());

        if (($hasBBcode = strpos($message, '[')) !== false &&
                strpos($message, '[/', $hasBBcode) !== false) {
            $filters_params[0]['parselevel'] = Horde_Text_Filter_Text2html::NOHTML;
        }

        $message = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(trim($message), $filters, $filters_params);

        $result = $GLOBALS['folks_driver']->logActivity($message, 'folks:custom');
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        if ($conf['facebook']['enabled']) {
            $message = trim(strip_tags($this->_vars->get('activity')));
            register_shutdown_function(array(&$this, '_facebook'), $message);
        }

        return true;
    }

    /**
     */
    public function _facebook($message)
    {
        global $conf, $prefs;

        // Check FB installation
        if (!$conf['facebook']['enabled']) {
            return true;
        }

        // Chacke FB user config
        $fbp = unserialize($prefs->getValue('facebook'));
        if (!$fbp || empty($fbp['uid'])) {
            return true;
        }

        // Load FB
        $context = array('http_client' => new Horde_Http_Client(),
                         'http_request' => $GLOBALS['injector']->getInstance('Horde_Controller_Request'));
        $facebook = new Horde_Service_Facebook($conf['facebook']['key'],
                                               $conf['facebook']['secret'],
                                               $context);

        $facebook->auth->setUser($fbp['uid'], $fbp['sid'], 0);

        try {
            $facebook->users->setStatus($message);
        } catch (Horde_Service_Facebook_Exception $e) {
            // Do noting as we are exiting
        }
    }
}
