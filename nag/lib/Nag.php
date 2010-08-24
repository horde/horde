<?php
/**
 * Nag Base Class.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag
{
    /**
     * Sort by task name.
     */
    const SORT_NAME = 'name';

    /**
     * Sort by priority.
     */
    const SORT_PRIORITY = 'priority';

    /**
     * Sort by due date.
     */
    const SORT_DUE = 'due';

    /**
     * Sort by start date.
     */
    const SORT_START = 'start';

    /**
     * Sort by completion.
     */
    const SORT_COMPLETION = 'completed';

    /**
     * Sort by category.
     */
    const SORT_CATEGORY = 'category';

    /**
     * Sort by owner.
     */
    const SORT_OWNER = 'tasklist';

    /**
     * Sort by estimate.
     */
    const SORT_ESTIMATE = 'estimate';

    /**
     * Sort by assignee.
     */
    const SORT_ASSIGNEE = 'assignee';

    /**
     * Sort in ascending order.
     */
    const SORT_ASCEND = 0;

    /**
     * Sort in descending order.
     */
    const SORT_DESCEND = 1;

    /**
     * Incomplete tasks
     */
    const VIEW_INCOMPLETE = 0;

    /**
     * All tasks
     */
    const VIEW_ALL = 1;

    /**
     * Complete tasks
     */
    const VIEW_COMPLETE = 2;

    /**
     * Future tasks
     */
    const VIEW_FUTURE = 3;

    /**
     * Future and incompleted tasks
     */
    const VIEW_FUTURE_INCOMPLETE = 4;

    public static function secondsToString($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = ($seconds / 60) % 60;

        if ($hours > 1) {
            if ($minutes == 0) {
                return sprintf(_("%d hours"), $hours);
            } elseif ($minutes == 1) {
                return sprintf(_("%d hours, %d minute"), $hours, $minutes);
            } else {
                return sprintf(_("%d hours, %d minutes"), $hours, $minutes);
            }
        } elseif ($hours == 1) {
            if ($minutes == 0) {
                return sprintf(_("%d hour"), $hours);
            } elseif ($minutes == 1) {
                return sprintf(_("%d hour, %d minute"), $hours, $minutes);
            } else {
                return sprintf(_("%d hour, %d minutes"), $hours, $minutes);
            }
        } else {
            if ($minutes == 0) {
                return _("no time");
            } elseif ($minutes == 1) {
                return sprintf(_("%d minute"), $minutes);
            } else {
                return sprintf(_("%d minutes"), $minutes);
            }
        }
    }

    /**
     * Retrieves the current user's task list from storage.
     *
     * This function will also sort the resulting list, if requested.
     *
     * @param string $sortby      The field by which to sort (Nag::SORT_*).
     * @param integer $sortdir    The direction by which to sort
     *                            (Nag::SORT_ASCEND, Nag::SORT_DESCEND).
     * @param string $altsortby   The secondary sort field.
     * @param array $tasklists    An array of tasklist to display or
     *                            null/empty to display taskslists
     *                            $GLOBALS['display_tasklists'].
     * @param integer $completed  Which tasks to retrieve (1 = all tasks,
     *                            0 = incomplete tasks, 2 = complete tasks,
     *                            3 = future tasks, 4 = future and incomplete
     *                            tasks).
     *
     * @return Nag_Task  A list of the requested tasks.
     */
    public static function listTasks($sortby = null,
                                     $sortdir = null,
                                     $altsortby = null,
                                     $tasklists = null,
                                     $completed = null)
    {
        global $prefs, $registry;

        if (is_null($sortby)) {
            $sortby = $prefs->getValue('sortby');
        }
        if (is_null($sortdir)) {
            $sortdir = $prefs->getValue('sortdir');
        }
        if (is_null($altsortby)) {
            $altsortby = $prefs->getValue('altsortby');
        }

        if (is_null($tasklists)) {
            $tasklists = $GLOBALS['display_tasklists'];
        }
        if (!is_array($tasklists)) {
            $tasklists = array($tasklists);
        }
        if (is_null($completed)) {
            $completed = $prefs->getValue('show_completed');
        }

        $tasks = new Nag_Task();
        foreach ($tasklists as $tasklist) {
            /* Create a Nag storage instance. */
            $storage = Nag_Driver::singleton($tasklist);

            /* Retrieve the tasklist from storage. */
            $result = $storage->retrieve($completed);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            $tasks->mergeChildren($storage->tasks->children);
        }

        /* Process all tasks. */
        $tasks->process();

        /* We look for registered apis that support listAs(taskHash). */
        $apps = @unserialize($prefs->getValue('show_external'));
        if (is_array($apps)) {
            foreach ($apps as $app) {
                if ($app != 'nag' &&
                    $registry->hasMethod('getListTypes', $app)) {
                    $types = $registry->callByPackage($app, 'getListTypes');
                    if (is_a($types, 'PEAR_Error')) {
                        continue;
                    }
                    if (!empty($types['taskHash'])) {
                        $newtasks = $registry->callByPackage($app, 'listAs', array('taskHash'));
                        if (is_a($newtasks, 'PEAR_Error')) {
                            Horde::logMessage($newtasks, 'ERR');
                        } else {
                            foreach ($newtasks as $task) {
                                $task['tasklist_id'] = '**EXTERNAL**';
                                $task['tasklist_name'] = $registry->get('name', $app);
                                $tasks->add(new Nag_Task($task));
                            }
                        }
                    }
                }
            }
        }

        /* Sort the array. */
        $tasks->sort($sortby, $sortdir, $altsortby);

        return $tasks;
    }

    /**
     * Returns a single task.
     *
     * @param string $tasklist  A tasklist.
     * @param string $task      A task id.
     *
     * @return array  The task hash.
     */
    public static function getTask($tasklist, $task)
    {
        $storage = Nag_Driver::singleton($tasklist);
        $task = $storage->get($task);
        if (is_a($task, 'PEAR_Error')) {
            return $task;
        }
        $task->process();
        return $task;
    }

    /**
     * Returns the number of taks in task lists that the current user owns.
     *
     * @return integer  The number of tasks that the user owns.
     */
    public static function countTasks()
    {
        static $count;
        if (isset($count)) {
            return $count;
        }

        $tasklists = Nag::listTasklists(true, Horde_Perms::ALL);

        $count = 0;
        foreach (array_keys($tasklists) as $tasklist) {
            /* Create a Nag storage instance. */
            $storage = Nag_Driver::singleton($tasklist);
            $storage->retrieve();

            /* Retrieve the task list from storage. */
            $count += $storage->tasks->count();
        }

        return $count;
    }

    /**
     * Imports one or more tasks parsed from a string.
     *
     * @param string $text      The text to parse into
     * @param string $tasklist  The tasklist into which the task will be
     *                          imported.  If 'null', the user's default
     *                          tasklist will be used.
     *
     * @return array  The UIDs of all tasks that were added.
     */
    public static function createTasksFromText($text, $tasklist = null)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::EDIT);
        }
        if (!array_key_exists($tasklist, Nag::listTasklists(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $storage = Nag_Driver::singleton($tasklist);
        $dateParser = Horde_Date_Parser::factory();

        $quickParser = new Nag_QuickParser();
        $tasks = $quickParser->parse($text);

        $uids = array();
        foreach ($tasks as &$task) {
            if (!is_array($task)) {
                $name = $task;
                $task = array($name);
            }

            $r = $dateParser->parse($task[0], array('return' => 'result'));
            if ($d = $r->guess()) {
                $name = $r->untaggedText();
                $due = $d->timestamp();
            } else {
                $name = $task[0];
                $due = 0;
            }

            if (isset($task['parent'])) {
                $newTask = $storage->add($name, '', 0, $due, 3, 0.0, 0, '', 0, null, null, $tasks[$task['parent']]['id']);
            } else {
                $newTask = $storage->add($name, '', 0, $due, 3);
            }
            if (is_a($newTask, 'PEAR_Error')) {
                return $newTask;
            }
            $uids[] = $newTask[1];
            $task['id'] = $newTask[0];
        }

        return $uids;
    }

    /**
     * Returns all the alarms active right on $date.
     *
     * @param integer $date  The unix epoch time to check for alarms.
     *
     * @return array  The alarms (taskId) active on $date.
     */
    public static function listAlarms($date, $tasklists = null)
    {
        if (is_null($tasklists)) {
            $tasklists = $GLOBALS['display_tasklists'];
        }

        $tasks = array();
        foreach ($tasklists as $tasklist) {
            /* Create a Nag storage instance. */
            $storage = Nag_Driver::singleton($tasklist);

            /* Retrieve the alarms for the task list. */
            $newtasks = $storage->listAlarms($date);
            if (is_a($newtasks, 'PEAR_Error')) {
                return $newtasks;
            }

            /* Don't show an alarm for complete tasks. */
            foreach ($newtasks as $taskID => $task) {
                if (!empty($task->completed)) {
                    unset($newtasks[$taskID]);
                }
            }

            $tasks = array_merge($tasks, $newtasks);
        }

        return $tasks;
    }

    /**
     * Lists all task lists a user has access to.
     *
     * @param boolean $owneronly  Only return tasklists that this user owns?
     *                            Defaults to false.
     * @param integer $permission The permission to filter tasklists by.
     *
     * @return array  The task lists.
     */
    public static function listTasklists($owneronly = false,
                                         $permission = Horde_Perms::SHOW)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }
        try {
            $tasklists = $GLOBALS['nag_shares']->listShares($GLOBALS['registry']->getAuth(), $permission, $owneronly ? $GLOBALS['registry']->getAuth() : null, 0, 0, 'name');
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            return array();
        }

        return $tasklists;
    }

    /**
     * Filters data based on permissions.
     *
     * @param array $in            The data we want filtered.
     * @param string $filter       What type of data we are filtering.
     * @param integer $permission  The Horde_Perms::* constant we will filter
     *                             on.
     *
     * @return array  The filtered data.
     */
    public static function permissionsFilter($in, $permission = Horde_Perms::READ)
    {
        // FIXME: Must find a way to check individual tasklists for
        // permission.  Can't specify attributes as it does not check for the
        // 'key' attribute, only 'name' and 'value'.
        return $in;

        // Broken code:
        $out = array();

        foreach ($in as $sourceId => $source) {
            if ($in->hasPermission($permission)) {
                $out[$sourceId] = $source;
            }
        }

        return $out;
    }

    /**
     * Returns the default tasklist for the current user at the specified
     * permissions level.
     */
    public static function getDefaultTasklist($permission = Horde_Perms::SHOW)
    {
        global $prefs;

        $default_tasklist = $prefs->getValue('default_tasklist');
        $tasklists = Nag::listTasklists(false, $permission);

        if (isset($tasklists[$default_tasklist])) {
            return $default_tasklist;
        } elseif ($prefs->isLocked('default_tasklist')) {
            return $GLOBALS['registry']->getAuth();
        } elseif (count($tasklists)) {
            reset($tasklists);
            return key($tasklists);
        }

        return false;
    }

    /**
     * Creates a new share.
     *
     * @param array $info  Hash with calendar information.
     *
     * @return Horde_Share  The new share.
     */
    public static function addTasklist($info)
    {
        try {
            $tasklist = $GLOBALS['nag_shares']->newShare(strval(new Horde_Support_Uuid()));
            $tasklist->set('name', $info['name']);
            $tasklist->set('color', $info['color']);
            $tasklist->set('desc', $info['description']);
            if (!empty($info['system'])) {
                $tasklist->set('owner', null);
            }

            $GLOBALS['nag_shares']->addShare($tasklist);
        } catch (Horde_Share_Exception $e) {
            throw new Nag_Exception($e);
        }

        $GLOBALS['display_tasklists'][] = $tasklist->getName();
        $GLOBALS['prefs']->setValue('display_tasklists', serialize($GLOBALS['display_tasklists']));

        return $tasklist;
    }

    /**
     * Updates an existing share.
     *
     * @param Horde_Share $share  The share to update.
     * @param array $info         Hash with task list information.
     *
     * @throws Horde_Exception_PermissionDenied
     * @throws Nag_Exception
     */
    public static function updateTasklist($tasklist, $info)
    {
        if (!$GLOBALS['registry']->getAuth() ||
            ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
             (!is_null($tasklist->get('owner')) || !$GLOBALS['registry']->isAdmin()))) {

            throw new Horde_Exception_PermissionDenied(_("You are not allowed to change this task list."));
        }

        $tasklist->set('name', $info['name']);
        $tasklist->set('color', $info['color']);
        $tasklist->set('desc', $info['description']);
        $tasklist->set('owner', empty($info['system']) ? $GLOBALS['registry']->getAuth() : null);

        try {
            $tasklist->save();
        } catch (Horde_Share_Exception $e) {
            throw new Nag_Exception(sprintf(_("Unable to save task list \"%s\": %s"), $info['name'], $e->getMessage()));
        }
    }

    /**
     * Deletes a task list.
     *
     * @param Horde_Share $tasklist  The task list to delete.
     */
    public static function deleteTasklist($tasklist)
    {
        if ($tasklist->getName() == $GLOBALS['registry']->getAuth()) {
            throw new Horde_Exception_PermissionDenied(_("This task list cannot be deleted."));
        }

        if (!$GLOBALS['registry']->getAuth() ||
            ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
             (!is_null($tasklist->get('owner')) || !$GLOBALS['registry']->isAdmin()))) {
            throw new Horde_Exception_PermissionDenied(_("You are not allowed to delete this task list."));
        }

        // Delete the task list.
        $storage = &Nag_Driver::singleton($tasklist->getName());
        $result = $storage->deleteAll();
        if ($result instanceof PEAR_Error) {
            throw new Nag_Exception(sprintf(_("Unable to delete \"%s\": %s"), $tasklist->get('name'), $result->getMessage()));
        }

        // Remove share and all groups/permissions.
        try {
            return $GLOBALS['nag_shares']->removeShare($tasklist);
        } catch (Horde_Share_Exception $e) {
            throw new Nag_Exception($e);
        }
    }

    /**
     * Builds the HTML for a priority selection widget.
     *
     * @param string $name       The name of the widget.
     * @param integer $selected  The default selected priority.
     *
     * @return string  The HTML <select> widget.
     */
    public static function buildPriorityWidget($name, $selected = -1)
    {
        $descs = array(1 => _("(highest)"), 5 => _("(lowest)"));

        $html = "<select id=\"$name\" name=\"$name\">";
        for ($priority = 1; $priority <= 5; $priority++) {
            $html .= "<option value=\"$priority\"";
            $html .= ($priority == $selected) ? ' selected="selected">' : '>';
            $html .= $priority . ' ' . @$descs[$priority] . '</option>';
        }
        $html .= "</select>\n";

        return $html;
    }

    /**
     * Builds the HTML for a checkbox widget.
     *
     * @param string $name      The name of the widget.
     * @param integer $checked  The default checkbox state.
     *
     * @return string  HTML for a checkbox representing the completion state.
     */
    public static function buildCheckboxWidget($name, $checked = 0)
    {
        $name = htmlspecialchars($name);
        return "<input type=\"checkbox\" id=\"$name\" name=\"$name\"" .
            ($checked ? ' checked="checked"' : '') . ' />';
    }

    /**
     * Formats the given Unix-style date string.
     *
     * @param string $unixdate  The Unix-style date value to format.
     * @param boolean $hours    Whether to add hours.
     *
     * @return string  The formatted due date string.
     */
    public static function formatDate($unixdate = '', $hours = true)
    {
        global $prefs;

        if (empty($unixdate)) {
            return '';
        }

        $date = strftime($prefs->getValue('date_format'), $unixdate);
        if (!$hours) {
            return $date;
        }

        return sprintf(_("%s at %s"),
                       $date,
                       strftime($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M %p', $unixdate));
    }

    /**
     * Returns the string representation of the given completion status.
     *
     * @param int $completed  The completion value.
     *
     * @return string  The HTML representation of $completed.
     */
    public static function formatCompletion($completed)
    {
        return $completed ?
            Horde::img('checked.png', _("Completed")) :
            Horde::img('unchecked.png', _("Not Completed"));
    }

    /**
     * Returns a colored representation of a priority.
     *
     * @param int $priority  The priority level.
     *
     * @return string  The HTML representation of $priority.
     */
    public static function formatPriority($priority)
    {
        return '<span class="pri-' . (int)$priority . '">' . (int)$priority .
            '</span>';
    }

    /**
     * Returns the string matching the given alarm value.
     *
     * @param int $value  The alarm value in minutes.
     *
     * @return string  The formatted alarm string.
     */
    public static function formatAlarm($value)
    {
        if ($value) {
            if ($value % 10080 == 0) {
                $alarm_value = $value / 10080;
                $alarm_unit = _("Week(s)");
            } elseif ($value % 1440 == 0) {
                $alarm_value = $value / 1440;
                $alarm_unit = _("Day(s)");
            } elseif ($value % 60 == 0) {
                $alarm_value = $value / 60;
                $alarm_unit = _("Hour(s)");
            } else {
                $alarm_value = $value;
                $alarm_unit = _("Minute(s)");
            }
            $alarm_text = "$alarm_value $alarm_unit";
        } else {
            $alarm_text = _("None");
        }
        return $alarm_text;
    }

    /**
     * Returns the full name and a compose to message an assignee.
     *
     * @param string $assignee  The assignee's user name.
     * @param boolean $link     Whether to link to an email compose screen.
     *
     * @return string  The formatted assignee name.
     */
    public static function formatAssignee($assignee, $link = false)
    {
        if (!strlen($assignee)) {
            return '';
        }

        $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($assignee);
        $fullname = $identity->getValue('fullname');
        if (!strlen($fullname)) {
            $fullname = $assignee;
        }
        $email = $identity->getValue('from_addr');
        if ($link && !empty($email) &&
            $GLOBALS['registry']->hasMethod('mail/compose')) {
            return Horde::link($GLOBALS['registry']->call(
                                   'mail/compose',
                                   array(array('to' => $email))))
                . @htmlspecialchars($fullname . ' <' . $email . '>',
                                    ENT_COMPAT, $GLOBALS['registry']->getCharset())
                . '</a>';
        }

        return @htmlspecialchars($fullname, ENT_COMPAT, $GLOBALS['registry']->getCharset());
    }

    /**
     * Initial app setup code.
     */
    public static function initialize()
    {
        /* Store the request timestamp if it's not already present. */
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        // Update the preference for what task lists to display. If the user
        // doesn't have any selected task lists for view then fall back to
        // some available list.
        $GLOBALS['display_tasklists'] = @unserialize($GLOBALS['prefs']->getValue('display_tasklists'));
        if (!$GLOBALS['display_tasklists']) {
            $GLOBALS['display_tasklists'] = array();
        }
        if (($tasklistId = Horde_Util::getFormData('display_tasklist')) !== null) {
            if (is_array($tasklistId)) {
                $GLOBALS['display_tasklists'] = $tasklistId;
            } else {
                if (in_array($tasklistId, $GLOBALS['display_tasklists'])) {
                    $key = array_search($tasklistId, $GLOBALS['display_tasklists']);
                    unset($GLOBALS['display_tasklists'][$key]);
                } else {
                    $GLOBALS['display_tasklists'][] = $tasklistId;
                }
            }
        }

        // Make sure all task lists exist now, to save on checking later.
        $_temp = $GLOBALS['display_tasklists'];
        $GLOBALS['all_tasklists'] = Nag::listTasklists();
        $GLOBALS['display_tasklists'] = array();
        foreach ($_temp as $id) {
            if (isset($GLOBALS['all_tasklists'][$id])) {
                $GLOBALS['display_tasklists'][] = $id;
            }
        }

        if (count($GLOBALS['display_tasklists']) == 0) {
            $lists = Nag::listTasklists(true);
            if (!$GLOBALS['registry']->getAuth()) {
                /* All tasklists for guests. */
                $GLOBALS['display_tasklists'] = array_keys($lists);
            } else {
                /* Make sure at least the default tasklist is visible. */
                $default_tasklist = Nag::getDefaultTasklist(Horde_Perms::READ);
                if ($default_tasklist) {
                    $GLOBALS['display_tasklists'] = array($default_tasklist);
                }

                /* If the user's personal tasklist doesn't exist, then create it. */
                if (!$GLOBALS['nag_shares']->exists($GLOBALS['registry']->getAuth())) {
                    $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity();
                    $name = $identity->getValue('fullname');
                    if (trim($name) == '') {
                        $name = $GLOBALS['registry']->getAuth('original');
                    }
                    $share = $GLOBALS['nag_shares']->newShare($GLOBALS['registry']->getAuth());
                    $share->set('name', sprintf(_("%s's Task List"), $name));
                    $GLOBALS['nag_shares']->addShare($share);

                    /* Make sure the personal tasklist is displayed by default. */
                    if (!in_array($GLOBALS['registry']->getAuth(), $GLOBALS['display_tasklists'])) {
                        $GLOBALS['display_tasklists'][] = $GLOBALS['registry']->getAuth();
                    }
                }
            }
        }

        $GLOBALS['prefs']->setValue('display_tasklists', serialize($GLOBALS['display_tasklists']));
    }

    /**
     * Build Nag's list of menu items.
     */
    public static function getMenu()
    {
        global $conf, $registry;

        $menu = new Horde_Menu();
        $menu->add(Horde::applicationUrl('list.php'), _("_List Tasks"), 'nag.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        if (Nag::getDefaultTasklist(Horde_Perms::EDIT) &&
            (!empty($conf['hooks']['permsdenied']) ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_tasks') === true ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_tasks') > Nag::countTasks())) {
            $menu->add(Horde::applicationUrl('task.php')->add('actionID', 'add_task'), _("_New Task"), 'add.png', null, null, null, Horde_Util::getFormData('task') ? '__noselection' : null);
            if ($GLOBALS['browser']->hasFeature('dom')) {
                $menu->add(new Horde_Url(''), _("_Quick Add"), 'add.png', null, null, 'Nag.quickAddPanel.show(); $(\'quickText\').focus(); return false;', Horde_Util::getFormData('task') ? 'quickAdd __noselection' : 'quickAdd');
            }
        }

        /* Search. */
        $menu->add(Horde::applicationUrl('search.php'), _("_Search"), 'search.png', Horde_Themes::img(null, 'horde'));

        /* Import/Export. */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::applicationUrl('data.php'), _("_Import/Export"), 'data.png', Horde_Themes::img(null, 'horde'));
        }

        return $menu;
    }

    public static function status()
    {
        global $notification;

        if (empty($GLOBALS['conf']['alarms']['driver'])) {
            // Get any alarms in the next hour.
            $now = time();
            $alarmList = Nag::listAlarms($now);
            if (is_a($alarmList, 'PEAR_Error')) {
                Horde::logMessage($alarmList, 'ERR');
                $notification->push($alarmList, 'horde.error');
            } else {
                $messages = array();
                foreach ($alarmList as $task) {
                    $differential = $task->due - $now;
                    $key = $differential;
                    while (isset($messages[$key])) {
                        $key++;
                    }
                    if ($differential >= -60 && $differential < 60) {
                        $messages[$key] = array(sprintf(_("%s is due now."), $task->name), 'horde.alarm');
                    } elseif ($differential >= 60) {
                        $messages[$key] = array(sprintf(_("%s is due in %s"), $task->name,
                                                        Nag::secondsToString($differential)), 'horde.alarm');
                    }
                }

                ksort($messages);
                foreach ($messages as $message) {
                    $notification->push($message[0], $message[1]);
                }
            }
        }

        // Check here for guest task lists so that we don't get multiple
        // messages after redirects, etc.
        if (!$GLOBALS['registry']->getAuth() && !count(Nag::listTasklists())) {
            $notification->push(_("No task lists are available to guests."));
        }

        // Display all notifications.
        $notification->notify(array('listeners' => 'status'));
    }

    /**
     * Sends email notifications that a task has been added, edited, or
     * deleted to users that want such notifications.
     *
     * @param string $action      The event action. One of "add", "edit", or
     *                            "delete".
     * @param Nag_Task $task      The changed task.
     * @param Nag_Task $old_task  The original task if $action is "edit".
     */
    public static function sendNotification($action, $task, $old_task = null)
    {
        if (!in_array($action, array('add', 'edit', 'delete'))) {
            return PEAR::raiseError('Unknown event action: ' . $action);
        }

        try {
            $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Nag_Exception($e);
        }

        $groups = Horde_Group::singleton();
        $recipients = array();
        $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity();
        $from = $identity->getDefaultFromAddress(true);

        $owner = $share->get('owner');
        if (strlen($owner)) {
            $recipients[$owner] = Nag::_notificationPref($owner, 'owner');
        }

        foreach ($share->listUsers(Horde_Perms::READ) as $user) {
            if (empty($recipients[$user])) {
                $recipients[$user] = Nag::_notificationPref($user, 'read', $task->tasklist);
            }
        }
        foreach ($share->listGroups(Horde_Perms::READ) as $group) {
            try {
                $group = $groups->getGroupById($group);
                $group_users = $group->listAllUsers();
            } catch (Horde_Group_Exception $e) {
                Horde::logMessage($e, 'ERR');
                continue;
            }

            foreach ($group_users as $user) {
                if (empty($recipients[$user])) {
                    $recipients[$user] = Nag::_notificationPref($user, 'read', $task->tasklist);
                }
            }
        }

        $addresses = array();
        foreach ($recipients as $user => $vals) {
            if (!$vals) {
                continue;
            }
            $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($user);
            $email = $identity->getValue('from_addr');
            if (strpos($email, '@') === false) {
                continue;
            }
            list($mailbox, $host) = explode('@', $email);
            if (!isset($addresses[$vals['lang']][$vals['tf']][$vals['df']])) {
                $addresses[$vals['lang']][$vals['tf']][$vals['df']] = array();
            }
            $addresses[$vals['lang']][$vals['tf']][$vals['df']][] = Horde_Mime_Address::writeAddress($mailbox, $host, $identity->getValue('fullname'));
        }

        if (!$addresses) {
            return;
        }

        $mail = new Horde_Mime_Mail();
        $mail->addHeader('User-Agent', 'Nag ' . $GLOBALS['registry']->getVersion());
        $mail->addHeader('Precedence', 'bulk');
        $mail->addHeader('Auto-Submitted', 'auto-generated');
        $mail->addHeader('From', $from);

        foreach ($addresses as $lang => $twentyFour) {
            $GLOBALS['registry']->setLanguageEnvironment($lang);

            $view_link = Horde_Util::addParameter(Horde::applicationUrl('view.php', true),
                                            array('tasklist' => $task->tasklist,
                                                  'task' => $task->id),
                                            null, false);

            switch ($action) {
            case 'add':
                $subject = _("Task added:");
                $notification_message = _("You requested to be notified when tasks are added to your task lists.")
                    . "\n\n"
                    . ($task->due
                       ? _("The task \"%s\" has been added to task list \"%s\", with a due date of: %s.")
                       : _("The task \"%s\" has been added to task list \"%s\"."))
                    . "\n"
                    . str_replace('%', '%%', $view_link);
                break;

            case 'edit':
                $subject = _("Task modified:");
                $notification_message = _("You requested to be notified when tasks are edited on your task lists.")
                    . "\n\n"
                    . _("The task \"%s\" has been edited on task list \"%s\".")
                    . "\n"
                    . str_replace('%', '%%', $view_link)
                    . "\n\n"
                    . _("Changes made for this task:");
                if ($old_task->name != $task->name) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed name from \"%s\" to \"%s\""),
                                  $old_task->name, $task->name);
                }
                if ($old_task->tasklist != $task->tasklist) {
                    $old_share = $GLOBALS['nag_shares']->getShare($old_task->tasklist);
                    $notification_message .= "\n - "
                        . sprintf(_("Changed task list from \"%s\" to \"%s\""),
                                  $old_share->get('name'), $share->get('name'));
                }
                if ($old_task->parent_id != $task->parent_id) {
                    $old_parent = $old_task->getParent();
                    $parent = $task->getParent();
                    if (!is_a($parent, 'PEAR_Error')) {
                        $notification_message .= "\n - "
                            . sprintf(_("Changed parent task from \"%s\" to \"%s\""),
                                      $old_parent ? $old_parent->name : _("no parent"),
                                      $parent ? $parent->name : _("no parent"));
                    }

                }
                if ($old_task->category != $task->category) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed category from \"%s\" to \"%s\""),
                                  $old_task->category, $task->category);
                }
                if ($old_task->assignee != $task->assignee) {
                    $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($old_task->assignee);
                    $old_name = $identity->getValue('fullname');
                    if (!strlen($old_name)) {
                        $old_name = $old_task->assignee;
                    }
                    $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($task->assignee);
                    $new_name = $identity->getValue('fullname');
                    if (!strlen($new_name)) {
                        $new_name = $new_task->assignee;
                    }
                    $notification_message .= "\n - "
                        . sprintf(_("Changed assignee from \"%s\" to \"%s\""),
                                  $old_name, $new_name);
                }
                if ($old_task->private != $task->private) {
                    $notification_message .= "\n - "
                        . ($task->private ? _("Turned privacy on") : _("Turned privacy off"));
                }
                if ($old_task->due != $task->due) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed due date from %s to %s"),
                                  $old_task->due ? Nag::formatDate($old_task->due) : _("no due date"),
                                  $task->due ? Nag::formatDate($task->due) : _("no due date"));
                }
                if ($old_task->start != $task->start) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed start date from %s to %s"),
                                  $old_task->start ? Nag::formatDate($old_task->start) : _("no start date"),
                                  $task->start ? Nag::formatDate($task->start) : _("no start date"));
                }
                if ($old_task->alarm != $task->alarm) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed alarm from %s to %s"),
                                  Nag::formatAlarm($old_task->alarm), Nag::formatAlarm($task->alarm));
                }
                if ($old_task->priority != $task->priority) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed priority from %s to %s"),
                                  $old_task->priority, $task->priority);
                }
                if ($old_task->estimate != $task->estimate) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed estimate from %s to %s"),
                                  $old_task->estimate, $task->estimate);
                }
                if ($old_task->completed != $task->completed) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed completion from %s to %s"),
                                  $old_task->completed ? _("completed") : _("not completed"),
                                  $task->completed ? _("completed") : _("not completed"));
                }
                if ($old_task->desc != $task->desc) {
                    $notification_message .= "\n - " . _("Changed description");
                }
                break;

            case 'delete':
                $subject = _("Task deleted:");
                $notification_message =
                    _("You requested to be notified when tasks are deleted from your task lists.")
                    . "\n\n"
                    . _("The task \"%s\" has been deleted from task list \"%s\".");
                break;
            }

            $mail->addHeader('Subject', $subject . ' ' . $task->name, $GLOBALS['registry']->getCharset());

            foreach ($twentyFour as $tf => $dateFormat) {
                foreach ($dateFormat as $df => $df_recipients) {
                    $message = sprintf($notification_message,
                                       $task->name,
                                       $share->get('name'),
                                       $task->due ? strftime($df, $task->due) . ' ' . date($tf ? 'H:i' : 'h:ia', $task->due) : '');
                    if (strlen(trim($task->desc))) {
                        $message .= "\n\n" . _("Task description:") . "\n\n" . $task->desc;
                    }

                    $mail->setBody($message, $GLOBALS['registry']->getCharset());
                    $mail->clearRecipients();
                    $mail->addRecipients($df_recipients);

                    Horde::logMessage(sprintf('Sending event notifications for %s to %s',
                                              $task->name, implode(', ', $df_recipients)), 'INFO');
                    $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
                }
            }
        }
    }

    /**
     * Returns the real name, if available, of a user.
     */
    public static function getUserName($uid)
    {
        static $names = array();

        if (!isset($names[$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($uid);
            $ident->setDefault($ident->getDefault());
            $names[$uid] = $ident->getValue('fullname');
            if (empty($names[$uid])) {
                $names[$uid] = $uid;
            }
        }

        return $names[$uid];
    }

    /**
     * Returns whether a user wants email notifications for a tasklist.
     *
     * @access private
     *
     * @todo This method is causing a memory leak somewhere, noticeable if
     *       importing a large amount of events.
     *
     * @param string $user      A user name.
     * @param string $mode      The check "mode". If "owner", the method checks
     *                          if the user wants notifications only for
     *                          tasklists he owns. If "read", the method checks
     *                          if the user wants notifications for all
     *                          tasklists he has read access to, or only for
     *                          shown tasklists and the specified tasklist is
     *                          currently shown.
     * @param string $tasklist  The name of the tasklist if mode is "read".
     *
     * @return boolean  True if the user wants notifications for the tasklist.
     */
    public static function _notificationPref($user, $mode, $tasklist = null)
    {
        $prefs = $GLOBALS['injector']->getInstance('Horde_Prefs')->getPrefs('nag', array(
            'cache' => false,
            'user' => $user
        ));
        $vals = array('lang' => $prefs->getValue('language'),
                      'tf' => $prefs->getValue('twentyFour'),
                      'df' => $prefs->getValue('date_format'));

        if ($prefs->getValue('task_notification_exclude_self') &&
            $user == $GLOBALS['registry']->getAuth()) {
            return false;
        }

        $notification = $prefs->getValue('task_notification');
        switch ($notification) {
        case 'owner':
            return $mode == 'owner' ? $vals : false;
        case 'read':
            return $mode == 'read' ? $vals : false;
        case 'show':
            if ($mode == 'read') {
                $display_tasklists = unserialize($prefs->getValue('display_tasklists'));
                return in_array($tasklist, $display_tasklists) ? $vals : false;;
            }
        }

        return false;
    }

    /**
     * Comparison function for sorting tasks by create date (not currently used
     * as it would require accessing Horde_History for each task) and id.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal (though no tasks should ever be
     *                  equal in this comparison).
     */
    public static function _sortByIdentity($a, $b)
    {
        return strcmp($a->id, $b->id);
    }

    /**
     * Comparison function for sorting tasks by priority.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByPriority($a, $b)
    {
        if ($a->priority == $b->priority) {
            return self::_sortByIdentity($a, $b);
        }
        return ($a->priority > $b->priority) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting tasks by priority.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _rsortByPriority($a, $b)
    {
        if ($a->priority == $b->priority) {
            return self::_sortByIdentity($b, $a);
        }
        return ($a->priority > $b->priority) ? -1 : 1;
    }

    /**
     * Comparison function for sorting tasks by name.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByName($a, $b)
    {
        return strcasecmp($a->name, $b->name);
    }

    /**
     * Comparison function for reverse sorting tasks by name.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _rsortByName($a, $b)
    {
        return strcasecmp($b->name, $a->name);
    }

    /**
     * Comparison function for sorting tasks by assignee.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByAssignee($a, $b)
    {
        return strcasecmp($a->assignee, $b->assignee);
    }

    /**
     * Comparison function for reverse sorting tasks by assignee.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _rsortByAssignee($a, $b)
    {
        return strcasecmp($b->assignee, $a->assignee);
    }

    /**
     * Comparison function for sorting tasks by assignee.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByEstimate($a, $b)
    {
        $a_est = $a->estimation();
        $b_est = $b->estimation();
        if ($a_est == $b_est) {
            return self::_sortByIdentity($a, $b);
        }
        return ($a_est > $b_est) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting tasks by name.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _rsortByEstimate($a, $b)
    {
        $a_est = $a->estimation();
        $b_est = $b->estimation();
        if ($a_est == $b_est) {
            return self::_sortByIdentity($b, $a);
        }
        return ($a_est > $b_est) ? -1 : 1;
    }

    /**
     * Comparison function for sorting tasks by category.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByCategory($a, $b)
    {
        return strcasecmp($a->category ? $a->category : _("Unfiled"),
                          $b->category ? $b->category : _("Unfiled"));
    }

    /**
     * Comparison function for reverse sorting tasks by category.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _rsortByCategory($a, $b)
    {
        return strcasecmp($b->category ? $b->category : _("Unfiled"),
                          $a->category ? $a->category : _("Unfiled"));
    }

    /**
     * Comparison function for sorting tasks by due date.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByDue($a, $b)
    {
        if ($a->due == $b->due) {
            return self::_sortByIdentity($a, $b);
        }

        // Treat empty due dates as farthest into the future.
        if ($a->due == 0) {
            return 1;
        }
        if ($b->due == 0) {
            return -1;
        }

        return ($a->due > $b->due) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting tasks by due date.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater,
     *                  0 if they are equal.
     */
    public static function _rsortByDue($a, $b)
    {
        if ($a->due == $b->due) {
            return self::_sortByIdentity($b, $a);
        }

        // Treat empty due dates as farthest into the future.
        if ($a->due == 0) {
            return -1;
        }
        if ($b->due == 0) {
            return 1;
        }

        return ($a->due < $b->due) ? 1 : -1;
    }

    /**
     * Comparison function for sorting tasks by start date.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByStart($a, $b)
    {
        if ($a->start == $b->start) {
            return self::_sortByIdentity($a, $b);
        }

        // Treat empty start dates as farthest into the future.
        if ($a->start == 0) {
            return 1;
        }
        if ($b->start == 0) {
            return -1;
        }

        return ($a->start > $b->start) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting tasks by start date.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater,
     *                  0 if they are equal.
     */
    public static function _rsortByStart($a, $b)
    {
        if ($a->start == $b->start) {
            return self::_sortByIdentity($b, $a);
        }

        // Treat empty start dates as farthest into the future.
        if ($a->start == 0) {
            return -1;
        }
        if ($b->start == 0) {
            return 1;
        }

        return ($a->start < $b->start) ? 1 : -1;
    }

    /**
     * Comparison function for sorting tasks by completion status.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByCompletion($a, $b)
    {
        if ($a->completed == $b->completed) {
            return self::_sortByIdentity($a, $b);
        }
        return ($a->completed > $b->completed) ? -1 : 1;
    }

    /**
     * Comparison function for reverse sorting tasks by completion status.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _rsortByCompletion($a, $b)
    {
        if ($a->completed == $b->completed) {
            return self::_sortByIdentity($b, $a);
        }
        return ($a->completed < $b->completed) ? -1 : 1;
    }

    /**
     * Comparison function for sorting tasks by owner.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  1 if task one is greater, -1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _sortByOwner($a, $b)
    {
        $ashare = $GLOBALS['nag_shares']->getShare($a->tasklist);
        $bshare = $GLOBALS['nag_shares']->getShare($b->tasklist);

        $aowner = $a->tasklist;
        $bowner = $b->tasklist;

        if ($aowner != $ashare->get('owner')) {
            $aowner = $ashare->get('name');
        }
        if ($bowner != $bshare->get('owner')) {
            $bowner = $bshare->get('name');
        }

        $diff = strcasecmp($aowner, $bowner);
        if ($diff == 0) {
            return self::_sortByIdentity($a, $b);
        } else {
            return $diff;
        }
    }

    /**
     * Comparison function for reverse sorting tasks by owner.
     *
     * @param array $a  Task one.
     * @param array $b  Task two.
     *
     * @return integer  -1 if task one is greater, 1 if task two is greater;
     *                  0 if they are equal.
     */
    public static function _rsortByOwner($a, $b)
    {
        $ashare = $GLOBALS['nag_shares']->getShare($a->tasklist);
        $bshare = $GLOBALS['nag_shares']->getShare($b->tasklist);

        $aowner = $a->tasklist;
        $bowner = $b->tasklist;

        if ($aowner != $ashare->get('owner')) {
            $aowner = $ashare->get('name');
        }
        if ($bowner != $bshare->get('owner')) {
            $bowner = $bshare->get('name');
        }

        $diff = strcasecmp($bowner, $aowner);
        if ($diff == 0) {
            return self::_sortByIdentity($b, $a);
        } else {
            return $diff;
        }
    }

}
