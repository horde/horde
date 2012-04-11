<?php
/**
 * Gollem application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Ben Klang <bklang@horde.org>
 * @author   Amith Varghese <amith@xalan.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

/* Determine the base directories. */
if (!defined('GOLLEM_BASE')) {
    define('GOLLEM_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(GOLLEM_BASE . '/config/horde.local.php')) {
        include GOLLEM_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', GOLLEM_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Gollem_Application extends Horde_Registry_Application
{
    /**
     */
    public $auth = array(
        'authenticate',
        'transparent',
        'validate'
    );

    /**
     */
    public $version = 'H4 (2.0.3-git)';

    /**
     * Cached values to add to the session after authentication.
     *
     * @var array
     */
    protected $_cacheSess = array();

    /**
     * Server key used in logged out session.
     *
     * @var string
     */
    protected $_oldbackend = null;

    /**
     */
    protected function _init()
    {
        $GLOBALS['injector']->bindFactory('Gollem_Vfs', 'Gollem_Factory_VfsDefault', 'create');

        if ($backend_key = $GLOBALS['session']->get('gollem', 'backend_key')) {
            Gollem_Auth::changeBackend($backend_key);
        }
    }

    /**
     */
    public function perms()
    {
        $perms = array(
            'backends' => array(
                'title' => _("Backends")
            )
        );

        // Run through every backend.
        foreach (Gollem_Auth::getBackend() as $key => $val) {
            $perms['backends:' . $key] = array(
                'title' => $val['name']
            );
        }

        return $perms;
    }

    /* Horde_Core_Auth_Application methods. */

    /**
     * Return login parameters used on the login page.
     *
     * @return array  See Horde_Core_Auth_Application#authLoginParams().
     */
    public function authLoginParams()
    {
        $params = array();

        if ($GLOBALS['conf']['backend']['backend_list'] == 'shown') {
            $backend_list = array();
            $selected = is_null($this->_oldbackend)
                ? Horde_Util::getFormData('backend_key', Gollem_Auth::getPreferredBackend())
                : $this->_oldbackend;

            foreach (Gollem_Auth::getBackend() as $key => $val) {
                $backend_list[$key] = array(
                    'name' => $val['name'],
                    'selected' => ($selected == $key)
                );
                if ($selected == $key) {
                    if (!empty($val['loginparams'])) {
                        foreach ($val['loginparams'] as $param => $label) {
                            $params[$param] = array(
                                'label' => $label,
                                'type' => 'text',
                                'value' => isset($val['params'][$param]) ? $val['params'][$param] : ''
                            );
                        }
                    }
                    if (Gollem_Auth::canAutoLogin($key)) {
                        $params['horde_user'] = null;
                        $params['horde_pass'] = null;
                    }
                }
            }
            $params['backend_key'] = array(
                'label' => _("Backend"),
                'type' => 'select',
                'value' => $backend_list
            );
        }

        return array(
            'js_code' => array(),
            'js_files' => array(array('login.js', 'gollem')),
            'params' => $params
        );
    }

    /**
     * Tries to authenticate with the server and create a session.
     *
     * @param string $userId      The username of the user.
     * @param array $credentials  Credentials of the user. Allowed keys:
     *                            'backend', 'password'.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAuthenticate($userId, $credentials)
    {
        $this->init();

        $new_session = Gollem_Auth::authenticate(array(
            'password' => $credentials['password'],
            'backend_key' => empty($credentials['backend']) ? Gollem_Auth::getPreferredBackend() : $credentials['backend'],
            'userId' => $userId
        ));

        if ($new_session) {
            $this->_cacheSess = $new_session;
        }
    }

    /**
     * Tries to transparently authenticate with the server and create a
     * session.
     *
     * @param Horde_Core_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    public function authTransparent($auth_ob)
    {
        $this->init();

        if ($result = Gollem_Auth::transparent($auth_ob)) {
            $this->_cacheSess = $result;
            return true;
        }

        return false;
    }

    /**
     * Does necessary authentication tasks reliant on a full app environment.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAuthenticateCallback()
    {
        if ($GLOBALS['registry']->getAuth()) {
            $this->init();

            foreach ($this->_cacheSess as $key => $val) {
                $GLOBALS['session']->set('gollem', $key, $val);
            }
            $this->_cacheSess = array();
        }
    }

    /**
     * Validates an existing authentication.
     *
     * @return boolean  Whether the authentication is still valid.
     */
    public function authValidate()
    {
        if (($backend_key = Horde_Util::getFormData('backend_key')) &&
            $backend_key != $GLOBALS['session']->get('gollem', 'backend_key')) {
            Gollem_Auth::changeBackend($backend_key);
        }

        return !empty(Gollem::$backend['auth']);
    }

    /**
     */
    public function prefsGroup($ui)
    {
        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'columnselect':
                Horde_Core_Prefs_Ui_Widgets::sourceInit();
                break;
            }
        }
    }

    /**
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'columnselect':
            $cols = json_decode($GLOBALS['prefs']->getValue('columns'));
            $sources = array();

            foreach (Gollem_Auth::getBackend() as $source => $info) {
                $selected = $unselected = array();
                $selected_list = isset($cols[$source])
                    ? array_flip($cols[$source])
                    : array();

                foreach ($info['attributes'] as $column) {
                    if (isset($selected_list[$column])) {
                        $selected[$column] = $column;
                    } else {
                        $unselected[$column] = $column;
                    }
                }
                $sources[$source] = array(
                    'selected' => $selected,
                    'unselected' => $unselected,
                );
            }

            return Horde_Core_Prefs_Ui_Widgets::source(array(
                'mainlabel' => _("Choose which columns to display, and in what order:"),
                'selectlabel' => _("These columns will display in this order:"),
                'sourcelabel' => _("Select a backend:"),
                'sources' => $sources,
                'unselectlabel' => _("Columns that will not be displayed:")
            ));
        }

        return '';
    }

    /**
     */
    public function menu($menu)
    {
        $backend_key = Gollem_Auth::getPreferredBackend();

        $menu->add(Horde::url('manager.php')->add('dir', Gollem::$backend['home']), _("_My Home"), 'folder_home.png');

        if ($GLOBALS['registry']->isAdmin()) {
            $menu->add(Horde::url('permissions.php')->add('backend', $backend_key), _("_Permissions"), 'perms.png');
        }

        if (isset(Gollem::$backend['quota_val']) &&
            Gollem::$backend['quota_val'] != -1) {
            if ($GLOBALS['browser']->hasFeature('javascript')) {
                $quota_url = 'javascript:' . Horde::popupJs(Horde::url('quota.php'), array('params' => array('backend' => $backend_key), 'height' => 300, 'width' => 300, 'urlencode' => true));
            } else {
                $quota_url = Horde::url('quota.php')->add('backend', $backend_key);
            }
            $menu->add($quota_url, _("Check Quota"), 'info_icon.png');
        }
    }

    /* Sidebar method. */

    /**
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        $icon = Horde_Themes::img('gollem.png');
        $url = Horde::url('manager.php');

        foreach (Gollem_Auth::getBackend() as $key => $val) {
            $tree->addNode(
                $parent . $key,
                $parent,
                $val['name'],
                1,
                false,
                array(
                    'icon' => $icon,
                    'url' => $url->add(array('backend_key' => $key))
                )
            );
        }
    }

}
