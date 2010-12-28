<?php
/**
 * This class provides the code needed to generate the Horde sidebar.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Sidebar
{
    /**
     * Generate the sidebar tree object.
     *
     * @return Horde_Tree_Base  The sidebar tree object.
     */
    public function getTree()
    {
        global $injector, $registry;

        $isAdmin = $registry->isAdmin();
        $menu = $parents = array();

        foreach ($registry->listApps(array('active', 'admin', 'heading', 'notoolbar', 'sidebar'), true, null) as $app => $params) {
            /* Check if the current user has permisson to see this
             * application, and if the application is active. Headings are
             * visible to everyone (but get filtered out later if they
             * have no children). Administrators always see all
             * applications except those marked 'inactive'. */
            if ($isAdmin ||
                ($params['status'] == 'heading') ||
                (in_array($params['status'], array('active', 'sidebar')) &&
                 $registry->hasPermission($app, Horde_Perms::SHOW))) {
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

        // Add the administration menu if the user is an admin.
        if ($isAdmin) {
            $menu['administration'] = array(
                'name' => Horde_Core_Translation::t("Administration"),
                'icon' => Horde_Themes::img('administration.png'),
                'status' => 'heading'
            );

            try {
                foreach ($registry->callByPackage('horde', 'admin_list') as $method => $val) {
                    $menu['administration_' . $method] = array(
                        'icon' => $val['icon'],
                        'menu_parent' => 'administration',
                        'name' => Horde::stripAccessKey($val['name']),
                        'status' => 'active',
                        'url' => Horde::url($registry->applicationWebPath($val['link'], 'horde')),
                    );
                }
            } catch (Horde_Exception $e) {}
        }

        if (Horde_Menu::showService('prefs') &&
            !($injector->getInstance('Horde_Core_Factory_Prefs')->create() instanceof Horde_Prefs_Session)) {
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

            asort($prefs_apps);
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

        // Set up the tree.
        $tree = $injector->getInstance('Horde_Core_Factory_Tree')->create('horde_sidebar', 'Javascript', array('jsvar' => 'HordeSidebar.tree'));

        foreach ($menu as $app => $params) {
            switch ($params['status']) {
            case 'sidebar':
                try {
                    $registry->callAppMethod($params['app'], 'sidebarCreate', array('args' => array($tree, empty($params['menu_parent']) ? null : $params['menu_parent'], isset($params['sidebar_params']) ? $params['sidebar_params'] : array())));
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

                $tree->addNode(
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

        return $tree;
    }

}
