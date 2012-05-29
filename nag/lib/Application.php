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
    define('NAG_BASE', dirname(__FILE__) . '/..');
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
    public $version = 'H4 (3.0.9-git)';

    /**
     */
    public $mobileView = true;

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
        global $conf, $injector;

        $menu->add(Horde::url('list.php'), _("_List Tasks"), 'nag.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        if (Nag::getDefaultTasklist(Horde_Perms::EDIT) &&
            ($injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_tasks') === true ||
             $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_tasks') > Nag::countTasks())) {
            $menu->add(Horde::url('task.php')->add('actionID', 'add_task'), _("_New Task"), 'add.png', null, null, null, Horde_Util::getFormData('task') ? '__noselection' : null);
            if ($GLOBALS['browser']->hasFeature('dom')) {
                Horde::addScriptFile('effects.js', 'horde');
                Horde::addScriptFile('redbox.js', 'horde');
                $menu->add(new Horde_Url(''), _("_Quick Add"), 'add.png', null, null, 'RedBox.showInline(\'quickAddInfoPanel\'); $(\'quickText\').focus(); return false;', Horde_Util::getFormData('task') ? 'quickAdd __noselection' : 'quickAdd');
            }
        }

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png');

        /* Import/Export. */
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
            case 'max_tasks':
                $allowed = max($allowed);
                break;
            }
        }
        return $allowed;
    }

    public function prefsInit($ui)
    {
        global $registry;
        if ($registry->hasMethod('getListTypes', 'whups')) {
            $ui->override['show_external'] = array(
                'whups' => $registry->get('name', 'whups')
            );
        } else {
            $ui->suppress[] = 'show_external';
        }
    }

    /**
     */
    public function prefsGroup($ui)
    {
        global $conf, $prefs, $registry;

        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'default_due_time':
                $twentyfour = $prefs->getValue('twentyFour');

                $vals = array('now' => _("The current hour"));
                for ($i = 0; $i < 24; ++$i) {
                    $value = sprintf('%02d:00', $i);
                    $vals[$value] = ($twentyfour)
                        ? $value
                        : sprintf('%02d:00 ' . ($i >= 12 ? _("pm") : _("am")), ($i % 12 ? $i % 12 : 12));
                }
                $ui->override['default_due_time'] = $vals;
                break;

            case 'default_tasklist':
                $vals = array();
                foreach (Nag::listTasklists() as $id => $tasklist) {
                    $vals[htmlspecialchars($id)] = htmlspecialchars($tasklist->get('name'));
                }
                $ui->override['default_tasklist'] = $vals;
                break;

            case 'sync_lists':
                $sync = @unserialize($prefs->getValue('sync_lists'));
                if (empty($sync)) {
                    $prefs->setValue('sync_lists', serialize(array(Nag::getDefaultTasklist())));
                }
                $out = array();
                foreach (Nag::listTasklists(false, Horde_Perms::EDIT) as $key => $list) {
                    if ($list->getName() != Nag::getDefaultTasklist(Horde_Perms::EDIT)) {
                        $out[$key] = $list->get('name');
                    }
                }
                $ui->override['sync_lists'] = $out;
                break;

            case 'show_external':
                if ($registry->hasMethod('getListTypes', 'whups')) {
                    $ui->override['show_external'] = array(
                        'whups' => $registry->get('name', 'whups')
                    );
                } else {
                    $ui->suppress[] = 'show_external';
                }
                break;

            case 'task_alarms_select':
                if (empty($conf['alarms']['driver']) ||
                    $prefs->isLocked('task_alarms_select')) {
                    $ui->suppress[] = 'task_alarms';
                } else {
                    Horde_Core_Prefs_Ui_Widgets::alarmInit();
                }
                break;
            }
        }
    }

    /**
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'task_alarms_select':
            return Horde_Core_Prefs_Ui_Widgets::alarm(array(
                'label' => _("Choose how you want to receive reminders for tasks with alarms:"),
                'pref' => 'task_alarms'
            ));
        }

        return '';
    }

    /**
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        switch ($item) {
        case 'task_alarms_select':
            $data = Horde_Core_Prefs_Ui_Widgets::alarmUpdate($ui, array('pref' => 'task_alarms'));
            if (!is_null($data)) {
                $GLOBALS['prefs']->setValue('task_alarms', serialize($data));
                return true;
            }
            break;
        }

        return false;
    }

    /**
     */
    public function prefsCallback($ui)
    {
        // Ensure that the current default_share is included in sync_calendars
        if ($GLOBALS['prefs']->isDirty('sync_lists') || $GLOBALS['prefs']->isDirty('default_tasklist')) {
            $sync = @unserialize($GLOBALS['prefs']->getValue('sync_lists'));
            $haveDefault = false;
            $default = Nag::getDefaultTasklist(Horde_Perms::EDIT);
            foreach ($sync as $cid) {
                if ($cid == $default) {
                    $haveDefault = true;
                    break;
                }
            }
            if (!$haveDefault) {
                $sync[] = $default;
                $GLOBALS['prefs']->setValue('sync_lists', serialize($sync));
            }
        }

        if ($GLOBALS['conf']['activesync']['enabled'] && $GLOBALS['prefs']->isDirty('sync_lists')) {
            try {
                $stateMachine = $GLOBALS['injector']->getInstance('Horde_ActiveSyncState');
                $stateMachine->setLogger($GLOBALS['injector']->getInstance('Horde_Log_Logger'));
                $devices = $stateMachine->listDevices($GLOBALS['registry']->getAuth());
                foreach ($devices as $device) {
                    $stateMachine->removeState(null, $device['device_id'], $GLOBALS['registry']->getAuth());
                }
                $GLOBALS['notification']->push(_("All state removed for your ActiveSync devices. They will resynchronize next time they connect to the server."));
            } catch (Horde_ActiveSync_Exception $e) {
                $GLOBALS['notification']->push(_("There was an error communicating with the ActiveSync server: %s"), $e->getMessage(), 'horde.err');
            }
        }
    }

    /**
     */
    public function removeUserData($user)
    {
        /* Get the shares for later deletion */
        try {
            $shares = $GLOBALS['nag_shares']->listShares($user, array('attributes' => $user));
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


    /* Sidebar method. */

    /**
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
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

                $tree->addNode(
                    $parent . $taskId,
                    $parent,
                    $task->name,
                    1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('alarm.png'),
                        'title' => $title,
                        'url' => $url
                    )
                );
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

            $tree->addNode(
                $parent,
                $registry->get('menu_parent', $parent),
                $pnode_name,
                0,
                false,
                array(
                    'icon' => strval($registry->get('icon', $parent)),
                    'url' => $purl
                )
            );
            break;

        case 'menu':
            $add = Horde::url('task.php')->add('actionID', 'add_task');

            $tree->addNode(
                $parent . '__new',
                $parent,
                _("New Task"),
                1,
                false,
                array(
                    'icon' => Horde_Themes::img('add.png'),
                    'url' => $add
                )
            );

            foreach (Nag::listTasklists() as $name => $tasklist) {
                $tree->addNode(
                    $parent . $name . '__new',
                    $parent . '__new',
                    sprintf(_("in %s"), $tasklist->get('name')),
                    2,
                    false,
                    array(
                        'icon' => Horde_Themes::img('add.png'),
                        'url' => $add->copy()->add('tasklist_id', $name)
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
            break;
        }
    }

}
