<?php
/**
 * The Horde_Ui_Sidebar:: class is designed to provide a place to store common
 * code for sidebar generation.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
class Horde_Ui_Sidebar
{
    /**
     * Generate the sidebar tree object.
     *
     * @return Horde_Tree  The sidebar tree object.
     */
    public function getTree()
    {
        global $conf, $injector, $prefs, $registry, $notification;

        $apps = $cache = $children = $menu = array();

        $isAdmin = $registry->isAdmin();
        $user = $registry->getAuth();
        foreach ($registry->applications as $app => $params) {
            $curr_app = empty($params['app'])
                ? $app
                : $params['app'];

            if (!isset($cache[$curr_app])) {
                /* Check if the current user has permisson to see this
                 * application, and if the application is active. Headings are
                 * visible to everyone (but get filtered out later if they
                 * have no children). Administrators always see all
                 * applications except those marked 'inactive'. Anyone with
                 * SHOW permissions can see an application, but READ is needed
                 * to actually use the application. You can use this
                 * distinction to show applications to guests that they need
                 * to log in to use. If you don't want them to see apps they
                 * can't use, then don't give guests SHOW permissions to
                 * anything. */

                /* Don't show applications that aren't installed, even if
                 * they're configured.
                 * -OR-
                 * Don't show blocks of applications that aren't installed.
                 * -OR-
                 * Filter out entries that are disabled, hidden or shouldn't
                 * show up in the menu. */
                if ((isset($params['fileroot']) &&
                     !is_dir($params['fileroot'])) ||
                    (($params['status'] == 'block') &&
                     !is_dir($registry->get('fileroot', $params['app']))) ||
                    (in_array($params['status'], array('hidden', 'inactive', 'notoolbar')))) {
                    $cache[$curr_app] = false;
                } elseif (($params['status'] == 'heading') ||
                          ($isAdmin ||
                           ($registry->hasPermission($curr_app, Horde_Perms::SHOW) &&
                            (($params['status'] == 'active') ||
                             ($params['status'] == 'block'))))) {
                    $cache[$curr_app] = true;

                    // Note that the parent node, if any, has children.
                    if (isset($params['menu_parent'])) {
                        $children[$params['menu_parent']] = true;
                    }
                } else {
                    // Catch anything that fell through, and don't show it.
                    $cache[$curr_app] = false;
                }
            }

            if ($cache[$curr_app]) {
                $apps[$app] = $params;
            }
        }

        foreach ($apps as $app => $params) {
            // Filter out all headings without children.
            if (($params['status'] != 'heading') || !empty($children[$app])) {
                $menu[$app] = $params;
            }
        }

        // Add the administration menu if the user is an admin.
        if ($registry->isAdmin()) {
            $menu['administration'] = array(
                'name' => _("Administration"),
                'icon' => strval(Horde_Themes::img('administration.png')),
                'status' => 'heading'
            );

            try {
                $list = $registry->callByPackage('horde', 'admin_list');
                foreach ($list as $method => $vals) {
                    $name = Horde::stripAccessKey($vals['name']);
                    $icon = isset($vals['icon'])
                        ? Horde_Themes::img($vals['icon'])
                        : $registry->get('icon');

                    $menu['administration_' . $method] = array(
                        'name' => $name,
                        'icon' => strval($icon),
                        'status' => 'active',
                        'menu_parent' => 'administration',
                        'url' => Horde::url($registry->applicationWebPath($vals['link'], 'horde')),
                    );
                }
            } catch (Horde_Exception $e) {}
        }

        if (Horde_Menu::showService('options') &&
            ($conf['prefs']['driver'] != '') &&
            ($conf['prefs']['driver'] != 'none')) {
            $menu['options'] = array(
                'name' => _("Options"),
                'icon' => strval(Horde_Themes::img('prefs.png')),
                'status' => 'active'
            );

            /* Get a list of configurable applications. */
            $prefs_apps = array();
            foreach ($registry->applications as $application => $params) {
                if (($params['status'] == 'heading') ||
                    ($params['status'] == 'block') ||
                    !file_exists($registry->get('fileroot', $application) . '/config/prefs.php')) {
                    continue;
                }

                /* Check if the current user has permission to see this
                 * application, and if the application is active.
                 * Administrators always see all applications. */
                try {
                    if (($registry->isAdmin() &&
                         ($params['status'] != 'inactive')) ||
                        ($registry->hasPermission($application) &&
                         ($params['status'] == 'active'))) {
                        $prefs_apps[$application] = _($params['name']);
                    }
                } catch (Horde_Exception $e) {
                    /* @todo Remove or log instead of notifying when all apps
                     * have been H4-ified. */
                    $notification->push($e);
                }
            }

            if (!empty($prefs_apps['horde'])) {
                $menu['options_' . 'horde'] = array(
                    'name' => _("Global Options"),
                    'status' => 'active',
                    'menu_parent' => 'options',
                    'icon' => strval($registry->get('icon', 'horde')),
                    'url' => strval(Horde::getServiceLink('options', 'horde'))
                );
                unset($prefs_apps['horde']);
            }

            asort($prefs_apps);
            foreach ($prefs_apps as $app => $name) {
                $menu['options_' . $app] = array(
                    'name' => $name,
                    'status' => 'active',
                    'menu_parent' => 'options',
                    'icon' => strval($registry->get('icon', $app)),
                    'url' => strval(Horde::getServiceLink('options', $app))
                );
            }
        }

        if ($registry->getAuth()) {
            $menu['logout'] = array(
                'name' => _("Log out"),
                'status' => 'active',
                'icon' => strval(Horde_Themes::img('logout.png')),
                'url' => Horde::getServiceLink('logout', 'horde'),
                'target' => '_parent'
            );
        } else {
            $menu['login'] = array(
                'name' => _("Log in"),
                'status' => 'active',
                'icon' => strval(Horde_Themes::img('login.png')),
                'url' => Horde::getServiceLink('login', 'horde')
            );
        }

        // Set up the tree.
        $tree = $injector->getInstance('Horde_Tree')->getTree('horde_sidebar', 'Javascript', array('jsvar' => 'HordeSidebar.tree'));

        foreach ($menu as $app => $params) {
            if ($params['status'] == 'block') {
                if ($registry->get('status', $params['app']) == 'inactive') {
                    continue;
                }

                try {
                    $block = Horde_Block_Collection::getBlock($params['app'], $params['blockname']);
                } catch (Horde_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    continue;
                }

                try {
                    $block->buildTree($tree, 0, isset($params['menu_parent']) ? $params['menu_parent'] : null);
                } catch (Horde_Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    continue;
                }
            } else {
                // Need to run the name through gettext since the user's
                // locale may not have been loaded when registry.php was
                // parsed.
                $name = _($params['name']);

                // Headings have no webroot; they're just containers for other
                // menu items.
                if (isset($params['url'])) {
                    $url = $params['url'];
                } elseif (($params['status'] == 'heading') ||
                          !isset($params['webroot'])) {
                    $url = null;
                } else {
                    $url = Horde::url($params['webroot'] . '/' . (isset($params['initial_page']) ? $params['initial_page'] : ''));
                }

                $node_params = array(
                    'icon' => strval((isset($params['icon']) ? $params['icon'] : $registry->get('icon', $app))),
                    'target' => isset($params['target']) ? $params['target'] : null,
                    'url' => $url
                );

                $tree->addNode($app, empty($params['menu_parent']) ? null : $params['menu_parent'], $name, 0, false, $node_params);
            }
        }

        return $tree;
    }

}
