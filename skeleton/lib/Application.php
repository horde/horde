<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Your Name <you@example.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Skeleton
 */

/* Determine the base directories. */
if (!defined('SKELETON_BASE')) {
    define('SKELETON_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(SKELETON_BASE . '/config/horde.local.php')) {
        include SKELETON_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(SKELETON_BASE . '/..'));
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

/**
 * Skeleton application API.
 *
 * This class defines Horde's core API interface. Other core Horde libraries
 * can interact with Skeleton through this API.
 *
 * @author   Your Name <you@example.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Skeleton
 */
class Skeleton_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H5 (0.1-git)';

    /**
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Skeleton_Driver', 'Skeleton_Factory_Driver', 'create');
    }

    /**
     * Adds items to the sidebar menu.
     *
     * Simple sidebar menu entries go here. More complex entries are added in
     * the sidebar() method.
     *
     * @param $menu Horde_Menu  The sidebar menu.
     */
    public function menu($menu)
    {
        /* If index.php == lists.php, jump some extra loops to highlight the
         * menu entry. */
        $menu->add(
            Horde::url('list.php'),
            _("List"),
            'skeleton-list',
            null,
            null,
            null,
            basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        /* A regular entry. */
        $menu->add(Horde::url('data.php'), _("Import/Export"), 'horde-data');
    }

    /**
     * Adds additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        $sidebar->addNewButton(
            _("_Add Item"),
            Horde::url('new.php')
        );

        /* Checkbox lists are for resources that can be incrementally added to
         * the current content. */
        $sidebar->containers['foo'] = array(
            'header' => array(
                'id' => 'skeleton-toggle-foo',
                'label' => _("Foo"),
                'collapsed' => false,
                'add' => array(
                    'url' => Horde::url('foo.php'),
                    'label' => _("Create a new Foo"),
                ),
            ),
        );
        $sidebar->addRow(
            array(
                'selected' => true,
                'url' => Horde::url('foo.php')->add('foo', 1),
                'label' => _("One"),
                'color' => '#113355',
                'edit' => Horde::url('edit.php')->add('foo', 1),
                'type' => 'checkbox',
            ),
            'foo'
        );
        $sidebar->addRow(
            array(
                'selected' => false,
                'url' => Horde::url('foo.php')->add('foo', 2),
                'label' => _("Two"),
                'color' => '#557799',
                'type' => 'checkbox',
            ),
            'foo'
        );

        /* Radiobox lists are for resources that can be displayed mutually
         * exclusive in the current content. */
        $sidebar->containers['bar'] = array(
            'header' => array(
                'id' => 'skeleton-toggle-bar',
                'label' => _("Bar"),
                'collapsed' => true,
            ),
        );
        $sidebar->addRow(
            array(
                'selected' => true,
                'url' => Horde::url('bar.php')->add('bar', 1),
                'label' => _("One"),
                'color' => '#553311',
                'edit' => Horde::url('edit.php')->add('bar', 1),
                'type' => 'radiobox',
            ),
            'bar'
        );
        $sidebar->addRow(
            array(
                'selected' => false,
                'url' => Horde::url('bar.php')->add('bar', 2),
                'label' => _("Two"),
                'color' => '#997755',
                'type' => 'radiobox',
            ),
            'bar'
        );
    }

    /**
     * Add node(s) to the topbar tree.
     *
     * @param Horde_Tree_Renderer_Base $tree  Tree object.
     * @param string $parent                  The current parent element.
     * @param array $params                   Additional parameters.
     *
     * @throws Horde_Exception
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        switch ($params['id']) {
        case 'menu':
            $tree->addNode(array(
                'id' => $parent . '__sub',
                'parent' => $parent,
                'label' => _("Sub Item"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('add.png'),
                    'url' => Horde::url('item.php'),
                ),
            ));
            break;
        }
    }
}
