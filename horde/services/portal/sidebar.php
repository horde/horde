<?php
/**
 * Horde sidebar generation.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael Pawlowsky <mikep@clearskymedia.ca>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/**
 * Determine if the current user can see an application.
 *
 * @param string $app         The application name.
 * @param array $params       The application's parameters.
 * @param array $hasChildren  Reference to an array to set children flags in.
 */
function canSee($app, $params, &$hasChildren)
{
    global $registry;

    static $cache = array();
    static $isAdmin;
    static $user;

    // If we have a cached value for this application, return it now.
    if (isset($cache[$app])) {
        return $cache[$app];
    }

    // Initialize variables we'll keep using in successive calls on
    // the first call.
    if (is_null($isAdmin)) {
        $isAdmin = $registry->isAdmin();
        $user = $registry->getAuth();
    }

    // Check if the current user has permisson to see this application, and if
    // the application is active. Headings are visible to everyone (but get
    // filtered out later if they have no children). Administrators always see
    // all applications except those marked 'inactive'. Anyone with SHOW
    // permissions can see an application, but READ is needed to actually use
    // the application. You can use this distinction to show applications to
    // guests that they need to log in to use. If you don't want them to see
    // apps they can't use, then don't give guests SHOW permissions to
    // anything.
    if (// Don't show applications that aren't installed, even if they're
        // configured.
        (isset($params['fileroot']) && !is_dir($params['fileroot'])) ||

        // Don't show blocks of applications that aren't installed.
        ($params['status'] == 'block' &&
         !is_dir($registry->get('fileroot', $params['app']))) ||

        // Filter out entries that are disabled, hidden or shouldn't show up
        // in the menu.
        $params['status'] == 'notoolbar' || $params['status'] == 'hidden' ||
        $params['status'] == 'inactive') {

        $cache[$app] = false;

    } elseif (// Headings can always be seen.
              ($params['status'] == 'heading') ||

              // Admins see everything that makes it to this point.
              ($isAdmin ||

               // Users who have SHOW permissions to active or block entries
               // see them.
               ($registry->hasPermission($app, Horde_Perms::SHOW) &&
                ($params['status'] == 'active' ||
                 $params['status'] == 'block')))) {

        $cache[$app] = true;

        // Note that the parent node, if any, has children.
        if (isset($params['menu_parent'])) {
            $hasChildren[$params['menu_parent']] = true;
        }
    } else {
        // Catch anything that fell through, and don't show it.
        $cache[$app] = false;
    }

    return $cache[$app];
}

/**
 * Builds the menu structure depending on application permissions.
 */
