<?php
/**
 * Nag application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Nag
 */

/* Determine the base directories. */
if (!defined('NAG_BASE')) {
    define('NAG_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(NAG_BASE . '/config/horde.local.php')) {
        include NAG_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', NAG_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Nag_Application extends Horde_Registry_Application
{
    /**
     */
    public $features = array(
        'smartmobileView' => true
    );

    /**
     */
    public $version = 'H4 (4.0.0-git)';

    /**
     * Global variables defined:
     *   $nag_shares - TODO
     */
    protected function _init()
    {
        // Set the timezone variable.
        $GLOBALS['registry']->setTimeZone();

        /* For now, autoloading the Content_* classes depend on there being a
         * registry entry for the 'content' application that contains at least
         * the fileroot entry. */
        $GLOBALS['injector']->getInstance('Horde_Autoloader')
            ->addClassPathMapper(
                new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));

        // Create a share instance.
        $GLOBALS['nag_shares'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();

        Nag::initialize();
    }

    /**
     */
    public function perms()
    {
        return array(
            'max_tasks' => array(
                'title' => _("Maximum Number of Tasks"),
                'type' => 'int'
            )
        );
    }

    /**
     * Generate links in the sidebar.
     *
     * @param Horde_Menu  The menu object.
     */
    public function menu(Horde_Menu $menu)
    {
        global $conf;

        $menu->add(Horde::url('list.php'), _("_List Tasks"), 'nag-list', null, null, null, (basename($_SERVER['PHP_SELF']) == 'list.php' || strpos($_SERVER['PHP_SELF'], 'nag/index.php') !== false) ? 'current' : null);

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'nag-search');

        /* Import/Export. */
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
        // @TODO: Implement an injector factory for this.
        global $display_tasklists, $page_output, $prefs;

        $perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
        if (Nag::getDefaultTasklist(Horde_Perms::EDIT) &&
            ($perms->hasAppPermission('max_tasks') === true ||
             $perms->hasAppPermission('max_tasks') > Nag::countTasks())) {
            $sidebar->addNewButton(
                _("_New Task"),
                Horde::url('task.php')->add('actionID', 'add_task'));

            if ($GLOBALS['browser']->hasFeature('dom')) {
                $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
                $page_output->addScriptFile('redbox.js', 'horde');
                $blank = new Horde_Url();
                $sidebar->newExtra = $blank->link(
                    array_merge(
                        array('onclick' => 'RedBox.showInline(\'quickAddInfoPanel\'); $(\'quickText\').focus(); return false;'),
                        Horde::getAccessKeyAndTitle(_("_Quick Add"), false, true)
                    )
                );
                require_once NAG_TEMPLATES . '/quick.inc';
            }
        }

        $list = Horde::url('list.php');
        $edit = Horde::url('tasklists/edit.php');
        $user = $GLOBALS['registry']->getAuth();

        $sidebar->containers['my'] = array(
            'header' => array(
                'id' => 'nag-toggle-my',
                'label' => _("My Task Lists"),
                'collapsed' => false,
            ),
        );
        if (!$GLOBALS['prefs']->isLocked('default_tasklist')) {
            $sidebar->containers['my']['header']['add'] = array(
                'url' => Horde::url('tasklists/create.php'),
                'label' => _("Create a new Task List"),
            );
        }
        $sidebar->containers['shared'] = array(
            'header' => array(
                'id' => 'nag-toggle-shared',
                'label' => _("Shared Task Lists"),
                'collapsed' => true,
            ),
        );
        foreach (Nag::listTaskLists(false, Horde_Perms::SHOW, false) as $name => $tasklist) {
            $row = array(
                'selected' => in_array($name, $display_tasklists),
                'url' => $list->add('display_tasklist', $name),
                'label' => $tasklist->get('name'),
                'color' => $tasklist->get('color') ?: '#dddddd',
                'edit' => $edit->add('t', $tasklist->getName()),
                'type' => 'checkbox',
            );
            if ($tasklist->get('owner') == $user) {
                $sidebar->addRow($row, 'my');
            } else {
                if ($tasklist->get('owner')) {
                    $row['label'] .= ' [' . $GLOBALS['registry']->convertUsername($tasklist->get('owner'), false) . ']';
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
            case 'max_tasks':
                $allowed = max($allowed);
                break;
            }
        }
        return $allowed;
    }

    /**
     * Remove all data for the specified user.
     *
     * @param string $user  The user to remove.
     * @throws Nag_Exception
     */
    public function removeUserData($user)
    {
        try {
            $shares = $GLOBALS['nag_shares']
                ->listShares($user, array('attributes' => $user));
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Nag_Exception($e);
        }

        $error = false;
        foreach ($shares as $share) {
            $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($share->getName());
            $result = $storage->deleteAll();
            try {
                $GLOBALS['nag_shares']->removeShare($share);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'NOTICE');
                $error = true;
            }
        }

        /* Now remove perms for this user from all other shares */
        try {
            $shares = $GLOBALS['nag_shares']->listShares($user);
            foreach ($shares as $share) {
               $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'NOTICE');
            $error = true;
        }

        if ($error) {
            throw new Nag_Exception(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
        }
    }

    /* Alarm method. */

    /**
     */
    public function listAlarms($time, $user = null)
    {
        if ((empty($user) || $user != $GLOBALS['registry']->getAuth()) &&
            !$GLOBALS['registry']->isAdmin()) {

            throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
        }

        $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create();
        $group = $GLOBALS['injector']->getInstance('Horde_Group');
        $alarm_list = array();
        $tasklists = is_null($user) ?
            array_keys($GLOBALS['nag_shares']->listAllShares()) :
            $GLOBALS['display_tasklists'];

        $alarms = Nag::listAlarms($time, $tasklists);
        foreach ($alarms as $alarm) {
            try {
                $share = $GLOBALS['nag_shares']->getShare($alarm->tasklist);
            } catch (Horde_Share_Exception $e) {
                continue;
            }
            if (empty($user)) {
                $users = $share->listUsers(Horde_Perms::READ);
                $groups = $share->listGroups(Horde_Perms::READ);
                foreach ($groups as $gid) {
                    $users = array_merge($users, $group->listUsers($gid));
                }
                $users = array_unique($users);
            } else {
                $users = array($user);
            }
            foreach ($users as $alarm_user) {
                $prefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('nag', array(
                    'cache' => false,
                    'user' => $alarm_user
                ));
                $GLOBALS['registry']->setLanguageEnvironment($prefs->getValue('language'));
                $alarm_list[] = $alarm->toAlarm($alarm_user, $prefs);
            }
        }

        return $alarm_list;
    }


    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        global $registry;

        switch ($params['id']) {
        case 'menu':
            $add = Horde::url('task.php')->add('actionID', 'add_task');

            $tree->addNode(array(
                'id' => $parent . '__new',
                'parent' => $parent,
                'label' => _("New Task"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('add.png'),
                    'url' => $add
                )
            ));

            foreach (Nag::listTasklists() as $name => $tasklist) {
                $tree->addNode(array(
                    'id' => $parent . $name . '__new',
                    'parent' => $parent . '__new',
                    'label' => sprintf(_("in %s"), $tasklist->get('name')),
                    'expanded' => false,
                    'params' => array(
                        'icon' => Horde_Themes::img('add.png'),
                        'url' => $add->copy()->add('tasklist_id', $name)
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
            break;
        }
    }

    /* Download data. */

    /**
     * @throws Nag_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $display_tasklists, $injector, $registry;

        switch ($vars->actionID) {
        case 'export':
            $tasklists = $vars->get('exportList', $display_tasklists);
            if (!is_array($tasklists)) {
                $tasklists = array($tasklists);
            }

            /* Get the full, sorted task list. */
            $tasks = Nag::listTasks(array(
                'tasklists' => $tasklists,
                'completed' => $vars->exportTasks,
                'include_tags' => true)
            );

            if (!$tasks->hasTasks()) {
                throw new Nag_Exception(_("There were no tasks to export."));
            }

            $tasks->reset();
            switch ($vars->exportID) {
            case Horde_Data::EXPORT_CSV:
                $data = array();

                while ($task = $tasks->each()) {
                    $task = $task->toHash();
                    $task['desc'] = str_replace(',', '', $task['desc']);
                    unset(
                        $task['complete_link'],
                        $task['delete_link'],
                        $task['edit_link'],
                        $task['parent'],
                        $task['task_id'],
                        $task['tasklist_id'],
                        $task['view_link']
                    );
                    $data[] = $task;
                }

                $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => array($this, 'cleanupData')))->exportFile(_("tasks.csv"), $data, true);
                exit;

            case Horde_Data::EXPORT_ICALENDAR:
                $iCal = new Horde_Icalendar();
                $iCal->setAttribute(
                    'PRODID',
                    '-//The Horde Project//Nag ' . $registry->getVersion() . '//EN');
                while ($task = $tasks->each()) {
                    $iCal->addComponent($task->toiCalendar($iCal));
                }

                return array(
                    'data' => $iCal->exportvCalendar(),
                    'name' => _("tasks.ics"),
                    'type' => 'text/calendar'
                );
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
