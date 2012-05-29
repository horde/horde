<?php
/**
 * Mnemo application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Mnemo through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
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
    public $version = 'H4 (3.0.7-git)';

    /**
     * Global variables defined:
     *   $mnemo_shares - TODO
     */
    protected function _init()
    {
        Mnemo::initialize();
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
        global $conf, $injector;

        $menu->add(Horde::url('list.php'), _("_List Notes"), 'mnemo.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        if (Mnemo::getDefaultNotepad(Horde_Perms::EDIT) &&
            ($injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_notes') === true ||
             $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_notes') > Mnemo::countMemos())) {
            $menu->add(Horde::url(Horde_Util::addParameter('memo.php', 'actionID', 'add_memo')), _("_New Note"), 'add.png', null, null, null, Horde_Util::getFormData('memo') ? '__noselection' : null);
        }

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png');

        /* Import/Export */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'data.png');
        }
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_notes':
                $allowed = max($allowed);
                break;
            }
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

    /**
     */
    public function removeUserData($user)
    {
        $error = false;
        $notepads = $GLOBALS['mnemo_shares']->listShares(
            $user, array('attribtues' => $user));
        foreach ($notepads as $notepad => $share) {
            $driver = $GLOBALS['injector']
                ->getInstance('Mnemo_Factory_Driver')
                ->create($notepad);
            try {
                $driver->deleteAll();
            } catch (Mnemo_Exception $e) {
                Horde::logMessage($e, 'NOTICE');
                $error = true;
            }

            try {
                $GLOBALS['mnemo_shares']->removeShare($share);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'NOTICE');
                $error = true;
            }
        }

        // Get a list of all shares this user has perms to and remove the perms.
        try {
            $shares = $GLOBALS['mnemo_shares']->listShares($user);
            foreach ($shares as $share) {
                $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'NOTICE');
            $error = true;
        }

        if ($error) {
            throw new Mnemo_Exception(sprintf(_("There was an error removing notes for %s. Details have been logged."), $user));
        }
    }

}
