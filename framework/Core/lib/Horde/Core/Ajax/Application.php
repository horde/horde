<?php
/**
 * Defines the AJAX interface for an application.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
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
     * Determines if notification information is sent in response.
     *
     * @var boolean
     */
    public $notify = false;

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
     * Default domain.
     *
     * @see parseEmailAddress()
     * @var string
     */
    protected $_defaultDomain;

    /**
     * The list of actions that require readonly access to the session.
     *
     * @var array
     */
    protected $_readOnly = array();

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
     */
    public function __construct($app, Horde_Variables $vars, $action = null)
    {
        $this->_app = $app;
        $this->_vars = $vars;

        if (!is_null($action)) {
            /* Close session if action is labeled as read-only. */
            if (in_array($action, $this->_readOnly)) {
                $GLOBALS['session']->close();
            }

            $this->_action = $action;
        }
    }

    /**
     * Performs the AJAX action.
     *
     * @throws Horde_Exception
     */
    public function doAction()
    {
        if (!$this->_action) {
            return;
        }

        if (method_exists($this, $this->_action)) {
            $this->data = call_user_func(array($this, $this->_action));
            return;
        }

        /* Look for hook in application. */
        try {
            $this->data = Horde::callHook('ajaxaction', array($this->_action, $this->_vars), $this->_app);
            return;
        } catch (Horde_Exception $e) {}

        throw new Horde_Exception('Handler for action "' . $this->_action . '" does not exist.');
    }

    /**
     * Add task to response data.
     *
     * @param string $name  Task name.
     * @param mixed $data   Task data.
     */
    public function addTask($name, $data)
    {
        if (empty($this->tasks)) {
            $this->tasks = new stdClass;
        }

        $name = $this->_app . ':' . $name;
        $this->tasks->$name = $data;
    }

    /**
     * Determines the HTTP response output type.
     *
     * @return string  The output type.
     */
    public function responseType()
    {
        return 'json';
    }

    /**
     * Send AJAX response to the browser.
     */
    public function send()
    {
        $response = new Horde_Core_Ajax_Response_HordeCore($this->data, $this->tasks, $this->notify);
        $response->sendAndExit($this->responseType());
    }

    /**
     * Sends a notification to the browser indicating that the user's session
     * has timed out.
     */
    public function sessionTimeout()
    {
        $msg = new stdClass;
        $msg->message = strval($this->getSessionLogoutUrl());
        $msg->type = 'horde.ajaxtimeout';

        $response = new Horde_Core_Ajax_Response(new stdClass);
        $response->notifications = array($msg);
        $response->sendAndExit('json');
    }

    /**
     * Logs the user off the Horde session.
     *
     * This needs to be done here (server), rather than on the browser,
     * because the logout tokens might otherwise expire.
     */
    public function logOut()
    {
        Horde::getServiceLink('logout', $this->_app)->setRaw(true)->redirect();
    }

    /**
     * AJAX actions performed through the endpoint are normally not a good
     * URL to return to.  Thus, by default after a session timeout, return
     * to the base of the application instead.
     *
     * @return Horde_Url  The logout Horde_Url object.
     */
    public function getSessionLogoutUrl()
    {
        return $GLOBALS['registry']->getLogoutUrl(array(
            'reason' => Horde_Auth::REASON_SESSION
        ))->add('url', Horde::url('', false, array(
            'app' => $this->_app,
            'append_session' => -1
        )));
    }

    /**
     * Returns a hash of group IDs and group names that the user has access
     * to.
     *
     * @return object  Object with the following properties:
     *   - groups: (array) Groups hash.
     */
    public function listGroups()
    {
        $result = new stdClass;
        try {
            $groups = $GLOBALS['injector']
                ->getInstance('Horde_Group')
                ->listAll(empty($GLOBALS['conf']['share']['any_group'])
                          ? $GLOBALS['registry']->getAuth()
                          : null);
            if ($groups) {
                asort($groups);
                $result->groups = $groups;
            }
        } catch (Horde_Group_Exception $e) {
            Horde::logMessage($e);
        }

        return $result;
    }

    /**
     * Parses a valid email address out of a complete address string.
     *
     * Variables used:
     *   - mbox: (string) The name of the new mailbox.
     *   - parent: (string) The parent mailbox.
     *
     * @return object  Object with the following properties:
     *   - email: (string) The parsed email address.
     *
     * @throws Horde_Exception
     * @throws Horde_Mail_Exception
     */
    public function parseEmailAddress()
    {
        $ob = new Horde_Mail_Rfc822_Address($this->_vars->email);
        if (is_null($ob->mailbox)) {
            throw new Horde_Exception(Horde_Core_Translation::t("No valid email address found"));
        }

        if ($this->_defaultDomain) {
            $ob->host = $this->_defaultDomain;
        }

        $ret = new stdClass;
        $ret->email = $ob->bare_address;

        return $ret;
    }

    /**
     * Loads a chunk of PHP code (usually an HTML template) from the
     * application's templates directory.
     *
     * @return object  Object with the following properties:
     *   - chunk: (string) A chunk of PHP output.
     */
    public function chunkContent()
    {
        $chunk = basename($vars->chunk);
        $result = new stdClass;
        if (!empty($chunk)) {
            Horde::startBuffer();
            include $GLOBALS['registry']->get('templates', $this->_app) . '/chunks/' . $chunk . '.php';
            $result->chunk = Horde::endBuffer();
        }

        return $result;
    }

    /**
     * Sets a preference value.
     *
     * Variables used:
     *   - pref: (string) The preference name.
     *   - value: (mixed) The preference value.
     *
     * @return boolean  True on success.
     */
    public function setPrefValue()
    {
        return $GLOBALS['prefs']->setValue($this->_vars->pref, $this->_vars->value);
    }

}
