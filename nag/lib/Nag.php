<?php
/**
 * Nag Base Class.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag
{
    /**
     * Status codes
     */
    const RESPONSE_NONE      = 1;
    const RESPONSE_ACCEPTED  = 2;
    const RESPONSE_DECLINED  = 3;

    /** iTip requests */
    const ITIP_REQUEST = 1;
    const ITIP_CANCEL  = 2;
    const ITIP_UPDATE  = 3;
    const RANGE_THISANDFUTURE = 'THISANDFUTURE';

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

    /**
     * WebDAV task list.
     */
    const DAV_WEBDAV = 1;

    /**
     * CalDAV task list.
     */
    const DAV_CALDAV = 2;

    /**
     * CalDAV principal.
     */
    const DAV_ACCOUNT = 3;

    /**
     *
     * @param integer $seconds
     *
     * @return string
     */
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
     * Parses a complete date-time string into a Horde_Date object.
     *
     * @param string $date       The date-time string to parse.
     * @param boolean $withtime  Whether time is included in the string.
     *
     * @return Horde_Date  The parsed date.
     * @throws Horde_Date_Exception
     */
    public static function parseDate($date, $withtime = true)
    {
        // strptime() is not available on Windows.
        if (!function_exists('strptime')) {
            return new Horde_Date($date);
        }

        // strptime() is locale dependent, i.e. %p is not always matching
        // AM/PM. Set the locale to C to workaround this, but grab the
        // locale's D_FMT before that.
        $format = Horde_Nls::getLangInfo(D_FMT);
        if ($withtime) {
            $format .= ' '
                . ($GLOBALS['prefs']->getValue('twentyFour') ? '%H:%M' : '%I:%M %p');
        }
        $old_locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');

        // Try exact format match first.
        $date_arr = strptime($date, $format);
        setlocale(LC_TIME, $old_locale);

        if (!$date_arr) {
            // Try with locale dependent parsing next.
            $date_arr = strptime($date, $format);
            if (!$date_arr) {
                // Try throwing at Horde_Date finally.
                return new Horde_Date($date);
            }
        }

        return new Horde_Date(
            array('year'  => $date_arr['tm_year'] + 1900,
                  'month' => $date_arr['tm_mon'] + 1,
                  'mday'  => $date_arr['tm_mday'],
                  'hour'  => $date_arr['tm_hour'],
                  'min'   => $date_arr['tm_min'],
                  'sec'   => $date_arr['tm_sec']));
    }

    /**
     * Retrieves the current user's task list from storage.
     *
     * This function will also sort the resulting list, if requested.
     *
     * @param arary $options  Options array:
     *   - altsortby: (string) The secondary sort field. Same values as sortdir.
     *                DEFAULT: altsortby pref is used.
     *   - completed: (integer) Which task to retrieve. A Nag::VIEW_* constant.
     *                DEFAULT: show_completed pref is used.
     *   - external: (boolean) Whether to include tasks from other applications
     *               too.
     *               DEFAULT: true.
     *   - include_history: (boolean) Autoload created/modified data from
     *                      Horde_History.
     *                      DEFAULT: true (Automatically load history data).
     *   - include_tags: (boolean) Autoload all tags.
     *                   DEFAULT: false (Tags are lazy loaded as needed.)
     *   - sortby: (string)  A Nag::SORT_* constant for the field to sort by.
     *             DEFAULT: sortby pref is used.
     *   - sortdir: (string) Direction of sort. NAG::SORT_ASCEND or
     *              NAG::SORT_DESCEND.
     *              DEFAULT: sortdir pref is used.
     *   - tasklists: (array) An array of tasklists to include.
     *                DEFAULT: Use $GLOBALS['display_tasklists'];
     *
     * @return Nag_Task  A list of the requested tasks.
     */
    public static function listTasks(array $options = array())
    {
        global $prefs, $registry;

        // Prevent null tasklists value from obscuring the default value.
        if (array_key_exists('tasklists', $options) && empty($options['tasklists'])) {
            unset($options['tasklists']);
        }

        $options = array_merge(
            array(
                'sortby' => $prefs->getValue('sortby'),
                'sortdir' => $prefs->getValue('sortdir'),
                'altsortby' => $prefs->getValue('altsortby'),
                'tasklists' => $GLOBALS['display_tasklists'],
                'completed' => $prefs->getValue('show_completed'),
                'include_tags' => false,
                'external' => true,
                'include_history' => true
            ),
            $options
        );

        if (!is_array($options['tasklists'])) {
            $options['tasklists'] = array($options['tasklists']);
        }
        $tasks = new Nag_Task();
        foreach ($options['tasklists'] as $tasklist) {
            $storage = $GLOBALS['injector']
                ->getInstance('Nag_Factory_Driver')
                ->create($tasklist);

            // Retrieve the tasklist from storage.
            $storage->retrieve($options['completed'], $options['include_history']);
            $tasks->mergeChildren($storage->tasks->children);
        }

        // Process all tasks.
        $tasks->process();

        if ($options['external'] &&
            ($apps = @unserialize($prefs->getValue('show_external'))) &&
            is_array($apps)) {
            foreach ($apps as $app) {
                // We look for registered apis that support listAs(taskHash).
                if ($app == 'nag' ||
                    !$registry->hasMethod('getListTypes', $app)) {
                    continue;
                }
                try {
                    $types = $registry->callByPackage($app, 'getListTypes');
                } catch (Horde_Exception $e) {
                    continue;
                }
                if (empty($types['taskHash'])) {
                    continue;
                }

                try {
                    $newtasks = $registry->callByPackage($app, 'listAs', array('taskHash'));
                    foreach ($newtasks as $task) {
                        if (!isset($task['priority'])) {
                            $task['priority'] = 3;
                        }
                        $task['tasklist_id'] = '**EXTERNAL**';
                        $task['tasklist_name'] = $registry->get('name', $app);
                        $task = new Nag_Task(null, $task);
                        if (($options['completed'] == Nag::VIEW_INCOMPLETE &&
                             ($task->completed ||
                              $task->start > $_SERVER['REQUEST_TIME'])) ||
                            ($options['completed'] == Nag::VIEW_COMPLETE &&
                             !$task->completed) ||
                            ($options['completed'] == Nag::VIEW_FUTURE &&
                             ($task->completed ||
                              !$task->start ||
                              $task->start < $_SERVER['REQUEST_TIME'])) ||
                            ($options['completed'] == Nag::VIEW_FUTURE_INCOMPLETE &&
                             $task->completed)) {
                            continue;
                        }
                        $tasks->add($task);
                    }
                } catch (Horde_Exception $e) {
                    Horde::log($e);
                }
            }
        }

        // Sort the array.
        $tasks->sort($options['sortby'], $options['sortdir'], $options['altsortby']);

        // Preload tags if requested.
        if ($options['include_tags']) {
            $tasks->loadTags();
        }

        return $tasks;
    }

    /**
     * Returns a single task.
     *
     * @param string $tasklist  A tasklist.
     * @param string $task      A task id.
     *
     * @return Nag_Task  The task hash.
     */
    public static function getTask($tasklist, $task)
    {
        $storage = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create($tasklist);
        $task = $storage->get($task);
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

        $tasklists = self::listTasklists(true, Horde_Perms::ALL);

        $count = 0;
        foreach (array_keys($tasklists) as $tasklist) {
            /* Create a Nag storage instance. */
            $storage = $GLOBALS['injector']
                ->getInstance('Nag_Factory_Driver')
                ->create($tasklist);
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
            $tasklist = self::getDefaultTasklist(Horde_Perms::EDIT);
        } elseif (!self::hasPermission($tasklist, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }

        $storage = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create($tasklist);
        $dateParser = Horde_Date_Parser::factory(
            array('locale' => $GLOBALS['prefs']->getValue('language')) );

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

            // Look for tags to be added in the text.
            $pattern = '/#\w+/';
            $tags = array();
            if (preg_match_all($pattern, $name, $results)) {
                $tags = $results[0];
                $name = str_replace($tags, '', $name);
                $tags = array_map(function($x) { return substr($x, -(strlen($x) - 1)); }, $tags);
            } else {
                $tags = '';
            }

            if (isset($task['parent'])) {
                $newTask = $storage->add(array('name' => $name, 'due' => $due, 'parent' => $tasks[$task['parent']]['id'], 'tags' => $tags));
            } else {
                $newTask = $storage->add(array('name' => $name, 'due' => $due, 'tags' => $tags));
            }
            $uids[] = $newTask[1];
            $task['id'] = $newTask[0];
        }

        return $uids;
    }

    /**
     * Returns all the alarms active right on $date.
     *
     * @param integer $date     The unix epoch time to check for alarms.
     * @param array $tasklists  An array of tasklists
     *
     * @return array  An array of Nag_Task objects with alarms active on $date.
     */
    public static function listAlarms($date, array $tasklists = null)
    {
        if (is_null($tasklists)) {
            $tasklists = $GLOBALS['display_tasklists'];
        }

        $tasks = array();
        foreach ($tasklists as $tasklist) {
            /* Create a Nag storage instance. */
            $storage = $GLOBALS['injector']
                ->getInstance('Nag_Factory_Driver')
                ->create($tasklist);

            /* Retrieve the alarms for the task list. */
            $newtasks = $storage->listAlarms($date);

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
     * @param boolean $owneronly  Only return task lists that this user owns?
     *                            Defaults to false.
     * @param integer $permission The permission to filter task lists by.
     * @param boolean $smart      Include SmartLists in the results.
     *
     * @return array  The task lists.
     */
    public static function listTasklists($owneronly = false,
                                         $permission = Horde_Perms::SHOW,
                                         $smart = true)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }
        $att = array();
        if ($owneronly) {
            $att = array('owner' => $GLOBALS['registry']->getAuth());
        }
        if (!$smart) {
            $att['issmart'] = 0;
        }

        try {
            $tasklists = $GLOBALS['nag_shares']->listShares(
                $GLOBALS['registry']->getAuth(),
                array('perm' => $permission,
                      'attributes' => $att,
                      'sort_by' => 'name'));
            if ($GLOBALS['registry']->isAdmin()) {
                $tasklists = array_merge(
                    $tasklists,
                    $GLOBALS['nag_shares']->listSystemShares()
                );
            }
        } catch (Horde_Share_Exception $e) {
            Horde::log($e->getMessage(), 'ERR');
            return array();
        }

        if ($owneronly) {
            return $tasklists;
        }

        $display_tasklists = @unserialize($GLOBALS['prefs']->getValue('display_tasklists'));
        if (is_array($display_tasklists)) {
            foreach ($display_tasklists as $id) {
                try {
                    $tasklist = $GLOBALS['nag_shares']->getShare($id);
                    if ($tasklist->hasPermission($GLOBALS['registry']->getAuth(), $permission)) {
                        $tasklists[$id] = $tasklist;
                    }
                } catch (Horde_Exception_NotFound $e) {
                } catch (Horde_Share_Exception $e) {
                    Horde::log($e);
                    return array();
                }
            }
        }

        return $tasklists;
    }

    /**
     * Returns whether the current user has certain permissions on a tasklist.
     *
     * @param string $tasklist  A tasklist id.
     * @param integer $perm     A Horde_Perms permission mask.
     *
     * @return boolean  True if the current user has the requested permissions.
     */
    public static function hasPermission($tasklist, $perm)
    {
        try {
            $share = $GLOBALS['nag_shares']->getShare($tasklist);
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), $perm)) {
                throw new Horde_Exception_NotFound();
            }
        } catch (Horde_Exception_NotFound $e) {
            return false;
        }
        return true;
    }

    /**
     * Returns the default tasklist for the current user at the specified
     * permissions level.
     *
     * @param integer $permission  Horde_Perms constant for permission level
     *                             required.
     *
     * @return string  The default tasklist or null if none.
     */
    public static function getDefaultTasklist($permission = Horde_Perms::SHOW)
    {
        $tasklists = self::listTasklists(false, $permission);

        $default_tasklist = $GLOBALS['prefs']->getValue('default_tasklist');
        if (isset($tasklists[$default_tasklist])) {
            return $default_tasklist;
        }

        $default_tasklist = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Tasklists')
            ->create()
            ->getDefaultShare();

        if (!isset($tasklists[$default_tasklist])) {
            reset($tasklists);
            $default_tasklist = key($tasklists);
        }

        $GLOBALS['prefs']->setValue('default_tasklist', $default_tasklist);
        return $default_tasklist;
    }

    /**
     * Creates a new share.
     *
     * @param array $info       Hash with tasklist information.
     * @param boolean $display  Add the new tasklist to display_tasklists
     *
     * @return Horde_Share  The new share.
     */
    public static function addTasklist(array $info, $display = true)
    {
        try {
            $tasklist = $GLOBALS['nag_shares']->newShare(
                $GLOBALS['registry']->getAuth(),
                strval(new Horde_Support_Randomid()), $info['name']);
            $tasklist->set('color', $info['color']);
            $tasklist->set('desc', $info['description']);
            if (!empty($info['system'])) {
                $tasklist->set('owner', null);
            }

            // Smartlist
            if (!empty($info['search'])) {
                $tasklist->set('search', $info['search']);
                $tasklist->set('issmart', 1);
            }

            $GLOBALS['nag_shares']->addShare($tasklist);
        } catch (Horde_Share_Exception $e) {
            throw new Nag_Exception($e);
        }

        if ($display) {
            $GLOBALS['display_tasklists'][] = $tasklist->getName();
            $GLOBALS['prefs']->setValue('display_tasklists', serialize($GLOBALS['display_tasklists']));
        }

        return $tasklist;
    }

    /**
     * Updates an existing share.
     *
     * @param Horde_Share_Object $tasklist  The share to update.
     * @param array $info                   Hash with task list information.
     *
     * @throws Horde_Exception_PermissionDenied
     * @throws Nag_Exception
     */
    public static function updateTasklist(Horde_Share_Object $tasklist, array $info)
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
        if ($tasklist->get('issmart')) {
            if (empty($info['search'])) {
                throw new Nag_Exception(_("Missing valid search criteria"));
            }
            $tasklist->set('search', $info['search']);
        }
        try {
            $tasklist->save();
        } catch (Horde_Share_Exception $e) {
            throw new Nag_Exception(sprintf(_("Unable to save task list \"%s\": %s"), $info['name'], $e->getMessage()));
        }
    }

    /**
     * Deletes a task list.
     *
     * @param Horde_Share_Object $tasklist  The task list to delete.
     *
     * @throws Nag_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public static function deleteTasklist(Horde_Share_Object $tasklist)
    {
        if (!$GLOBALS['registry']->getAuth() ||
            ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
             (!is_null($tasklist->get('owner')) || !$GLOBALS['registry']->isAdmin()))) {
            throw new Horde_Exception_PermissionDenied(_("You are not allowed to delete this task list."));
        }

        // Delete the task list.
        $storage = &$GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($tasklist->getName());
        $result = $storage->deleteAll();

        // Remove share and all groups/permissions.
        try {
            $GLOBALS['nag_shares']->removeShare($tasklist);
        } catch (Horde_Share_Exception $e) {
            throw new Nag_Exception($e);
        }
    }

    /**
     * Returns the label to be used for a task list.
     *
     * Attaches the owner name of shared task lists if necessary.
     *
     * @param Horde_Share_Object  A task list.
     *
     * @return string  The task list's label.
     */
    public static function getLabel($tasklist)
    {
        $label = $tasklist->get('name');
        if ($tasklist->get('owner') &&
            $tasklist->get('owner') != $GLOBALS['registry']->getAuth()) {
            $label .= ' [' . $GLOBALS['registry']->convertUsername($tasklist->get('owner'), false) . ']';
        }
        return $label;
    }

    /**
     * Returns a DAV URL to be used for a task list.
     *
     * @param integer $type       A Nag::DAV_* constant.
     * @param Horde_Share_Object  A task list.
     *
     * @return string  The task list's URL.
     * @throws Horde_Exception
     */
    public static function getUrl($type, $tasklist)
    {
        global $conf, $injector, $registry;

        $url = $registry->get('webroot', 'horde');
        $rewrite = isset($conf['urls']['pretty']) &&
            $conf['urls']['pretty'] == 'rewrite';

        switch ($type) {
        case Nag::DAV_WEBDAV:
            if ($rewrite) {
                $url .= '/rpc/nag/';
            } else {
                $url .= '/rpc.php/nag/';
            }
            $url = Horde::url($url, true, -1)
                . ($tasklist->get('owner')
                   ? $registry->convertUsername($tasklist->get('owner'), false)
                   : '-system-')
                . '/' . $tasklist->getName() . '.ics';
            break;

        case Nag::DAV_CALDAV:
            if ($rewrite) {
                $url .= '/rpc/calendars/';
            } else {
                $url .= '/rpc.php/calendars/';
            }
            $url = Horde::url($url, true, -1)
                . $registry->convertUsername($registry->getAuth(), false)
                . '/'
                . $injector->getInstance('Horde_Dav_Storage')
                    ->getExternalCollectionId($tasklist->getName(), 'tasks')
                . '/';
            break;

        case Nag::DAV_ACCOUNT:
            if ($rewrite) {
                $url .= '/rpc/';
            } else {
                $url .= '/rpc.php/';
            }
            $url = Horde::url($url, true, -1)
                . 'principals/' . $registry->convertUsername($registry->getAuth(), false) . '/';
            break;
        }

        return $url;
    }

    /**
     * Returns a random CSS color.
     *
     * @return string  A random CSS color string.
     */
    public static function randomColor()
    {
        $color = '#';
        for ($i = 0; $i < 3; $i++) {
            $color .= sprintf('%02x', mt_rand(0, 255));
        }
        return $color;
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
     * @param integer $completed  The completion value.
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
     * @param integer $priority  The priority level.
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
     * @param integer $value  The alarm value in minutes.
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
     * Returns formatted string representing a task organizer.
     *
     * @param string $organizer  The organinzer, as an email or mailto: format.
     * @param boolean $link     Whether to link to an email compose screen.
     *
     * @return string  The formatted organizer name.
     */
    public static function formatOrganizer($organizer, $link = false)
    {
        if (empty($organizer)) {
            return;
        }
        $rfc = new Horde_Mail_Rfc822();
        $list = $rfc->parseAddressList(str_ireplace('mailto:', '', $organizer), array('limit' => 1));
        if (empty($list)) {
            return;
        }
        $email = $list[0];
        if ($link && $GLOBALS['registry']->hasMethod('mail/compose')) {
            return Horde::link($GLOBALS['registry']->call(
                                   'mail/compose',
                                   array(array('to' => $email->bare_address))))
                . htmlspecialchars($email->writeAddress())
                . '</a>';
        } else {
            return htmlspecialchars($email->writeAddress());
        }
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

        if ($GLOBALS['conf']['assignees']['allow_external']) {
            $email = new Horde_Mail_Rfc822_Address($assignee);
            $fullname = $email->personal;
            $email = $email->bare_address;
        } else {
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($assignee);
            $fullname = $identity->getValue('fullname');
            if (!strlen($fullname)) {
                $fullname = $assignee;
            }
            $email = $identity->getValue('from_addr');
        }

        if ($link && !empty($email) &&
            $GLOBALS['registry']->hasMethod('mail/compose')) {
            return Horde::link($GLOBALS['registry']->call(
                                   'mail/compose',
                                   array(array('to' => $email))))
                . htmlspecialchars($fullname . ' <' . $email . '>')
                . '</a>';
        }

        return htmlspecialchars($fullname);
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
        if (($actionID = Horde_Util::getFormData('actionID')) !== null) {
            $tasklistId = Horde_Util::getFormData('display_tasklist');
            switch ($actionID) {
            case 'add_displaylist':
                if (!in_array($tasklistId, $GLOBALS['display_tasklists'])) {
                    $GLOBALS['display_tasklists'][] = $tasklistId;
                }
                break;
            case 'remove_displaylist':
                if (in_array($tasklistId, $GLOBALS['display_tasklists'])) {
                    $key = array_search($tasklistId, $GLOBALS['display_tasklists']);
                    unset($GLOBALS['display_tasklists'][$key]);
                }
            }
        }

        // Make sure all task lists exist now, to save on checking later.
        $_temp = $GLOBALS['display_tasklists'];
        $GLOBALS['all_tasklists'] = self::listTasklists();
        $GLOBALS['display_tasklists'] = array();
        foreach ($_temp as $id) {
            if (isset($GLOBALS['all_tasklists'][$id])) {
                $GLOBALS['display_tasklists'][] = $id;
            }
        }

        /* All tasklists for guests. */
        if (!count($GLOBALS['display_tasklists']) &&
            !$GLOBALS['registry']->getAuth()) {
            $GLOBALS['display_tasklists'] = array_keys($GLOBALS['all_tasklists']);
        }

        $tasklists = $GLOBALS['injector']->getInstance('Nag_Factory_Tasklists')
            ->create();
        if (($new_default = $tasklists->ensureDefaultShare()) !== null) {
            $GLOBALS['display_tasklists'][] = $new_default;
            $GLOBALS['prefs']->setValue('default_tasklist', $new_default);
        }

        $GLOBALS['prefs']->setValue('display_tasklists', serialize($GLOBALS['display_tasklists']));
    }

    /**
     * Trigger notifications.
     */
    public static function status()
    {
        global $notification;

        if (empty($GLOBALS['conf']['alarms']['driver'])) {
            // Get any alarms in the next hour.
            try {
                $alarmList = self::listAlarms($_SERVER['REQUEST_TIME']);
                $messages = array();
                foreach ($alarmList as $task) {
                    $differential = $task->due - $_SERVER['REQUEST_TIME'];
                    $key = $differential;
                    while (isset($messages[$key])) {
                        $key++;
                    }
                    if ($differential >= -60 && $differential < 60) {
                        $messages[$key] = array(sprintf(_("%s is due now."), $task->name), 'horde.alarm');
                    } elseif ($differential >= 60) {
                        $messages[$key] = array(sprintf(_("%s is due in %s"), $task->name,
                                                        self::secondsToString($differential)), 'horde.alarm');
                    }
                }

                ksort($messages);
                foreach ($messages as $message) {
                    $notification->push($message[0], $message[1]);
                }
            } catch (Nag_Exception $e) {
                Horde::log($e, 'ERR');
                $notification->push($e->getMessage(), 'horde.error');
            }
        }

        // Check here for guest task lists so that we don't get multiple
        // messages after redirects, etc.
        if (!$GLOBALS['registry']->getAuth() && !count(self::listTasklists())) {
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
     *
     * @throws Nag_Exception
     */
    public static function sendNotification($action, $task, $old_task = null)
    {
        if (!in_array($action, array('add', 'edit', 'delete'))) {
            throw new Nag_Exception('Unknown event action: ' . $action);
        }

        try {
            $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
        } catch (Horde_Share_Exception $e) {
            Horde::log($e->getMessage(), 'ERR');
            throw new Nag_Exception($e);
        }

        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
        $recipients = array();
        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
        $from = $identity->getDefaultFromAddress(true);

        $owner = $share->get('owner');
        if (strlen($owner)) {
            $recipients[$owner] = self::_notificationPref($owner, 'owner');
        }

        foreach ($share->listUsers(Horde_Perms::READ) as $user) {
            if (empty($recipients[$user])) {
                $recipients[$user] = self::_notificationPref($user, 'read', $task->tasklist);
            }
        }
        foreach ($share->listGroups(Horde_Perms::READ) as $group) {
            try {
                $group_users = $groups->listUsers($group);
            } catch (Horde_Group_Exception $e) {
                Horde::log($e, 'ERR');
                continue;
            }

            foreach ($group_users as $user) {
                if (empty($recipients[$user])) {
                    $recipients[$user] = self::_notificationPref($user, 'read', $task->tasklist);
                }
            }
        }

        $addresses = array();
        foreach ($recipients as $user => $vals) {
            if (!$vals) {
                continue;
            }
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);
            $email = $identity->getValue('from_addr');
            if (strpos($email, '@') === false) {
                continue;
            }
            if (!isset($addresses[$vals['lang']][$vals['tf']][$vals['df']])) {
                $addresses[$vals['lang']][$vals['tf']][$vals['df']] = array();
            }

            $tmp = new Horde_Mail_Rfc822_Address($email);
            $tmp->personal = $identity->getValue('fullname');
            $addresses[$vals['lang']][$vals['tf']][$vals['df']][] = strval($tmp);
        }

        if (!$addresses) {
            return;
        }

        $mail = new Horde_Mime_Mail(array(
            'User-Agent' => 'Nag ' . $GLOBALS['registry']->getVersion(),
            'Precedence' => 'bulk',
            'Auto-Submitted' => 'auto-generated',
            'From' => $from));

        foreach ($addresses as $lang => $twentyFour) {
            $GLOBALS['registry']->setLanguageEnvironment($lang);

            $view_link = Horde::url('view.php', true)->add(array(
                'tasklist' => $task->tasklist,
                'task' => $task->id
            ))->setRaw(true);

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
                                  Nag::getLabel($old_share), Nag::getLabel($share));
                }
                if ($old_task->parent_id != $task->parent_id) {
                    $old_parent = $old_task->getParent();
                    try {
                        $parent = $task->getParent();
                        $notification_message .= "\n - "
                            . sprintf(_("Changed parent task from \"%s\" to \"%s\""),
                                      $old_parent ? $old_parent->name : _("no parent"),
                                      $parent ? $parent->name : _("no parent"));
                    } catch (Nag_Exception $e) {
                    }
                }
                if ($old_task->assignee != $task->assignee) {
                    $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($old_task->assignee);
                    $old_name = $identity->getValue('fullname');
                    if (!strlen($old_name)) {
                        $old_name = $old_task->assignee;
                    }
                    $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($task->assignee);
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
                                  $old_task->due ? self::formatDate($old_task->due) : _("no due date"),
                                  $task->due ? self::formatDate($task->due) : _("no due date"));
                }
                if ($old_task->start != $task->start) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed start date from %s to %s"),
                                  $old_task->start ? self::formatDate($old_task->start) : _("no start date"),
                                  $task->start ? self::formatDate($task->start) : _("no start date"));
                }
                if ($old_task->alarm != $task->alarm) {
                    $notification_message .= "\n - "
                        . sprintf(_("Changed alarm from %s to %s"),
                                  self::formatAlarm($old_task->alarm), self::formatAlarm($task->alarm));
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

            $mail->addHeader('Subject', $subject . ' ' . $task->name);

            foreach ($twentyFour as $tf => $dateFormat) {
                foreach ($dateFormat as $df => $df_recipients) {
                    $message = sprintf($notification_message,
                                       $task->name,
                                       Nag::getLabel($share),
                                       $task->due ? strftime($df, $task->due) . ' ' . date($tf ? 'H:i' : 'h:ia', $task->due) : '');
                    if (strlen(trim($task->desc))) {
                        $message .= "\n\n" . _("Task description:") . "\n\n" . $task->desc;
                    }

                    $mail->setBody($message);
                    $mail->clearRecipients();
                    $mail->addRecipients($df_recipients);

                    Horde::log(sprintf('Sending event notifications for %s to %s', $task->name, implode(', ', $df_recipients)), 'INFO');
                    $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
                }
            }
        }
    }

    /**
     * Builds the body MIME part of a multipart message.
     *
     * @param Horde_View $view        A view to render the HTML and plain text
     *                                templates for the messate.
     * @param string $template        The template base name for the view.
     * @param Horde_Mime_Part $image  The MIME part of a related image.
     *
     * @return Horde_Mime_Part  A multipart/alternative MIME part.
     */
    public static function buildMimeMessage(Horde_View $view, $template,
                                            Horde_Mime_Part $image)
    {
        $multipart = new Horde_Mime_Part();
        $multipart->setType('multipart/alternative');
        $bodyText = new Horde_Mime_Part();
        $bodyText->setType('text/plain');
        $bodyText->setCharset('UTF-8');
        $bodyText->setContents($view->render($template . '.plain.php'));
        $bodyText->setDisposition('inline');
        $multipart->addPart($bodyText);
        $bodyHtml = new Horde_Mime_Part();
        $bodyHtml->setType('text/html');
        $bodyHtml->setCharset('UTF-8');
        $bodyHtml->setContents($view->render($template . '.html.php'));
        $bodyHtml->setDisposition('inline');
        $related = new Horde_Mime_Part();
        $related->setType('multipart/related');
        $related->setContentTypeParameter('start', $bodyHtml->setContentId());
        $related->addPart($bodyHtml);
        $related->addPart($image);
        $multipart->addPart($related);
        return $multipart;
    }

    /**
     * Returns a MIME part for an image to be embedded into a HTML document.
     *
     * @param string $file  An image file name.
     *
     * @return Horde_Mime_Part  A MIME part representing the image.
     */
    public static function getImagePart($file)
    {
        $background = Horde_Themes::img($file);
        $image = new Horde_Mime_Part();
        $image->setType('image/png');
        $image->setContents(file_get_contents($background->fs));
        $image->setContentId();
        $image->setDisposition('attachment');
        return $image;
    }

    /**
     * Returns the real name, if available, of a user.
     *
     * @param string $uid  The userid of the user to retrieve
     *
     * @return string  The fullname of the user.
     */
    public static function getUserName($uid)
    {
        static $names = array();

        if (!isset($names[$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($uid);
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
    protected static function _notificationPref($user, $mode, $tasklist = null)
    {
        $prefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('nag', array(
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
                return in_array($tasklist, $display_tasklists) ? $vals : false;
            }
        }

        return false;
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
            return self::_sortByName($a, $b);
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
        return self::_sortByPriority($b, $a);
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
            return self::_sortByName($a, $b);
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
        return self::_sortByEstimate($b, $a);
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
            return self::_sortByName($a, $b);
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
        return self::_sortByDue($b, $a);
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
            return self::_sortByName($a, $b);
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
        return self::_sortByStart($b, $a);
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
            return self::_sortByName($a, $b);
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
        return self::_sortByCompletion($b, $a);
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
        $diff = strcasecmp(self::_getOwner($a), self::_getOwner($b));
        if ($diff == 0) {
            return self::_sortByName($a, $b);
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
        return self::_sortByOwner($b, $a);
    }

    /**
     * Returns the owner of a task.
     *
     * @param Nag_Task $task  A task.
     *
     * @return string  The task's owner.
     */
    protected static function _getOwner($task)
    {
        if ($task->tasklist == '**EXTERNAL**') {
            return $GLOBALS['registry']->getAuth();
        }
        $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
        $owner = $task->tasklist;
        if ($owner != $share->get('owner')) {
            $owner = $share->get('name');
        }
        return $owner;
    }

    /**
     * Returns the tasklists that should be used for syncing.
     *
     * @return array  An array of task list ids
     */
    public static function getSyncLists()
    {
        $cs = unserialize($GLOBALS['prefs']->getValue('sync_lists'));
        if (!empty($cs)) {
            // Have a pref, make sure it's still available
            $lists = self::listTasklists(false, Horde_Perms::EDIT);
            $cscopy = array_flip($cs);
            foreach ($cs as $c) {
                if (empty($lists[$c])) {
                    unset($cscopy[$c]);
                }
            }

            // Have at least one
            if (count($cscopy)) {
                return array_flip($cscopy);
            }
        }

        if ($cs = self::getDefaultTasklist(Horde_Perms::EDIT)) {
            return array($cs);
        }

        return array();
    }

    public static function getUserEmail($user)
    {
        if (strpos($user, '@')) {
            return $user;
        }
        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);
        return $identity->getValue('from_addr');
    }

    /**
     * Maps an iCalendar attendee response string to the corresponding
     * Nag value.
     *
     * @param string $response  The attendee response.
     *
     * @return string  The Nag response value.
     */
    public static function responseFromICal($response)
    {
        switch (Horde_String::upper($response)) {
        case 'ACCEPTED':
            return self::RESPONSE_ACCEPTED;

        case 'DECLINED':
            return self::RESPONSE_DECLINED;

        case 'NEEDS-ACTION':
        default:
            return self::RESPONSE_NONE;
        }
    }

    /**
     * Sends out iTip task notification to the assignee.
     *
     * Can be used to send task invitations, updates, and cancellations.
     *
     * @param Nag_Task $task  The task in question.
     * @param Horde_Notification_Handler $notification
     *        A notification object used to show result status.
     * @param integer $action
     *        The type of notification to send. One of the Nag::ITIP_* values.
     * @param Horde_Date $instance
     *        If cancelling a single instance of a recurring task, the date of
     *        this instance.
     * @param  string $range  The range parameter if this is a recurring event.
     *                        Possible values are self::RANGE_THISANDFUTURE
     */
    public static function sendITipNotifications(
        Nag_Task $task, Horde_Notification_Handler $notification,
        $action, Horde_Date $instance = null, $range = null)
    {
        global $injector, $registry, $nag_shares;

        if (!$task->assignee) {
            return;
        }

        $ident = $injector->getInstance('Horde_Core_Factory_Identity')->create($task->creator);
        if (!$ident->getValue('from_addr')) {
            $notification->push(sprintf(_("You do not have an email address configured in your Personal Information Preferences. You must set one %shere%s before event notifications can be sent."), $registry->getServiceLink('prefs', 'kronolith')->add(array('app' => 'horde', 'group' => 'identities'))->link(), '</a>'), 'horde.error', array('content.raw'));
            return;
        }

        // Generate image mime part first and only once, because we
        // need the Content-ID.
        $image = self::getImagePart('big_invitation.png');
        $share = $nag_shares->getShare($task->tasklist);
        $view = new Horde_View(array('templatePath' => NAG_TEMPLATES . '/itip'));
        new Horde_View_Helper_Text($view);
        $view->identity = $ident;
        $view->task = $task;
        $view->imageId = $image->getContentId();

        $email = Nag::getUserEmail($task->assignee);
        if (strpos($email, '@') === false) {
            continue;
        }

       /* Determine all notification-specific strings. */
       $method = 'REQUEST';
       switch ($action) {
       case self::ITIP_CANCEL:
            /* Cancellation. */
            $method = 'CANCEL';
            $filename = 'task-cancellation.ics';
            $view->subject = sprintf(_("Cancelled: %s"), $task->name);
            if (empty($instance)) {
                $view->header = sprintf(_("%s has cancelled \"%s\"."), $ident->getName(), $task->name);
            } else {
                $view->header = sprintf(_("%s has cancelled an instance of the recurring \"%s\"."), $ident->getName(), $task->name);
            }
            break;
        case self::ITIP_UPDATE:
            if (!empty($task->organizer) && $task->organizer != Nag::getUserEmail($task->creator)) {
                // Sending a progress update.
                $method = 'REPLY';
            } else {
                $method = 'UPDATE';
            }
        case self::ITIP_REQUEST:
        default:
            if (empty($task->status) || $task->status == self::RESPONSE_NONE) {
                /* Invitation. */
                $filename = 'task-invitation.ics';
                $view->subject = $task->name;
                $view->header = sprintf(_("%s wishes to make you aware of \"%s\"."), $ident->getName(), $task->name);
            } else {
                $filename = 'task-update.ics';
                $view->subject = sprintf(_("Updated: %s."), $task->name);
                $view->header = sprintf(_("%s wants to notify you about changes of \"%s\"."), $ident->getName(), $task->name);
            }
            break;
        }
        $view->attendees = $email;
        $view->organizer = empty($task->organizer)
            ? $registry->convertUserName($task->creator, false)
            : $task->organizer;

        /* Build the iCalendar data */
        $iCal = new Horde_Icalendar();
        $iCal->setAttribute('METHOD', $method);
        $vevent = $task->toiCalendar($iCal);

        $iCal->addComponent($vevent);

        /* text/calendar part */
        $ics = new Horde_Mime_Part();
        $ics->setType('text/calendar');
        $ics->setContents($iCal->exportvCalendar());
        $ics->setName($filename);
        $ics->setContentTypeParameter('METHOD', $method);
        $ics->setCharset('UTF-8');
        $ics->setEOL("\r\n");

        /* application/ics part */
        $ics2 = clone $ics;
        $ics2->setType('application/ics');

        /* multipart/mixed part */
        $multipart = new Horde_Mime_Part();
        $multipart->setType('multipart/mixed');
        $inner = self::buildMimeMessage($view, 'notification', $image);
        $inner->addPart($ics);
        $multipart->addPart($inner);
        $multipart->addPart($ics2);

        $recipient = $method != 'REPLY'
            ? new Horde_Mail_Rfc822_Address($email)
            : new Horde_Mail_Rfc822_Address($task->organizer);

        $mail = new Horde_Mime_Mail(
            array('Subject' => $view->subject,
                  'To' => $recipient,
                  'From' => $ident->getDefaultFromAddress(true),
                  'User-Agent' => 'Nag ' . $registry->getVersion()));
        $mail->setBasePart($multipart);

        try {
            $mail->send($injector->getInstance('Horde_Mail'));
            $notification->push(
                sprintf(_("The task request notification to %s was successfully sent."), $recipient),
                'horde.success'
            );
        } catch (Horde_Mime_Exception $e) {
            $notification->push(
                sprintf(_("There was an error sending a task request notification to %s: %s"), $recipient, $e->getMessage(), $e->getCode()),
                'horde.error'
            );
        }
    }

}
