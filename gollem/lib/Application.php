<?php
/**
 * Gollem application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Amith Varghese <amith@xalan.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Ben Klang <bklang@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
 *  Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Gollem_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Global variables defined:
     *   $gollem_backends - A link to the current list of available backends
     *   $gollem_be - A link to the current backend parameters in the session
     *   $gollem_vfs - A link to the current VFS object for the active backend
     */
    protected function _init()
    {
        return;
        // Load the backend list.
        Gollem::loadBackendList();

        // Set the global $gollem_be variable to the current backend's
        // parameters.
        $backend_key = $GLOBALS['session']->get('gollem', 'backend_key');

        if (empty($backend_key)) {
            // Get the preferred backend
            $backend_key = Gollem::getPreferredBackend();
            if (empty($backend_key)) {
                // Auto-select the first backend
                $backend_key = reset(array_keys($GLOBALS['gollem_backends']));
            }
        }
echo "Backend: $backend_key";
        if ($backend_key === null) {
            $autologin = Horde_Util::getFormData('autologin', false);
        } else {
            $autologin = Horde_Util::getFormData('autologin', $this->_canAutoLogin($backend_key, true));
        }

        if (isset($_SESSION['gollem']) &&
            is_array($_SESSION['gollem']) &&
            ($_SESSION['gollem']['backend_key'] == $backend_key)) {
            
            // Restore an existing session
            $this->_restoreSession();
        } else if (Horde_Util::getFormData('gollem_loginform') ||
            Horde_Util::getFormData('nocredentials') ||
            $autologin) {

            // Set up a new session
            $this->_setupSession($backend_key, $autologin);
        } else {
            // FIXME: Redirect to login page
            die('FIXME: Auth required but not implemented!');
        }

        $GLOBALS['gollem_be'] =
            $GLOBALS['session']->get('gollem', 'backends/' . $backend_key);

    }

    private function _setupSession($backend_key, $autologin)
    {
          // FIXME: How is per-app auth handled now?
//        if (Horde_Auth::getProvider() == 'gollem') {
//            /* Destroy any existing session on login and make sure to use
//             * a new session ID, to avoid session fixation issues. */
//            Horde::getCleanSession();
//        }

        /* Get the required parameters from the form data. */
        $args = array();
        if (isset($GLOBALS['gollem_backends'][$backend_key]['loginparams'])) {
            $postdata = array_keys($GLOBALS['gollem_backends'][$backend_key]['loginparams']);
        } else {
            $postdata = array();
        }
        if (empty($autologin)) {
            // Allocate a global VFS object
            $GLOBALS['gollem_vfs'] = &Gollem::getVFSOb($backend_key, array());
            if (is_a($GLOBALS['gollem_vfs'], 'PEAR_Error')) {
                Horde::fatal($GLOBALS['gollem_vfs']);
            }

            $postdata = array_merge($postdata, $GLOBALS['gollem_vfs']->getRequiredCredentials());
        } else {
            /* We are attempting autologin.  If hordeauth is off, we need to make
             * sure we are not trying to use horde auth info to login. */
            if (empty($GLOBALS['gollem_backends'][$backend_key]['hordeauth'])) {
                $pass = Horde_Util::getPost('password');
            }
        }

        foreach ($postdata as $val) {
            $args[$val] = Horde_Util::getPost($val);
        }

        require_once GOLLEM_BASE . '/lib/Session.php';
        if (Gollem_Session::createSession($backend_key, $user, $pass, $args)) {
            $entry = sprintf('Login success for User %s [%s] using backend %s.', $GLOBALS['registry']->getAuth(), $_SERVER['REMOTE_ADDR'], $backend_key);
            Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_NOTICE);

            $ie_version = Horde_Util::getFormData('ie_version');
            if ($ie_version) {
                $browser->setIEVersion($ie_version);
            }

            if (($horde_language = Horde_Util::getFormData('new_lang'))) {
                $_SESSION['horde_language'] = $horde_language;
            }

            if (!empty($url_in)) {
                $url = Horde::url(Horde_Util::removeParameter($url_in, session_name()), true);
                if ($actionID) {
                    $url = Horde_Util::addParameter($url, 'actionID', $actionID, false);
                }
            // FIXME: How is per-app auth handled now?
            //} elseif (Horde_Auth::getProvider() == 'gollem') {
            //    $url = Horde::applicationUrl($registry->get('webroot', 'horde') . '/index.php', true);
            //} else {
                $url = Horde::applicationUrl('manager.php', true);
            }
        } else {
            $url = Horde_Util::addParameter(Horde_Auth::addLogoutParameters(Gollem::logoutUrl()), 'backend_key', $backend_key, false);
            if (!empty($autologin)) {
                $url = Horde_Util::addParameter($url, 'autologin_fail', '1', false);
            }
        }

        if (Horde_Util::getFormData('load_frameset')) {
            $full_url = Horde::applicationUrl($registry->get('webroot', 'horde') . '/index.php', true);
            $url = Horde_Util::addParameter($full_url, 'url', _addAnchor($url, 'param'), false);
        }

        //header('Refresh: 0; URL=' . _addAnchor($url, 'url'));
        //exit;

    }

    private function _restoreSession()
    {
        $backend_key = $GLOBALS['session']->get('gollem', 'backend_key');
        
        $user = (empty($autologin)) ? Horde_Util::getPost('username') : Gollem::getAutologinID($backend_key);
        $pass = (empty($autologin)) ? Horde_Util::getPost('password') : Horde_Auth::getCredential('password');

        /* Make sure that if a username was specified, it is the current
         * username. */
        if ((($user === null) ||
             ($user == $GLOBALS['gollem_be']['params']['username'])) &&
            (($pass === null) ||
             ($pass == Secret::read(Secret::getKey('gollem'), $GLOBALS['gollem_be']['params']['password'])))) {
            $url = $url_in;
            if (empty($url)) {
                $url = Horde::applicationUrl('manager.php', true);
            } elseif (!empty($actionID)) {
                $url = Horde_Util::addParameter($url, 'actionID', $actionID);
            }

            if (Horde_Util::getFormData('load_frameset')) {
                $full_url = Horde::applicationUrl($registry->get('webroot', 'horde') . '/index.php', true);
                $url = Horde_Util::addParameter($full_url, 'url', _addAnchor($url, 'param'), false);
            }

            header('Refresh: 0; URL=' . _addAnchor($url, 'url'));
            exit;
        } else {
            /* Disable the old session. */
            unset($_SESSION['gollem']);
            header('Location: ' . Horde_Auth::addLogoutParameters(Gollem::logoutUrl(), AUTH_REASON_FAILED));
            exit;
        }
    }

    private function _canAutoLogin($key)
    {
        return ($GLOBALS['registry']->getAuth() &&
                empty($GLOBALS['gollem_backends'][$key]['loginparams']) &&
                !empty($GLOBALS['gollem_backends'][$key]['hordeauth']));
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
        require GOLLEM_BASE . '/config/backends.php';
        foreach ($backends as $key => $val) {
            $perms['backends:' . $key] = array(
                'title' => $val['name']
            );
        }

        return $perms;
    }

    /**
     */
    public function prefsGroup($ui)
    {
        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'columns':
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

            foreach ($GLOBALS['gollem_backends'] as $source => $info) {
                $selected = $unselected = array();
                $selected_list = isset($cols[$source])
                    ? array_flip($cols[$source])
                    : array();

                foreach ($info['attributes'] as $column) {
                    if (isset($selected_list[$column])) {
                        $selected[] = array($column, $column);
                    } else {
                        $unselected[] = array($column, $column);
                    }
                }
                $sources[$source] = array(
                    'selected' => $selected,
                    'unselected' => $unselected,
                );
            }

            return Horde_Core_Prefs_Ui_Widgets::source(array(
                'mainlabel' => _("Choose which backends to display, and in what order:"),
                'selectlabel' => _("These backends will display in this order:"),
                'sourcelabel' => _("Select a backend:"),
                'sources' => $sources,
                'unselectlabel' => _("Backends that will not be displayed:")
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
        // TODO
        return;

        $login_url = Horde::url('login.php');

        foreach ($GLOBALS['gollem_backends'] as $key => $val) {
            if (Gollem::checkPermissions('backend', Horde_Perms::SHOW, $key)) {
                $tree->addNode(
                    $parent . $key,
                    $parent,
                    $val['name'],
                    1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('gollem.png'),
                        'url' => $login_url->copy()->add(array('backend_key' => $key, 'change_backend' => 1))
                    )
                );
            }
        }
    }

}
