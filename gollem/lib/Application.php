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
    define('GOLLEM_BASE', __DIR__ . '/..');
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
    public $version = 'H5 (3.0-git)';

    /**
     * Server key used in logged out session.
     *
     * @var string
     */
    protected $_oldbackend = null;

    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Gollem_Vfs', 'Gollem_Factory_VfsDefault', 'create');
        $GLOBALS['injector']->bindFactory('Gollem_Shares', 'Gollem_Factory_Shares', 'create');
    }

    /**
     */
    protected function _init()
    {
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

        $this->_addSessVars(Gollem_Auth::authenticate(array(
            'password' => $credentials['password'],
            'backend_key' => empty($credentials['backend_key']) ? Gollem_Auth::getPreferredBackend() : $credentials['backend_key'],
            'userId' => $userId
        )));
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
            $this->_addSessVars($result);
            return true;
        }

        return false;
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

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        $icon = Horde_Themes::img('gollem.png');
        $url = Horde::url('manager.php');

        foreach (Gollem_Auth::getBackend() as $key => $val) {
            $tree->addNode(array(
                'id' => $parent . $key,
                'parent' => $parent,
                'label' => $val['name'],
                'expanded' => false,
                'params' => array(
                    'icon' => $icon,
                    'url' => $url->add(array('backend_key' => $key))
                )
            ));
        }
    }

    /* Download data. */

    /**
     * URL parameters needed:
     *   - dir
     *   - driver
     *
     * @throws Horde_Vfs_Exception
     */
    public function download(Horde_Variables $vars)
    {
        $vfs = $GLOBALS['injector']->getInstance('Gollem_Factory_Vfs')->create($vars->driver);
        $res = array(
            'data' => is_callable(array($vfs, 'readStream'))
                ? $vfs->readStream($vars->dir, $vars->filename)
                : $vfs->read($vars->dir, $vars->filename)
        );

        try {
            $res['size'] = $vfs->size($vars->dir, $vars->filename);
        } catch (Horde_Vfs_Exception $e) {}

        return $res;
    }

}
