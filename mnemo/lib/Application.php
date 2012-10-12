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
    define('MNEMO_BASE', __DIR__ . '/..');
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
    public $version = 'H5 (4.0-git)';

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

        $menu->add(Horde::url('list.php'), _("_List Notes"), 'mnemo-list', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'mnemo-search');

        /* Import/Export */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'horde-data');
        }
    }

    /**
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
        if (Mnemo::getDefaultNotepad(Horde_Perms::EDIT) &&
            ($perms->hasAppPermission('max_notes') === true ||
             $perms->hasAppPermission('max_notes') > Mnemo::countMemos())) {
            $sidebar->addNewButton(
                _("_New Note"),
                Horde::url('memo.php')->add('actionID', 'add_memo'));
        }

        $url = Horde::url('');
        $edit = Horde::url('notepads/edit.php');
        $user = $GLOBALS['registry']->getAuth();

        $sidebar->containers['my'] = array(
            'header' => array(
                'id' => 'mnemo-toggle-my',
                'label' => _("My Notepads"),
                'collapsed' => false,
            ),
        );
        if (!$GLOBALS['prefs']->isLocked('default_notepad')) {
            $sidebar->containers['my']['header']['add'] = array(
                'url' => Horde::url('notepads/create.php'),
                'label' => _("Create a new Notepad"),
            );
        }
        $sidebar->containers['shared'] = array(
            'header' => array(
                'id' => 'mnemo-toggle-shared',
                'label' => _("Shared Notepads"),
                'collapsed' => true,
            ),
        );
        foreach (Mnemo::listNotepads() as $name => $notepad) {
            $row = array(
                'selected' => in_array($name, $GLOBALS['display_notepads']),
                'url' => $url->add('display_notepad', $name),
                'label' => $notepad->get('name'),
                'color' => $notepad->get('color') ?: '#dddddd',
                'edit' => $edit->add('n', $notepad->getName()),
                'type' => 'checkbox',
            );
            if ($notepad->get('owner') == $user) {
                $sidebar->addRow($row, 'my');
            } else {
                if ($notepad->get('owner')) {
                    $row['label'] .= ' [' . $GLOBALS['registry']->convertUsername($notepad->get('owner'), false) . ']';
                }
                $sidebar->addRow($row, 'shared');
            }
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

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        $add = Horde::url('memo.php')->add('actionID', 'add_memo');

        $tree->addNode(array(
            'id' => $parent . '__new',
            'parent' => $parent,
            'label' => _("New Note"),
            'expanded' => false,
            'params' => array(
                'icon' => Horde_Themes::img('add.png'),
                'url' => $add
            )
        ));

        foreach (Mnemo::listNotepads() as $name => $notepad) {
            $tree->addNode(array(
                'id' => $parent . $name . '__new',
                'parent' => $parent . '__new',
                'label' => sprintf(_("in %s"), $notepad->get('name')),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('add.png'),
                    'url' => $add->copy()->add('memolist', $name)
                )
            ));
        }

        $tree->addNode(array(
            'id' => $parent . '__search',
            'parent' => $parent,
            'label' => _("Search"),
            'expanded' => false,
            'params' => array(
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::url('search.php')
            )
        ));
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

    /* Download data. */

    /**
     * @throws Mnemo_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $injector, $registry;

        switch ($vars->actionID) {
        case 'export':
            /* Create a Mnemo storage instance. */
            $storage = $injector->getInstance('Mnemo_Factory_Driver')->create($registry->getAuth());
            $storage->retrieve();

            /* Get the full, sorted memo list. */
            $notes = Mnemo::listMemos();

            switch ($vars->exportID) {
            case Horde_Data::EXPORT_CSV:
                if (count($notes) == 0) {
                    throw new Mnemo_Exception(_("There were no memos to export."));
                }
                                                                                                $data = array();
                foreach ($notes as $note) {
                    unset(
                        $note['desc'],
                        $note['memo_id'],
                        $note['memolist_id'],
                        $nore['uid']
                    );
                    $data[] = $note;
                }

                $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => array($this, 'cleanupData')))->exportFile(_("notes.csv"), $data, true);
                exit;
            }
        }
    }

    /**
     */
    public function cleanupData()
    {
        $GLOBALS['import_step'] = 1;
        return Horde_Data::IMPORT_FILE;
    }

}
