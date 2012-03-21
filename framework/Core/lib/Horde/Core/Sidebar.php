<?php
/**
 * This class provides the code needed to generate the Horde sidebar.
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
class Horde_Core_Sidebar
{
    /**
     * A tree object.
     *
     * @var Horde_Tree_Base
     */
    protected $_tree;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set up the tree.
        $this->_tree = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Tree')
            ->create('horde_menu', 'Javascript',
                     array('jsvar' => 'HordeSidebar.tree'));
    }

    /**
     * Returns whether the sidebar tree is rendered through JavaScript.
     *
     * @return boolean  True if the sidebar is a JavaScript tree.
     */
    public function isJavascript()
    {
        return $this->_tree instanceof Horde_Core_Tree_Javascript;
    }

    /**
     * Returns the current tree object, without adding any nodes or further
     * processing.
     *
     * @return Horde_Tree_Base  The current tree object.
     */
    public function getBaseTree()
    {
        return $this->_tree;
    }

    /**
     * Generates the sidebar tree object.
     *
     * @return Horde_Tree_Base  The sidebar tree object.
     */
    public function getTree()
    {
        global $registry;

        $isAdmin = $registry->isAdmin();
        $menu = $children = array();

        foreach ($registry->listApps(array('active', 'admin', 'noadmin', 'heading', 'notoolbar', 'sidebar'), true, null) as $app => $params) {
            /* Check if the current user has permisson to see this application,
             * and if the application is active. Headings are visible to
             * everyone (but get filtered out later if they have no
             * children). Administrators always see all applications except
             * those marked 'inactive'. */
            if ($params['status'] == 'heading' ||
                (in_array($params['status'], array('active', 'admin', 'noadmin', 'sidebar')) &&
                 $registry->hasPermission((!empty($params['app']) ? $params['app'] : $app), Horde_Perms::SHOW) &&
                 !($isAdmin && $params['status'] == 'noadmin'))) {
                $menu[$app] = $params;

                if (isset($params['menu_parent'])) {
                    $children[$params['menu_parent']] = true;
                }
            }
        }

        foreach (array_keys($menu) as $key) {
            if (($menu[$key]['status'] == 'heading') &&
                !isset($children[$key])) {
                unset($menu[$key]);
            }
        }

        /* Add the administration menu if the user is an admin or has any admin
         * permissions. */
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $admin_item_count = 0;
        try {
            foreach ($registry->callByPackage('horde', 'admin_list') as $method => $val) {
                if ($isAdmin ||
                    $perms->hasPermission('horde:administration:' . $method, $registry->getAuth(), Horde_Perms::SHOW)) {
                    $admin_item_count++;
                    $menu['administration_' . $method] = array(
                        'icon' => $val['icon'],
                        'menu_parent' => 'administration',
                        'name' => Horde::stripAccessKey($val['name']),
                        'status' => 'active',
                        'url' => Horde::url($registry->applicationWebPath($val['link'], 'horde')),
                    );
                }
            }
        } catch (Horde_Exception $e) {
        }

        if ($admin_item_count) {
            $menu['administration'] = array(
                'name' => Horde_Core_Translation::t("Administration"),
                'icon' => Horde_Themes::img('administration.png'),
                'status' => 'heading'
            );
        }

        if (Horde_Menu::showService('prefs') &&
            !($GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create() instanceof Horde_Prefs_Session)) {
            $menu['prefs'] = array(
                'icon' => Horde_Themes::img('prefs.png'),
                'name' => Horde_Core_Translation::t("Preferences"),
                'status' => 'active'
            );

            /* Get a list of configurable applications. */
            $prefs_apps = $registry->listApps(array('active', 'admin'), true, Horde_Perms::READ);

            if (!empty($prefs_apps['horde'])) {
                $menu['prefs_' . 'horde'] = array(
                    'icon' => $registry->get('icon', 'horde'),
                    'menu_parent' => 'prefs',
                    'name' => Horde_Core_Translation::t("Global Preferences"),
                    'status' => 'active',
                    'url' => Horde::getServiceLink('prefs', 'horde')
                );
                unset($prefs_apps['horde']);
            }

            uasort($prefs_apps, array($this, '_sortByName'));
            foreach ($prefs_apps as $app => $params) {
                $menu['prefs_' . $app] = array(
                    'icon' => $registry->get('icon', $app),
                    'menu_parent' => 'prefs',
                    'name' => $params['name'],
                    'status' => 'active',
                    'url' => Horde::getServiceLink('prefs', $app)
                );
            }
        }

        if ($registry->getAuth()) {
            $menu['logout'] = array(
                'icon' => Horde_Themes::img('logout.png'),
                'name' => Horde_Core_Translation::t("Log out"),
                'status' => 'active',
                'url' => Horde::getServiceLink('logout', 'horde')
            );
        } else {
            $menu['login'] = array(
                'icon' => Horde_Themes::img('login.png'),
                'name' => Horde_Core_Translation::t("Log in"),
                'status' => 'active',
                'url' => Horde::getServiceLink('login', 'horde')
            );
        }

        foreach ($menu as $app => $params) {
            switch ($params['status']) {
            case 'sidebar':
                try {
                    $registry->callAppMethod($params['app'], 'sidebarCreate', array('args' => array($this->_tree, empty($params['menu_parent']) ? null : $params['menu_parent'], isset($params['sidebar_params']) ? $params['sidebar_params'] : array())));
                } catch (Horde_Exception $e) {
                    if ($e->getCode() != Horde_Registry::NOT_ACTIVE) {
                        Horde::logMessage($e, 'ERR');
                    }
                }
                break;

            default:
                // Need to run the name through Horde's gettext since the
                // user's locale may not have been loaded when registry.php was
                // parsed, and the translations of the application names are
                // not in the Core package.
                $name = _($params['name']);

                // Headings have no webroot; they're just containers for other
                // menu items.
                if (isset($params['url'])) {
                    $url = $params['url'];
                } elseif (($params['status'] == 'heading') ||
                          !isset($params['webroot'])) {
                    $url = null;
                } else {
                    $url = Horde::url($registry->getInitialPage($app), false, array('app' => $app));
                }

                $this->_tree->addNode(
                    $app,
                    empty($params['menu_parent']) ? null : $params['menu_parent'],
                    $name,
                    0,
                    false,
                    array(
                        'icon' => strval((isset($params['icon']) ? $params['icon'] : $registry->get('icon', $app))),
                        'target' => isset($params['target']) ? $params['target'] : null,
                        'url' => $url
                    )
                );
                break;
            }
        }

        return $this->_tree;
    }

    /**
     * Helper method for uasort to sort applications by name.
     *
     * @param string $a
     * @param string $a
     *
     * @return integer
     */
    protected function _sortByName($a, $b)
    {
        return strcoll(_($a['name']), _($b['name']));
    }
}
