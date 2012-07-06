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
    public $version = 'H5 (4.0-git)';

    /**
     * Global variables defined:
     *   $nag_shares - TODO
     */
    protected function _init()
    {
        // Set the timezone variable.
        $GLOBALS['registry']->setTimeZone();

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
     */
    public function menu($menu)
    {
        global $conf, $injector, $page_output;

        $menu->add(Horde::url('list.php'), _("_List Tasks"), 'nag-list', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        if (Nag::getDefaultTasklist(Horde_Perms::EDIT) &&
            ($injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_tasks') === true ||
             $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_tasks') > Nag::countTasks()) &&
            $GLOBALS['browser']->hasFeature('dom')) {
            $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
            $page_output->addScriptFile('redbox.js', 'horde');
            $menu->add(new Horde_Url(''), _("_Quick Add"), 'nag-add', null, null, 'RedBox.showInline(\'quickAddInfoPanel\'); $(\'quickText\').focus(); return false;', Horde_Util::getFormData('task') ? 'quickAdd __noselection' : 'quickAdd');
        }

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'nag-search');

        /* Import/Export. */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'horde-data');
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
            $storage = Nag_Driver::singleton($share->getName());
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

        $storage = Nag_Driver::singleton();
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
        case 'alarms':
            // Get any alarms in the next hour.
            $now = time();
            $alarms = Nag::listAlarms($now);
            $alarmCount = 0;
            $horde_alarm = $GLOBALS['injector']->getInstance('Horde_Alarm');
            foreach ($alarms as $taskId => $task) {
                if ($horde_alarm->isSnoozed($task->uid, $registry->getAuth())) {
                    continue;
                }
                ++$alarmCount;

                $differential = $task->due - $now;
                $title = ($differential >= 60)
                    ? sprintf(_("%s is due in %s"), $task->name, Nag::secondsToString($differential))
                    : sprintf(_("%s is due now."), $task->name);
                $url = Horde::url('view.php')->add(array(
                    'task' => $task->id,
                    'tasklist' => $task->tasklist
                ));

                $tree->addNode(array(
                    'id' => $parent . $taskId,
                    'parent' => $parent,
                    'label' => $task->name,
                    'expanded' => false,
                    'params' => array(
                        'icon' => Horde_Themes::img('alarm.png'),
                        'title' => $title,
                        'url' => $url
                    )
                ));
            }

            if ($registry->get('url', $parent)) {
                $purl = $registry->get('url', $parent);
            } elseif ($registry->get('status', $parent) == 'heading' ||
                      !$registry->get('webroot')) {
                $purl = null;
            } else {
                $purl = Horde::url($registry->getInitialPage($parent));
            }

            $pnode_name = $registry->get('name', $parent);
            if ($alarmCount) {
                $pnode_name = '<strong>' . $pnode_name . '</strong>';
            }

            $tree->addNode(array(
                'id' => $parent,
                'parent' => $registry->get('menu_parent', $parent),
                'label' => $pnode_name,
                'expanded' => false,
                'params' => array(
                    'icon' => strval($registry->get('icon', $parent)),
                    'url' => $purl
                )
            ));
            break;

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
            $tasks = Nag::listTasks(null, null, null, $tasklists, $vars->exportTasks);
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
