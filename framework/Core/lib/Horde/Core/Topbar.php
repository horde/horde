<?php
/**
 * This class provides the code needed to generate the Horde topbar.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Topbar
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
        /* Set up the tree. */
        $this->_tree = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Tree')
            ->create('horde_menu', 'Horde_Core_Tree_Menu', array('nosession' => true));
    }

    /**
     * Generates the topbar tree object.
     *
     * @return Horde_Tree_Base  The topbar tree object.
     */
    public function getTree()
    {
        global $registry;

        $isAdmin = $registry->isAdmin();
        $current = $registry->getApp();
        $menu = $children = array();

        foreach ($registry->listApps(array('active', 'admin', 'noadmin', 'heading', 'notoolbar', 'topbar'), true, null) as $app => $params) {
            /* Check if the current user has permisson to see this application,
             * and if the application is active. Headings are visible to
             * everyone (but get filtered out later if they have no
             * children). Administrators always see all applications except
             * those marked 'inactive'. */
            if ($params['status'] == 'heading' ||
                (in_array($params['status'], array('active', 'admin', 'noadmin', 'topbar')) &&
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

        if (!$admin_item_count) {
            unset($menu['administration']);
        }

        foreach ($menu as $app => $params) {
            switch ($params['status']) {
            case 'topbar':
                try {
                    $registry->callAppMethod($params['app'], 'topbarCreate', array('args' => array($this->_tree, empty($params['menu_parent']) ? null : $params['menu_parent'], isset($params['topbar_params']) ? $params['topbar_params'] : array())));
                } catch (Horde_Exception $e) {
                    if ($e->getCode() != Horde_Registry::NOT_ACTIVE) {
                        Horde::logMessage($e, 'ERR');
                    }
                }
                break;

            default:
                /* Need to run the name through Horde's gettext since the
                 * user's locale may not have been loaded when registry.php was
                 * parsed, and the translations of the application names are
                 * not in the Core package. */
                $name = _($params['name']);

                /* Headings have no webroot; they're just containers for other
                 * menu items. */
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
                        'url' => $url,
                        'active' => $app == $current,
                    )
                );
                break;
            }
        }

        return $this->_tree;
    }

    public function render()
    {
        $view = $GLOBALS['injector']->getInstance('Horde_View');
        $view->setTemplatePath($GLOBALS['registry']->get('templates', 'horde') . '/topbar');

        if (class_exists('Horde_Bundle')) {
            $view->version = Horde_Bundle::SHORTNAME . ' ' . Horde_Bundle::VERSION;
        } else {
            $view->version = $GLOBALS['registry']->getVersion('horde');
        }
        $view->menu = $this->getTree()->getTree();

        return $view->render('topbar');
    }
}
