<?php
/**
 * Defines the AJAX interface for an application.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
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
     * Default domain.
     *
     * @see parseEmailAddress()
     * @var string
     */
    protected $_defaultDomain;

    /**
     * Constructor.
     *
     * @param string $app            The application name.
     * @param Horde_Variables $vars  Form/request data.
     * @param string $action         The AJAX action to perform.
     */
    public function __construct($app, $vars, $action = null)
    {
        $this->_app = $app;
        $this->_vars = $vars;

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
     * @throws Horde_Exception
     */
    public function doAction()
    {
        if (!$this->_action) {
            return false;
        }

        if (method_exists($this, $this->_action)) {
            return call_user_func(array($this, $this->_action));
        }

        /* Look for hook in application. */
        try {
            return Horde::callHook('ajaxaction', array($this->_action, $this->_vars), $this->_app);
        } catch (Horde_Exception_HookNotSet $e) {
        } catch (Horde_Exception $e) {
        }

        throw new Horde_Exception('Handler for action "' . $this->_action . '" does not exist.');
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
     * <pre>
     * 'groups' - (array) Groups hash.
     * </pre>
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
     * <pre>
     * 'mbox' - (string) The name of the new mailbox.
     * 'parent' - (string) The parent mailbox.
     * </pre>
     *
     * @return object  Object with the following properties:
     * <pre>
     * 'email' - (string) The parsed email address.
     * </pre>
     *
     * @throws Horde_Exception
     * @throws Horde_Mail_Exception
     */
    public function parseEmailAddress()
    {
        $rfc822 = new Horde_Mail_Rfc822();
        $params = array();
        if ($this->_defaultDomain) {
            $params['default_domain'] = $this->_defaultDomain;
        }
        $res = $rfc822->parseAddressList($this->_vars->email, $params);
        if (!count($res)) {
            throw new Horde_Exception(Horde_Core_Translation::t("No valid email address found"));
        }

        return (object)array(
            'email' => Horde_Mime_Address::writeAddress($res[0]->mailbox, $res[0]->host)
        );
    }

    /**
     * Loads a chunk of PHP code (usually an HTML template) from the
     * application's templates directory.
     *
     * @return object  Object with the following properties:
     * <pre>
     * 'chunk' - (string) A chunk of PHP output.
     * </pre>
     */
    public function chunkContent()
    {
        $chunk = basename(Horde_Util::getPost('chunk'));
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
     * <pre>
     * 'pref' - (string) The preference name.
     * 'value' - (mixed) The preference value.
     * </pre>
     *
     * @return boolean  True on success.
     */
    public function setPrefValue()
    {
        return $GLOBALS['prefs']->setValue($this->_vars->pref, $this->_vars->value);
    }

}