function buildMenu()
{
    global $conf, $registry;

    $apps = array();
    $children = array();
    foreach ($registry->applications as $app => $params) {
        if (canSee((!empty($params['app']) ? $params['app'] : $app), $params, $children)) {
            $apps[$app] = $params;
        }
    }

    $menu = array();
    foreach ($apps as $app => $params) {
        // Filter out all headings without children.
        if ($params['status'] == 'heading' && empty($children[$app])) {
            continue;
        }

        $menu[$app] = $params;
    }

    // Add the administration menu if the user is an admin.
    if ($registry->isAdmin()) {
        $menu['administration'] = array('name' => _("Administration"),
                                        'icon' => (string)Horde_Themes::img('administration.png'),
                                        'status' => 'heading');

        try {
            $list = $registry->callByPackage('horde', 'admin_list');
            foreach ($list as $method => $vals) {
                $name = Horde::stripAccessKey($vals['name']);
                $icon = isset($vals['icon'])
                    ? Horde_Themes::img($vals['icon'])
                    : $registry->get('icon');

                $menu['administration_' . $method] = array(
                    'name' => $name,
                    'icon' => (string)$icon,
                    'status' => 'active',
                    'menu_parent' => 'administration',
                    'url' => Horde::url($registry->applicationWebPath($vals['link']), 'horde'),
                    );
            }
        } catch (Horde_Exception $e) {}
    }

    if (Horde_Menu::showService('options') &&
        $conf['prefs']['driver'] != '' && $conf['prefs']['driver'] != 'none') {
        $menu['options'] = array('name' => _("Options"),
                                 'status' => 'active',
                                 'icon' => (string)Horde_Themes::img('prefs.png'));

        /* Get a list of configurable applications. */
        $prefs_apps = array();
        foreach ($registry->applications as $application => $params) {
            if ($params['status'] == 'heading' ||
                $params['status'] == 'block' ||
                !file_exists($registry->get('fileroot', $application) . '/config/prefs.php')) {
                continue;
            }

            /* Check if the current user has permission to see this
             * application, and if the application is active.
             * Administrators always see all applications. */
            try {
                if (($registry->isAdmin() && $params['status'] != 'inactive') ||
                    ($registry->hasPermission($application) &&
                     ($params['status'] == 'active'))) {
                    $prefs_apps[$application] = _($params['name']);
                }
            } catch (Horde_Exception $e) {
                // @todo Remove or log instead of notifying when all apps have
                // been H4-ified.
                $GLOBALS['notification']->push($e);
            }
        }

        if (!empty($prefs_apps['horde'])) {
            $menu['options_' . 'horde'] = array('name' => _("Global Options"),
                                                'status' => 'active',
                                                'menu_parent' => 'options',
                                                'icon' => (string)$registry->get('icon', 'horde'),
                                                'url' => Horde::url($registry->get('webroot', 'horde') . '/services/prefs.php?app=horde'));
            unset($prefs_apps['horde']);
        }

        asort($prefs_apps);
        foreach ($prefs_apps as $app => $name) {
            $menu['options_' . $app] = array('name' => $name,
                                             'status' => 'active',
                                             'menu_parent' => 'options',
                                             'icon' => (string)$registry->get('icon', $app),
                                             'url' => Horde::url($registry->get('webroot', 'horde') . '/services/prefs.php?app=' . $app));
        }
    }

    if ($registry->getAuth()) {
        $menu['logout'] = array('name' => _("Log out"),
                                'status' => 'active',
                                'icon' => (string)Horde_Themes::img('logout.png'),
                                'url' => Horde::getServiceLink('logout', 'horde'),
                                'target' => '_parent');
    } else {
        $menu['login'] = array('name' => _("Log in"),
                               'status' => 'active',
                               'icon' => (string)Horde_Themes::img('login.png'),
                               'url' => Horde::getServiceLink('login', 'horde'));
    }

    return $menu;
}

function sidebar()
{
    global $registry, $conf, $language, $prefs;

    // Set up the tree.
    $tree = Horde_Tree::singleton('horde_menu', 'Javascript');
    $menu = buildMenu();
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
            } elseif ($params['status'] == 'heading' || !isset($params['webroot'])) {
                $url = null;
            } else {
                $url = Horde::url($params['webroot'] . '/' . (isset($params['initial_page']) ? $params['initial_page'] : ''));
            }

            $node_params = array('url' => $url,
                                 'target' => isset($params['target']) ? $params['target'] : null,
                                 'icon' => (string)(isset($params['icon']) ? $params['icon'] : $registry->get('icon', $app)),
                                 'icondir' => '',
                                 );
            $tree->addNode($app, !empty($params['menu_parent']) ? $params['menu_parent'] : null, $name, 0, false, $node_params);
        }
    }

    // If we're serving a request to the JS update client, just render the
    // updated node javascript.
    if (Horde_Util::getFormData('httpclient')) {
        header('Content-Type: application/json; charset=' . Horde_Nls::getCharset());
        $scripts = array(
            $tree->renderNodeDefinitions(),
            '$(\'horde_menu\').setStyle({ width: \'auto\', height: \'auto\' });');
        echo Horde::wrapInlineScript($scripts);
        exit;
    }

    $rtl = isset(Horde_Nls::$config['rtl'][$language]);
    Horde::addScriptFile('prototype.js', 'horde');
    Horde::addScriptFile('sidebar.js', 'horde');
    require $GLOBALS['registry']->get('templates', 'horde') . '/portal/sidebar.inc';
}

if (!empty($_GET['httpclient'])) {
    require_once dirname(__FILE__) . '/../../lib/Application.php';
    Horde_Registry::appInit('horde', array('authentication' => 'none'));
}

if (!Horde_Util::getFormData('ajaxui') &&
    ($GLOBALS['conf']['menu']['always'] ||
     ($GLOBALS['registry']->getAuth() &&
      $GLOBALS['prefs']->getValue('show_sidebar')))) {
    sidebar();
}

$GLOBALS['sidebarLoaded'] = true;
echo '<div class="body" style="margin-left:' . $GLOBALS['prefs']->getValue('sidebar_width') . 'px">';
