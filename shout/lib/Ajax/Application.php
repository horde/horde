<?php
/**
 * Defines the AJAX interface for Shout.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */
class Shout_Ajax_Application extends Horde_Ajax_Application_Base
{
    /**
     * Returns a notification handler object to use to output any
     * notification messages triggered by the action.
     *
     * @return Horde_Notification_Handler_Base   The notification handler.
     */
    public function notificationHandler()
    {
        // FIXME: Create Shout notification handler
        //return $GLOBALS['kronolith_notify'];
        return null;
    }

    /**
     * TODO
     */
    public function addDestination()
    {
        $vars = $this->_vars;
        $shout = Horde_Registry::appInit('shout');
        $context = $_SESSION['shout']['context'];
        try {
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $shout->extensions->addDestination($context, $vars->extension, $vars->type, $vars->destination);

            return $shout->extensions->getExtensions($context);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
    }

    /**
     * TODO
     */
    public function deleteDestination()
    {
        $vars = $this->_vars;
        $shout = Horde_Registry::appInit('shout');
        $context = $_SESSION['shout']['context'];
        try {
            // FIXME: Use Form?
            $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
            $shout->extensions->deleteDestination($context, $vars->extension, $vars->type, $vars->destination);

            return $shout->extensions->getExtensions($context);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
    }

    /**
     * TODO
     */
    public function getDestinations()
    {
        $vars = $this->_vars;
        $shout = Horde_Registry::appInit('shout');
        $context = $_SESSION['shout']['context'];
        try {
            return $shout->extensions->getExtensions($context);
        } catch (Exception $e) {
            //FIXME: Create a way to notify the user of the failure.
            Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
    }

}

