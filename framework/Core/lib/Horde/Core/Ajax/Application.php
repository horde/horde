<?php
/**
 * Defines the AJAX interface for an application.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 *
 * @property string $app  The current application
 * @property Horde_Variables $vars  The Variables object.
 */
abstract class Horde_Core_Ajax_Application
{
    /**
     * The data returned from the doAction() call.
     *
     * @var mixed
     */
    public $data = null;

    /**
     * The list of (possibly) unsolicited tasks/data to do for this request.
     *
     * @var object
     */
    public $tasks = null;

    /**
     * The action to perform.
     *
     * @var string
     */
    protected $_action;

    /**
     * The Horde application.
     *
     * @var string
     */
    protected $_app;

    /**
     * AJAX method handlers.
     *
     * @var array
     */
    protected $_handlers = array();

    /**
     * The request variables.
     *
     * @var Horde_Variables
     */
    protected $_vars;

    /**
     * Constructor.
     *
     * @param string $app            The application name.
     * @param Horde_Variables $vars  Form/request data.
     * @param string $action         The AJAX action to perform.
     * @param string $token          Session token.
     *
     * @throws Horde_Exception
     */
    public function __construct($app, Horde_Variables $vars, $action = null,
                                $token = null)
    {
        global $registry, $session;

        $this->_app = $app;
        $this->_vars = $vars;
        $this->_action = $action;

        $this->_init();

        $auth = $registry->currentProcessAuth();
        $ob = $this->_getHandler();

        /* Non-authenticated actions MUST occur in a handler. */
        if (!$ob && !$auth) {
            throw new Horde_Exception('Accessing AJAX action without being authenticated.');
        }

        /* Check authentication/token. */
        if ($ob && !$ob->external($action)) {
            if (!$auth) {
                throw new Horde_Exception('Accessing AJAX action without being authenticated.');
            }
            $session->checkToken($token);
        }

        /* Check for session regneration request. */
        if ($vars->regenerate_sid) {
            $session->regenerate();
            if (SID) {
                $this->addTask('sid', SID, 'horde');
            }
        }

        /* Close session if action is labeled as read-only. */
        if ($ob && $ob->readonly($action)) {
            $session->close();
        }
    }

    /**
     * Application initialization code.
     */
    protected function _init()
    {
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'app':
            return $this->_app;

        case 'vars':
            return $this->_vars;
        }
    }

    /**
     * Add an AJAX method handler.
     *
     * @param string $class  Classname of a Handler to add.
     *
     * @return Horde_Core_Ajax_Application_Handler  Handler object.
     */
    final public function addHandler($class)
    {
        if (!isset($this->_handlers[$class])) {
            if (!class_exists($class) ||
                !($ob = new $class($this)) ||
                !($ob instanceof Horde_Core_Ajax_Application_Handler)) {
                throw new InvalidArgumentException('Bad AJAX handler: ' . $class);
            }

            $this->_handlers[$class] = $ob;
        }

        return $this->_handlers[$class];
    }

    /**
     * Performs the AJAX action. The AJAX action should return either raw data
     * (which will be output to the browser to be parsed by the HordeCore JS
     * framework), or a Horde_Ajax_Core_Response object, which will be sent
     * unaltered.
     *
     * @throws Horde_Exception
     */
    public function doAction()
    {
        global $injector;

        if (!strlen($this->_action)) {
            return;
        }

        $hooks = $injector->getInstance('Horde_Core_Hooks');

        /* Look for action in helpers. */
        if ($ob = $this->_getHandler()) {
            $this->data = call_user_func(array($ob, $this->_action));
        } else {
            /* Look for action in application hook. */
            try {
                $this->data = $hooks->callHook(
                    'ajaxaction_handle',
                    $this->_app,
                    array($this, $this->_action)
                );
            } catch (Horde_Exception $e) {
                /* DEPRECATED hook. @deprecated */
                try {
                    $this->data = $hooks->callHook(
                        'ajaxaction',
                        $this->_app,
                        array($this->_action, $this->_vars)
                    );
                } catch (Horde_Exception $e) {
                    throw new Horde_Exception('Handler for action "' . $this->_action . '" does not exist.');
                }
            }
        }

        try {
            $this->data = $hooks->callHook(
                'ajaxaction_data',
                $this->_app,
                array($this->_action, $this->data)
            );
        } catch (Horde_Exception_HookNotSet $e) {}
    }

    /**
     * Add task to response data.
     *
     * @param string $name  Task name.
     * @param mixed $data   Task data.
     * @param string $app   Overwrite default application (since 2.5.0).
     */
    public function addTask($name, $data, $app = null)
    {
        if (empty($this->tasks)) {
            $this->tasks = new stdClass;
        }

        $name = (is_null($app) ? $this->_app : $app) . ':' . $name;
        $this->tasks->$name = $data;
    }

    /**
     * Send AJAX response to the browser.
     */
    public function send()
    {
        if ($GLOBALS['session']->regenerate_due) {
            $this->addTask('regenerate_sid', true, 'horde');
        }

        if ($this->data instanceof Horde_Core_Ajax_Response) {
            $response = clone $this->data;
            if ($response instanceof Horde_Core_Ajax_Response_HordeCore) {
                $response->tasks = $this->tasks;
            }
        } else {
            $response = new Horde_Core_Ajax_Response_HordeCore($this->data, $this->tasks);
        }
        $response->sendAndExit();
    }

    /**
     * Explicitly call an action.
     *
     * @since 2.5.0
     *
     * @param string $action  The action to call.
     *
     * @return mixed  The response from the called action.
     */
    public function callAction($action)
    {
        foreach ($this->_handlers as $ob) {
            if ($ob->has($action)) {
                return call_user_func(array($ob, $action));
            }
        }
    }

    /**
     * Return the Handler for the current action.
     *
     * @return mixed  A Horde_Core_Ajax_Application_Handler object, or null if
     *                handler is not found.
     */
    protected function _getHandler()
    {
        foreach ($this->_handlers as $ob) {
            if ($ob->has($this->_action)) {
                return $ob;
            }
        }

        return null;
    }

}
