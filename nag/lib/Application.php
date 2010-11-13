<?php
/**
 * Nag application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Indicate this app has a mobile view available.
     *
     * @var boolean
     */
    public $mobileView = true;

    /**
     * Initialization function.
     *
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
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
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
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        global $conf, $injector;

        $menu->add(Horde::url('list.php'), _("_List Tasks"), 'nag.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        if (Nag::getDefaultTasklist(Horde_Perms::EDIT) &&
            ($injector->getInstance('Horde_Perms')->hasAppPermission('max_tasks') === true ||
             $injector->getInstance('Horde_Perms')->hasAppPermission('max_tasks') > Nag::countTasks())) {
            $menu->add(Horde::url('task.php')->add('actionID', 'add_task'), _("_New Task"), 'add.png', null, null, null, Horde_Util::getFormData('task') ? '__noselection' : null);
            if ($GLOBALS['browser']->hasFeature('dom')) {
                Horde::addScriptFile('redbox.js', 'horde', true);
                $menu->add(new Horde_Url(''), _("_Quick Add"), 'add.png', null, null, 'RedBox.showInline(\'quickAddInfoPanel\'); $(\'quickText\').focus(); return false;', Horde_Util::getFormData('task') ? 'quickAdd __noselection' : 'quickAdd');
            }
        }

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png', Horde_Themes::img(null, 'horde'));

        /* Import/Export. */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::url('data.php'), _("_Import/Export"), 'data.png', Horde_Themes::img(null, 'horde'));
        }
    }

    /**
     * Returns the specified permission for the given app permission.
     *
     * @param string $permission  The permission to check.
     * @param mixed $allowed      The allowed permissions.
     * @param array $opts         Additional options (NONE).
     *
     * @return mixed  The value of the specified permission.
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        switch ($permission) {
        case 'max_tasks':
            $allowed = max($allowed);
            break;
        }

        return $allowed;
    }

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        global $prefs, $registry;

        switch ($ui->group) {
        case 'share':
            if (!$prefs->isLocked('default_tasklist')) {
                $all_tasklists = Nag::listTasklists();
                $tasklists = array();

                foreach ($all_tasklists as $id => $tasklist) {
                    if (!empty($conf['share']['hidden']) &&
                        ($tasklist->get('owner') != $GLOBALS['registry']->getAuth()) &&
                        !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
                        continue;
                    }
                    $tasklists[$id] = $tasklist;
                }

                $vals = array();
                foreach ($tasklists as $id => $tasklist) {
                    $vals[htmlspecialchars($id)] = htmlspecialchars($tasklist->get('name'));
                }
                $ui->override['default_tasklist'] = $vals;
            }
            break;

        case 'tasks':
            if (!$prefs->isLocked('default_due_time')) {
                $twentyfour = $prefs->getValue('twentyFour');

                $vals = array('now' => _("The current hour"));
                for ($i = 0; $i < 24; ++$i) {
                    $value = sprintf('%02d:00', $i);
                    $vals[$value] = ($twentyfour)
                        ? $value
                        : sprintf('%02d:00 ' . ($i >= 12 ? _("pm") : _("am")), ($i % 12 ? $i % 12 : 12));
                }
                $ui->override['default_due_time'] = $vals;
            }
            break;
        }

        $show_external = array();
        if ($registry->hasMethod('getListTypes', 'whups')) {
            $show_external['whups'] = $registry->get('name', 'whups');
        }
        if (count($show_external)) {
            $ui->override['show_external'] = $show_external;
        } else {
            $ui->suppress[] = 'show_external';
            $ui->suppressGroups[] = 'external';
        }
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $conf, $prefs;

        switch ($ui->group) {
        case 'notification':
            if (empty($conf['alarms']['driver']) ||
                $prefs->isLocked('task_alarms') ||
                $prefs->isLocked('task_alarms_select')) {
                $ui->suppress[] = 'task_alarms';
            }
            break;
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the prefs page.
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
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
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
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @throws Nag_Exception
     */
    public function removeUserData($user)
    {
        /* Get the share for later deletion */
        try {
            $share = $GLOBALS['nag_shares']->getShare($user);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        /* Get the list of all tasks */
        $tasks = Nag::listTasks(null, null, null, $user, 1);
        if ($tasks instanceof PEAR_Error) {
            Horde::logMessage($tasks, 'ERR');
            throw new Nag_Exception(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
        } else {
            $uids = array();
            $tasks->reset();
            while ($task = $tasks->each()) {
                $uids[] = $task->uid;
            }

            /* ... and delete them. */
            foreach ($uids as $uid) {
                $this->delete($uid);
            }
        }

        /* ...and finally, delete the actual share */
        if (!empty($share)) {
            try {
                $GLOBALS['nag_shares']->removeShare($share);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Nag_Exception(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
            }
        }

        /* Now remove perms for this user from all other shares */
        try {
            $shares = $GLOBALS['nag_shares']->listShares($user);
            foreach ($shares as $share) {
               $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Nag_Exception(sprintf(_("There was an error removing tasks for %s. Details have been logged."), $user));
        }
    }

    /* Sidebar method. */

    /**
     * Add node(s) to the sidebar tree.
     *
     * @param Horde_Tree_Base $tree  Tree object.
     * @param string $parent         The current parent element.
     * @param array $params          Additional parameters.
     *
     * @throws Horde_Exception
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
            if ($alarms instanceof PEAR_Error) {
                return;
            }

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
                if ($tasklist->get('owner') != $registry->getAuth() &&
                    !empty($GLOBALS['conf']['share']['hidden']) &&
                    !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
                    continue;
                }

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
