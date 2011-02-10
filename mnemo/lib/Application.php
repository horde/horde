<?php
/**
 * Mnemo application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Mnemo through this API.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Mnemo
 */

/* Determine the base directories. */
if (!defined('MNEMO_BASE')) {
    define('MNEMO_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(MNEMO_BASE . '/config/horde.local.php')) {
        include MNEMO_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', MNEMO_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Mnemo_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Global variables defined:
     *   $mnemo_shares - TODO
     */
    protected function _init()
    {
        Mnemo::initialize();
        $GLOBALS['injector']->getInstance('Horde_Themes_Css')->addThemeStylesheet('categoryCSS.php');
    }

    /**
     */
    public function perms()
    {
        return array(
            'max_notes' => array(
                'title' => _("Maximum Number of Notes"),
                'type' => 'int'
            )
        );
    }

    /**
     */
    public function menu($menu)
    {
        global $conf, $injector, $print_link;

        $menu->add(Horde::url('list.php'), _("_List Notes"), 'mnemo.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        if (Mnemo::getDefaultNotepad(Horde_Perms::EDIT) &&
            ($injector->getInstance('Horde_Perms')->hasAppPermission('max_notes') === true ||
             $injector->getInstance('Horde_Perms')->hasAppPermission('max_notes') > Mnemo::countMemos())) {
            $menu->add(Horde::url(Horde_Util::addParameter('memo.php', 'actionID', 'add_memo')), _("_New Note"), 'add.png', null, null, null, Horde_Util::getFormData('memo') ? '__noselection' : null);
        }

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png');

        /* Import/Export */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'data.png');
        }

        /* Print */
        if ($conf['menu']['print'] && isset($print_link)) {
            $menu->add(Horde::url($print_link), _("_Print"), 'print.png', null, '_blank', 'popup(this.href); return false;');
        }
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        switch ($permission) {
        case 'max_notes':
            $allowed = max($allowed);
            break;
        }

        return $allowed;
    }

    /**
     */
    public function prefsGroup($ui)
    {
        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'default_notepad':
                $notepads = array();
                foreach (Mnemo::listNotepads() as $key => $val) {
                    $notepads[htmlspecialchars($key)] = htmlspecialchars($val->get('name'));
                }
                $ui->override['default_notepad'] = $notepads;
                break;
            }
        }
    }

    /* Sidebar method. */

    /**
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        $add = Horde::url('memo.php')->add('actionID', 'add_memo');

        $tree->addNode(
            $parent . '__new',
            $parent,
            _("New Note"),
            1,
            false,
            array(
                'icon' => Horde_Themes::img('add.png'),
                'url' => $add
            )
        );

        foreach (Mnemo::listNotepads() as $name => $notepad) {
            if ($notepad->get('owner') != $GLOBALS['registry']->getAuth() &&
                !empty($GLOBALS['conf']['share']['hidden']) &&
                !in_array($notepad->getName(), $GLOBALS['display_notepads'])) {

                continue;
            }

            $tree->addNode(
                $parent . $name . '__new',
                $parent . '__new',
                sprintf(_("in %s"), $notepad->get('name')),
                2,
                false,
                array(
                    'icon' => Horde_Themes::img('add.png'),
                    'url' => $add->copy()->add('memolist', $name)
                )
            );
        }

        $tree->addNode(
            $parent . '__search',
            $parent,
            _("Search"),
            1,
            false,
            array(
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::url('search.php')
            )
        );
    }

}
