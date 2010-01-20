<?php
/**
 * Defines the AJAX interface for an application.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Ajax
 */
abstract class Horde_Ajax_Application_Base
{
    /**
     * The action to perform.
     *
     * @var string
     */
    protected $_action;

    /**
     * The list of actions that require readonly access to the session.
     *
     * @var array
     */
    protected $_readOnly = array();

    /**
     * Constructor.
     *
     * @param string $action  The AJAX action to perform.
     */
    public function __construct($action = null)
    {
        if (!is_null($action)) {
            /* Close session if action is labeled as read-only. */
            if (in_array($action, $this->_readOnly)) {
                session_write_close();
            }

            $this->_action = $action;
        }
    }

    /**
     * Performs the AJAX action.
     *
     * @return mixed  The result of the action call.
     * @throws Horde_Ajax_Exception
     */
    public function doAction()
    {
        if (!$this->_action) {
            return false;
        }

        if (method_exists($this, $this->_action)) {
        return call_user_func(array($this, $this->_action), Horde_Variables::getDefaultVariables());
        }

        throw new Horde_Ajax_Exception('Handler for action "' . $this->_action . '" does not exist.');
    }

    /**
     * Returns a notification handler object to use to output any
     * notification messages triggered by the AJAX action.
     *
     * @return Horde_Notification_Handler_Base  The notification handler.
     */
    public function notificationHandler()
    {
        return null;
    }

}
