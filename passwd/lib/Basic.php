<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Base class for basic view pages.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Basic
{
    /**
     * @var array
     */
    private $_backends;

    /**
     * @var string
     */
    private $_output;

    /**
     * User ID.
     *
     * @var string
     */
    private $_userid;

    /**
     * @var Horde_Variables
     */
    private $_vars;

    /**
     */
    public function __construct(Horde_Variables $vars)
    {
        global $conf, $injector, $registry;

        $this->_userid = $registry->getAuth();
        if ($conf['user']['change'] === true) {
            $this->_userid = $vars->get('userid', $this->_userid);
        } else {
            try {
                $this->_userid = Horde::callHook('default_username', array(), 'passwd');
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        $this->_backends = $injector->getInstance('Passwd_Factory_Driver')->backends;
        $this->_vars = $vars;

        $this->_init();
    }

    /**
     */
    public function render()
    {
        echo $this->_output;
    }

    /**
     */
    public function status()
    {
        Horde::startBuffer();
        $GLOBALS['notification']->notify(array('listeners' => array('status')));
        return Horde::endBuffer();
    }

    /**
     */
    private function _init()
    {
        global $conf, $page_output;

        // Get the backend details.
        $backend_key = $this->_vars->backend;
        if (!isset($this->_backends[$backend_key])) {
            $backend_key = null;
        }

        if ($backend_key && $this->_vars->submit) {
            $this->_changePassword($backend_key);
        }

        // Choose the prefered backend from config/backends.php.
        foreach ($this->_backends as $k => $v) {
            if (!isset($backend_key) && (substr($k, 0, 1) != '_')) {
                $backend_key = $k;
            }
            if ($this->_isPreferredBackend($v)) {
                $backend_key = $k;
                break;
            }
        }

        $view = new Horde_View(array(
            'templatePath' => PASSWD_TEMPLATES
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('Tag');

        $view->formInput = Horde_Util::formInput();
        $view->url = $this->_vars->return_to;
        $view->userid = $this->_userid;
        $view->userChange = $conf['user']['change'];
        $view->showlist = ($conf['backend']['backend_list'] == 'shown');
        $view->backend = $backend_key;

        // Build the <select> widget for the backends list.
        if ($view->showlist) {
            $view->backends = $this->_backends;
            $view->header = _("Change your password");
        } else {
            $view->header = sprintf(_("Changing password for %s"), htmlspecialchars($this->_backends[$backend_key]['name']));
        }

        $page_output->sidebar = false;

        $page_output->addScriptFile('stripe.js', 'horde');
        $page_output->addScriptFile('passwd.js');

        $page_output->addInlineJsVars(array(
            'var Passwd' => array(
                'current_pass' => _("Please provide your current password"),
                'new_pass' => _("Please provide a new password"),
                'verify_pass' => _("Please verify your new password"),
                'no_match' => _("Your passwords do not match"),
            )
        ));

        $this->_output = $view->render('index');
    }

    /**
     * @param string $backend_key  Backend key.
     */
    private function _changePassword($backend_key)
    {
        global $conf, $injector, $notification, $registry;

        // Check for users that cannot change their passwords.
        if (in_array($this->_userid, $conf['user']['refused'])) {
            $notification->push(sprintf(_("You can't change password for user %s"), $userid), 'horde.error');
            return;
        }

        // We must be passed the old (current) password.
        if (!isset($this->_vars->oldpassword)) {
            $notification->push(_("You must give your current password"), 'horde.warning');
            return;
        }

        if (!isset($this->_vars->newpassword0)) {
            $notification->push(_("You must give your new password"), 'horde.warning');
            return;
        }
        if (!isset($this->_vars->newpassword1)) {
            $notification->push(_("You must verify your new password"), 'horde.warning');
            return;
        }

        if ($this->_vars->newpassword0 != $this->_vars->newpassword1) {
            $notification->push(_("Your new passwords didn't match"), 'horde.warning');
            return;
        }

        if ($this->_vars->newpassword0 == $this->_vars->oldpassword) {
            $notification->push(_("Your new password must be different from your current password"), 'horde.warning');
            return;
        }

        $b_ptr = $this->_backends[$backend_key];

        try {
            Horde_Auth::checkPasswordPolicy($this->_vars->newpassword0, isset($b_ptr['policy']) ? $b_ptr['policy'] : array());
        } catch (Horde_Auth_Exception $e) {
            $notification->push($e, 'horde.warning');
            return;
        }

        // Do some simple strength tests, if enabled in the config file.
        if (!empty($conf['password']['strengthtests'])) {
            try {
                Horde_Auth::checkPasswordSimilarity($this->_vars->newpassword0, array($this->_userid, $this->_vars->oldpassword));
            } catch (Horde_Auth_Exception $e) {
                $notification->push($e, 'horde.warning');
                return;
            }
        }

        try {
            $driver = $injector->getInstance('Passwd_Factory_Driver')->create($backend_key);
        } catch (Passwd_Exception $e) {
            Horde::log($e);
            $notification->push(_("Password module is not properly configured"), 'horde.error');
            return;
        }

        try {
            $driver->changePassword(
                $this->_userid,
                $this->_vars->oldpassword,
                $this->_vars->newpassword0
            );
        } catch (Exception $e) {
            $notification->push(sprintf(_("Failure in changing password for %s: %s"), $b_ptr['name'], $e->getMessage()), 'horde.error');
            return;
        }

        $notification->push(sprintf(_("Password changed on %s."), $b_ptr['name']), 'horde.success');

        try {
            Horde::callHook('password_changed', array($this->_userid, $this->_vars->oldpassword, $this->_vars->newpassword0), 'passwd');
        } catch (Horde_Exception_HookNotSet $e) {}

        if (!empty($b_ptr['logout'])) {
            $logout_url = $registry->getLogoutUrl(array(
                'msg' => _("Your password has been succesfully changed. You need to re-login to the system with your new password."),
                'reason' => Horde_Auth::REASON_MESSAGE
            ));
            $registry->clearAuth();
            $logout_url->redirect();
        }

        if ($this->_vars->return_to) {
            $url = new Horde_Url($return_to);
            $url->redirect();
        }
    }

    /**
     * Determines if the given backend is the "preferred" backend for this web
     * server.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' field in the
     * backend's definition.  The 'preferred' field may take a single value or
     * an array of multiple values.
     *
     * @param array $backend  A complete backend entry from the $backends
     *                        hash.
     *
     * @return boolean  True if this entry is "preferred".
     */
    private function _isPreferredBackend($backend)
    {
        if (!empty($backend['preferred'])) {
            if (is_array($backend['preferred'])) {
                foreach ($backend['preferred'] as $backend) {
                    if ($backend == $_SERVER['SERVER_NAME'] ||
                        $backend == $_SERVER['HTTP_HOST']) {
                        return true;
                    }
                }
            } elseif ($backend['preferred'] == $_SERVER['SERVER_NAME'] ||
                      $backend['preferred'] == $_SERVER['HTTP_HOST']) {
                return true;
            }
        }

        return false;
    }

}
