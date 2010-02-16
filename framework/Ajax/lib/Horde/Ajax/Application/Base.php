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
     * Determines if notification information is sent in response.
     *
     * @var boolean
     */
    public $notify = false;

    /**
     * The Horde application.
     *
     * @var string
     */
    protected $_app;

    /**
     * The action to perform.
     *
     * @var string
     */
    protected $_action;

    /**
     * The request variables.
     *
     * @var Variables
     */
    protected $_vars;

    /**
     * The list of actions that require readonly access to the session.
     *
     * @var array
     */
    protected $_readOnly = array();

    /**
     * Constructor.
     *
     * @param string $app     The application name.
     * @param string $action  The AJAX action to perform.
     */
    public function __construct($app, $action = null)
    {
        $this->_app = $app;

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

        $this->_vars = Horde_Variables::getDefaultVariables();

        if (method_exists($this, $this->_action)) {
            return call_user_func(array($this, $this->_action));
        }

        /* Look for hook in application. */
        try {
            return Horde::callHook('ajaxaction', array($this->_action, $this->_vars), $this->_app);
        } catch (Horde_Exception_HookNotSet $e) {
        } catch (Horde_Ajax_Exception $e) {}

        throw new Horde_Ajax_Exception('Handler for action "' . $this->_action . '" does not exist.');
    }

    /**
     * Determines the HTTP response output type.
     *
     * @see Horde::sendHTTPResponse().
     *
     * @return string  The output type.
     */
    public function responseType()
    {
        return 'json';
    }

    /**
     * AJAX action: Logout.
     *
     * This needs to be done here (server), rather than on the browser,
     * because the logout tokens might otherwise expire.
     *
     * @param Horde_Variables $vars  None used.
     */
    public function LogOut($vars)
    {
        Horde::redirect(str_replace('&amp;', '&', Horde::getServiceLink('logout', $this->_app)));
        exit;
    }

}
