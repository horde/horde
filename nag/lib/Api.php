<?php
/**
 * Nag external API interface.
 *
 * This file defines Nag's external API interface. Other applications can
 * interact with Nag through this API.
 *
 * @package Nag
 */
class Nag_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'show' => '%application%/view.php?tasklist=|tasklist|&task=|task|&uid=|uid|'
    );

    /**
     * Returns a number of defaults necessary for the ajax view.
     *
     * @return array  A hash with default values.
     */
    public function ajaxDefaults()
    {
        return array(
            'URI_TASKLIST_EXPORT' => (string)Horde::url('data.php', true)->add(array('actionID' => 'export', 'exportTasks' => 1, 'exportID' => Horde_Data::EXPORT_ICALENDAR, 'exportList' => '')),
            'default_tasklist' => Nag::getDefaultTasklist(Horde_Perms::EDIT),
            'default_due' => (bool)$GLOBALS['prefs']->getValue('default_due'),
            'default_due_days' => (int)$GLOBALS['prefs']->getValue('default_due_days'),
            'default_due_time' => $GLOBALS['prefs']->getValue('default_due_time'),
        );
    }

    /**
     * Retrieves the current user's task list from storage.
     *
     * This function will also sort the resulting list, if requested.
     *
     * @param string $sortby        The field by which to sort
     *                              (NAG_SORT_PRIORITY, NAG_SORT_NAME
     *                              NAG_SORT_DUE, NAG_SORT_COMPLETION).
     * @param integer $sortdir      The direction by which to sort.
     * @param string $altsortby     The secondary sort field.
     * @param array $tasklists      An array of tasklist to display or
     *                              null/empty to display taskslists
     *                              $GLOBALS['display_tasklists'].
     * @param string $completed     Which tasks to retrieve (all, incomplete,
     *                              complete, future or future_incomplete).
     * @param boolean $json         Retrieve the results of the tasks in
     *                              'json format'.
     *
     * @return Nag_Task  A list of the requested tasks.
     */
    public function listTasks($sortby = null, $sortdir = null,
                              $altsortby = null, $tasklists = null,
                              $completed = null, $json = false)
    {
        $completedArray = array('incomplete' => Nag::VIEW_INCOMPLETE,
                                'all' => Nag::VIEW_ALL,
                                'complete' => Nag::VIEW_COMPLETE,
                                'future' => Nag::VIEW_FUTURE,
                                'future_incomplete' => Nag::VIEW_FUTURE_INCOMPLETE);

        if (!isset($sortby)) {
            $sortby = $GLOBALS['prefs']->getValue('sortby');
        }
        if (!isset($sortdir)) {
            $sortdir = $GLOBALS['prefs']->getValue('sortdir');
        }
        if (is_null($altsortby)) {
            $altsortby =  $GLOBALS['prefs']->getValue('altsortby');
        }
        if (is_null($tasklists)) {
            $tasklists = $GLOBALS['display_tasklists'];
        }
        if (is_null($completed) || !isset($completedArray[$completed])) {
            $completed = $GLOBALS['prefs']->getValue('show_completed');
        } else {
            $completed = $completedArray[$completed];
        }

        $tasks = Nag::listTasks($sortby, $sortdir, $altsortby, $tasklists, $completed);
        $tasks->reset();
        $list = array();
        while ($task = $tasks->each()) {
            $list[$task->id] = $json ? $task->toJson() : $task->toHash();
        }

        return $list;
    }

    /**
     * Returns a list of task lists.
     *
     * @param boolean $owneronly   Only return tasklists that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter tasklists by.
     *
     * @return array  The task lists.
     */
    public function listTasklists($owneronly = false, $permission = Horde_Perms::SHOW)
    {
        return Nag::listTasklists($owneronly, $permission);
    }

    /**
     * Adds a new task list.
     *
     * @param string $name        Task list name.
     * @param string $description Task list description.
     * @param string $color       Task list color.
     *
     * @return integer  The new tasklist's id.
     */
    public function addTasklist($name, $description = '', $color = '')
    {
        $tasklist = Nag::addTasklist(array('name' => $name, 'description' => $description, 'color' => $color));
        if (is_a($tasklist, 'PEAR_Error')) {
            return $tasklist;
        }
        return $tasklist->getName();
    }

    /**
     * Updates an existing task list.
     *
     * @param string $id   A task list id.
     * @param array $info  Hash with task list information.
     */
    public static function updateTasklist($id, $info)
    {
        try {
            $tasklist = $GLOBALS['nag_shares']->getShare($id);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Nag_Exception($e);
        }

        return Nag::updateTasklist($tasklist, $info);
    }

    /**
     * Deletes a task list.
     *
     * @param string $id  A task list id.
     */
    public function deleteTasklist($id)
    {
        $tasklist = $GLOBALS['nag_shares']->getShare($id);
        if (is_a($tasklist, 'PEAR_Error')) {
            return $tasklist;
        }
        return Nag::deleteTasklist($tasklist);
    }

    /**
     * Returns the last modification timestamp of a given uid.
     *
     * @param string $uid      The uid to look for.
     * @param string $tasklist The tasklist to look in.
     *
     * @return integer  The timestamp for the last modification of $uid.
     */
    public function modified($uid, $tasklist = null)
    {
        $modified = $this->getActionTimestamp($uid, 'modify', $tasklist);
        if (empty($modified)) {
            $modified = $this->getActionTimestamp($uid, 'add', $tasklist);
        }
        return $modified;
    }

    /**
     * Browse through Nag's object tree.
     *
     * @param string $path       The level of the tree to browse.
     * @param array $properties  The item properties to return. Defaults to 'name',
     *                           'icon', and 'browseable'.
     *
     * @return array  The contents of $path
     */
    public function browse($path = '', $properties = array())
    {
        global $registry;

        // Default properties.
        if (!$properties) {
            $properties = array('name', 'icon', 'browseable');
        }

        if (substr($path, 0, 3) == 'nag') {
            $path = substr($path, 3);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (empty($path)) {
            // This request is for a list of all users who have tasklists
            // visible to the requesting user.
            $tasklists = Nag::listTasklists(false, Horde_Perms::READ);
            $owners = array();
            foreach ($tasklists as $tasklist) {
                if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
                    !empty($GLOBALS['conf']['share']['hidden']) &&
                    !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
                    continue;
                }
                $owners[$tasklist->get('owner') ? $tasklist->get('owner') : '-system-'] = true;
            }

            $results = array();
            foreach (array_keys($owners) as $owner) {
                if (in_array('name', $properties)) {
                    $results['nag/' . $owner]['name'] = $owner;
                }
                if (in_array('icon', $properties)) {
                    $results['nag/' . $owner]['icon'] = Horde_Themes::img('user.png');
                }
                if (in_array('browseable', $properties)) {
                    $results['nag/' . $owner]['browseable'] = true;
                }
                if (in_array('contenttype', $properties)) {
                    $results['nag/' . $owner]['contenttype'] =
                        'httpd/unix-directory';
                }
                if (in_array('contentlength', $properties)) {
                    $results['nag/' . $owner]['contentlength'] = 0;
                }
                if (in_array('modified', $properties)) {
                    $results['nag/' . $owner]['modified'] =
                        $_SERVER['REQUEST_TIME'];
                }
                if (in_array('created', $properties)) {
                    $results['nag/' . $owner]['created'] = 0;
                }
            }
            return $results;

        } elseif (count($parts) == 1) {
            //
            // This request is for all tasklists owned by the requested user
            //
            $tasklists = $GLOBALS['nag_shares']->listShares($parts[0],
                Horde_Perms::SHOW,
                $parts[0]);

            // The last check returns all addressbooks for the requested user,
            // but that does not mean the requesting user has access to them.
            // Filter out those address books for which the requesting user has
            // no access.
            $tasklists = Nag::permissionsFilter($tasklists);

            $results = array();
            foreach ($tasklists as $tasklistId => $tasklist) {
                $retpath = 'nag/' . $parts[0] . '/' . $tasklistId;
                if (in_array('name', $properties)) {
                    $results[$retpath]['name'] = sprintf(_("Tasks from %s"), $tasklist->get('name'));
                    $results[$retpath . '.ics']['name'] = $tasklist->get('name');
                }
                if (in_array('icon', $properties)) {
                    $results[$retpath]['icon'] = Horde_Themes::img('nag.png');
                    $results[$retpath . '.ics']['icon'] = Horde_Themes::img('mime/icalendar.png');
                }
                if (in_array('browseable', $properties)) {
                    $results[$retpath]['browseable'] = $tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ);
                    $results[$retpath . '.ics']['browseable'] = false;
                }
                if (in_array('contenttype', $properties)) {
                    $results[$retpath]['contenttype'] = 'httpd/unix-directory';
                    $results[$retpath . '.ics']['contenttype'] = 'text/calendar';
                }
                if (in_array('contentlength', $properties)) {
                    $results[$retpath]['contentlength'] = 0;
                    $results[$retpath . '.ics']['contentlength'] = strlen($this->exportTasklist($tasklistId, 'text/calendar'));
                }
                if (in_array('modified', $properties)) {
                    // @TODO Find a way to get the actual modification times
                    $results[$retpath]['modified'] = $_SERVER['REQUEST_TIME'];
                    $results[$retpath . '.ics']['modified'] = $_SERVER['REQUEST_TIME'];
                }
                if (in_array('created', $properties)) {
                    // @TODO Find a way to get the actual creation times
                    $results[$retpath]['created'] = 0;
                    $results[$retpath . '.ics']['created'] = 0;
                }
            }
            return $results;

        } elseif (count($parts) == 2 && substr($parts[1], -4) == '.ics') {
            //
            // This is a request for the entire tasklist in iCalendar format.
            //
            $tasklist = substr($parts[1], 0, -4);
            if (!array_key_exists($tasklist, Nag::listTasklists(false, Horde_Perms::READ))) {
                return PEAR::raiseError(_("Invalid tasklist file requested."), 404);
            }
            $ical_data = $this->exportTasklist($tasklist, 'text/calendar');
            $result = array('data'          => $ical_data,
                'mimetype'      => 'text/calendar',
                'contentlength' => strlen($ical_data),
                'mtime'         => $_SERVER['REQUEST_TIME']);

            return $result;

        } elseif (count($parts) == 2) {
            //
            // This request is browsing into a specific tasklist.  Generate the list
            // of items and represent them as files within the directory.
            //
            if (!array_key_exists($parts[1], Nag::listTasklists(false, Horde_Perms::READ))) {
                return PEAR::raiseError(_("Invalid tasklist requested."), 404);
            }
            $storage = Nag_Driver::singleton($parts[1]);
            $result = $storage->retrieve();
            if (is_a($result, 'PEAR_Error')) {
                $result->code = 500;
                return $result;
            }

            $icon = Horde_Themes::img('nag.png');
            $results = array();
            $storage->tasks->reset();
            while ($task = $storage->tasks->each()) {
                $key = 'nag/' . $parts[0] . '/' . $parts[1] . '/' . $task->id;
                if (in_array('name', $properties)) {
                    $results[$key]['name'] = $task->name;
                }
                if (in_array('icon', $properties)) {
                    $results[$key]['icon'] = $icon;
                }
                if (in_array('browseable', $properties)) {
                    $results[$key]['browseable'] = false;
                }
                if (in_array('contenttype', $properties)) {
                    $results[$key]['contenttype'] = 'text/calendar';
                }
                if (in_array('contentlength', $properties)) {
                    // FIXME:  This is a hack.  If the content length is longer
                    // than the actual data then some WebDAV clients will report
                    // an error when the file EOF is received.  Ideally we should
                    // determine the actual size of the data and report it here, but
                    // the performance hit may be prohibitive.  This requires
                    // further investigation.
                    $results[$key]['contentlength'] = 1;
                }
                if (in_array('modified', $properties)) {
                    $results[$key]['modified'] = $this->modified($task->uid, $path);
                }
                if (in_array('created', $properties)) {
                    $results[$key]['created'] = $this->getActionTimestamp($task->uid, 'add', $path);
                }
            }
            return $results;
        } else {
            //
            // The only valid request left is for either a specific task item.
            //
            if (count($parts) == 3 &&
                array_key_exists($parts[1], Nag::listTasklists(false,
                    Horde_Perms::READ))) {
                        //
                        // This request is for a specific item within a given task list.
                        //
                        /* Create a Nag storage instance. */
                        $storage = Nag_Driver::singleton($parts[1]);
                        if (is_a($storage, 'PEAR_Error')) {
                            return PEAR::raiseError(sprintf(_("Connection failed: %s"), $storage->getMessage()));
                        }
                        $storage->retrieve();

                        $task = $storage->get($parts[2]);
                        if (is_a($task, 'PEAR_Error')) {
                            $task->code = 500;
                            return $task;
                        }

                        $result = array('data' => $this->export($task->uid, 'text/calendar'),
                            'mimetype' => 'text/calendar');
                        $modified = $this->modified($task->uid, $parts[1]);
                        if (!empty($modified)) {
                            $result['mtime'] = $modified;
                        }
                        return $result;
                    } elseif (count($parts) == 2 &&
                        substr($parts[1], -4) == '.ics' &&
                        array_key_exists(substr($parts[1], 0, -4), Nag::listTasklists(false, Horde_Perms::READ))) {
                        } else {
                            //
                            // All other requests are a 404: Not Found
                            //
                            return false;
                        }
        }
    }

    /**
     * Saves a file into the Nag tree.
     *
     * @param string $path          The path where to PUT the file.
     * @param string $content       The file content.
     * @param string $content_type  The file's content type.
     *
     * @return array  The event UIDs, or a PEAR_Error on failure.
     */
    public function put($path, $content, $content_type)
    {
        if (substr($path, 0, 3) == 'nag') {
            $path = substr($path, 3);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (count($parts) == 2 &&
            substr($parts[1], -4) == '.ics') {

                // Workaround for WebDAV clients that are not smart enough to send
                // the right content type.  Assume text/calendar.
                if ($content_type == 'application/octet-stream') {
                    $content_type = 'text/calendar';
                }
                $tasklist = substr($parts[1], 0, -4);
            } elseif (count($parts) == 3) {
                $tasklist = $parts[1];

                // Workaround for WebDAV clients that are not smart enough to send
                // the right content type.  Assume the same format we send individual
                // tasklist items: text/calendar
                if ($content_type == 'application/octet-stream') {
                    $content_type = 'text/calendar';
                }
            } else {
                return PEAR::raiseError(_("Invalid tasklist name supplied."), 403);
            }

        if (!array_key_exists($tasklist, Nag::listTasklists(false, Horde_Perms::EDIT))) {
            // FIXME: Should we attempt to create a tasklist based on the filename
            // in the case that the requested tasklist does not exist?
            return PEAR::raiseError(_("Tasklist does not exist or no permission to edit"), 403);
        }

        // Store all currently existings UIDs. Use this info to delete UIDs not
        // present in $content after processing.
        $ids = array();
        $uids_remove = array_flip($this->listTaskUids($tasklist));

        $storage = Nag_Driver::singleton($tasklist);

        switch ($content_type) {
        case 'text/calendar':
        case 'text/x-vcalendar':
            $iCal = new Horde_Icalendar();
            if (!($content instanceof Horde_Icalendar_Vtodo)) {
                if (!$iCal->parsevCalendar($content)) {
                    return PEAR::raiseError(_("There was an error importing the iCalendar data."), 400);
                }
            } else {
                $iCal->addComponent($content);
            }

            foreach ($iCal->getComponents() as $content) {
                if ($content instanceof Horde_Icalendar_Vtodo) {
                    $task = new Nag_Task();
                    $task->fromiCalendar($content);
                    $task->tasklist = $tasklist;
                    if (isset($task->uid) &&
                        !is_a(($existing = $storage->getByUID($task->uid)), 'PEAR_Error')) {
                        // Entry exists, remove from uids_remove list so we
                        // won't delete in the end.
                        if (isset($uids_remove[$task->uid])) {
                            unset($uids_remove[$task->uid]);
                        }
                        if ($existing->private &&
                            $existing->owner != $GLOBALS['registry']->getAuth()) {
                            continue;
                        }
                        // Check if our task is newer then the existing - get
                        // the task's history.
                        $history = $GLOBALS['injector']->getInstance('Horde_History');
                        $created = $modified = null;
                        try {
                            $log = $history->getHistory('nag:' . $tasklist . ':' . $task->uid);
                            foreach ($log as $entry) {
                                switch ($entry['action']) {
                                case 'add':
                                    $created = $entry['ts'];
                                    break;

                                case 'modify':
                                    $modified = $entry['ts'];
                                    break;
                                }
                            }
                        } catch (Exception $e) {}
                        if (empty($modified) && !empty($add)) {
                            $modified = $add;
                        }
                        if (!empty($modified) &&
                            $modified >= $content->getAttribute('LAST-MODIFIED')) {
                                // LAST-MODIFIED timestamp of existing entry
                                // is newer: don't replace it.
                                continue;
                            }

                        // Don't change creator/owner.
                        $owner = $existing->owner;
                        $taskId = $existing->id;
                        $result = $storage->modify(
                            $taskId,
                            isset($task->name) ? $task->name : $existing->name,
                            isset($task->desc) ? $task->desc : $existing->desc,
                            isset($task->start) ? $task->start : $existing->start,
                            isset($task->due) ? $task->due : $existing->due,
                            isset($task->priority) ? $task->priority : $existing->priority,
                            isset($task->estimate) ? $task->estimate : 0,
                            isset($task->completed) ? (int)$task->completed : $existing->completed,
                            isset($task->category) ? $task->category : $existing->category,
                            isset($task->alarm) ? $task->alarm : $existing->alarm,
                            isset($task->parent_id) ? $task->parent_id : $existing->parent_id,
                            isset($task->private) ? $task->private : $existing->private,
                            $owner,
                            isset($task->assignee) ? $task->assignee : $existing->assignee);

                        if (is_a($result, 'PEAR_Error')) {
                            $result->code = 500;
                            return $result;
                        }
                        $ids[] = $task->uid;
                    } else {
                        $newTask = $storage->add(
                            isset($task->name) ? $task->name : '',
                            isset($task->desc) ? $task->desc : '',
                            isset($task->start) ? $task->start : 0,
                            isset($task->due) ? $task->due : 0,
                            isset($task->priority) ? $task->priority : 3,
                            isset($task->estimate) ? $task->estimate : 0,
                            !empty($task->completed),
                            isset($task->category) ? $task->category : '',
                            isset($task->alarm) ? $task->alarm : 0,
                            isset($task->uid) ? $task->uid : null,
                            isset($task->parent_id) ? $task->parent_id : '',
                            !empty($task->private),
                            $GLOBALS['registry']->getAuth(),
                            isset($task->assignee) ? $task->assignee : null);
                        if (is_a($newTask, 'PEAR_Error')) {
                            $newtask->code = 500;
                            return $newTask;
                        }
                        // use UID rather than ID
                        $ids[] = $newTask[1];
                    }
                }
            }
            break;

        default:
                return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"), $content_type), 400);
        }

        if (array_key_exists($tasklist, Nag::listTasklists(false, Horde_Perms::DELETE))) {
            foreach (array_keys($uids_remove) as $uid) {
                $this->delete($uid);
            }
        }

        return $ids;
    }

    /**
     * Deletes a file from the Nag tree.
     *
     * @param string $path  The path to the file.
     *
     * @return mixed  The event's UID, or a PEAR_Error on failure.
     */
    public function path_delete($path)
    {
        if (substr($path, 0, 3) == 'nag') {
            $path = substr($path, 3);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (count($parts) == 2) {
            // @TODO Deny deleting of the entire tasklist for now.
            // Allow users to delete tasklists but not create them via WebDAV will
            // be more confusing than helpful.  They are, however, still able to
            // delete individual task items within the tasklist folder.
            return PEAR::raiseError(_("Deleting entire tasklists is not supported."), 403);
            // To re-enable the functionality just remove this if {} block.
        }

        if (substr($parts[1], -4) == '.ics') {
            $tasklistID = substr($parts[1], 0, -4);
        } else {
            $tasklistID = $parts[1];
        }

        if (!(count($parts) == 2 || count($parts) == 3) ||
            !array_key_exists($tasklistID, Nag::listTasklists(false, Horde_Perms::DELETE))) {
                return PEAR::raiseError(_("Tasklist does not exist or no permission to delete"), 403);
            }

        /* Create a Nag storage instance. */
        $storage = Nag_Driver::singleton($tasklistID);
        if (is_a($storage, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Connection failed: %s"), $storage->getMessage()), 500);
        }
        $retrieved = $storage->retrieve();
        if (is_a($retrieved, 'PEAR_Error')) {
            $retrieved->code = 500;
            return $retrieved;
        }

        if (count($parts) == 3) {
            // Delete just a single entry
            return $storage->delete($parts[2]);
        } else {
            // Delete the entire task list
            $result = $storage->deleteAll();
            if (is_a($result, 'PEAR_Error')) {
                return PEAR::raiseError(sprintf(_("Unable to delete tasklist \"%s\": %s"), $tasklistID, $result->getMessage()), 500);
            } else {
                // Remove share and all groups/permissions.
                $share = $GLOBALS['nag_shares']->getShare($tasklistID);
                try {
                    $GLOBALS['nag_shares']->removeShare($share);
                } catch (Horde_Share_Exception $e) {
                    throw new Nag_Exception($e->getMessage());
                }
            }
        }
    }

    /**
     * Returns an array of UIDs for all tasks that the current user is authorized
     * to see.
     *
     * @param variant $tasklist  The tasklist or an array of taskslists to list.
     *
     * @return array             An array of UIDs for all tasks
     *                           the user can access.
     */
    public function listUids($tasklist = null)
    {
        if (!isset($GLOBALS['conf']['storage']['driver'])) {
            return PEAR::raiseError(_("Not configured"));
        }

        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::READ);
        }

        if (!array_key_exists($tasklist,
            Nag::listTasklists(false, Horde_Perms::READ))) {
                return PEAR::raiseError(_("Permission Denied"));
            }

        $tasks = Nag::listTasks(null, null, null, $tasklist, 1);
        if (is_a($tasks, 'PEAR_Error')) {
            return $tasks;
        }

        $uids = array();
        $tasks->reset();
        while ($task = $tasks->each()) {
            $uids[] = $task->uid;
        }

        return $uids;
    }

    /**
     * Returns an array of UIDs for tasks that have had $action happen since
     * $timestamp.
     *
     * @param string  $action     The action to check for - add, modify, or delete.
     * @param integer $timestamp  The time to start the search.
     * @param string  $tasklist   The tasklist to be used. If 'null', the
     *                            user's default tasklist will be used.
     * @param integer $end        The optional ending timestamp.
     *
     * @return array  An array of UIDs matching the action and time criteria.
     *
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function listBy($action, $timestamp, $tasklist = null, $end = null)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::READ);
        }

        if (!array_key_exists($tasklist,
            Nag::listTasklists(false, Horde_Perms::READ))) {
                return PEAR::raiseError(_("Permission Denied"));
        }

        $filter = array(array('op' => '=', 'field' => 'action', 'value' => $action));
        if (!empty($end)) {
            $filter[] = array('op' => '<', 'field' => 'ts', 'value' => $end);
        }
        $histories = $GLOBALS['injector']->getInstance('Horde_History')->getByTimestamp('>', $timestamp, $filter, 'nag:' . $tasklist);

        // Strip leading nag:username:.
        return preg_replace('/^([^:]*:){2}/', '', array_keys($histories));
    }

    /**
     * Returns the timestamp of an operation for a given uid an action.
     *
     * @param string $uid      The uid to look for.
     * @param string $action   The action to check for - add, modify, or delete.
     * @param string $tasklist The tasklist to be used. If 'null', the
     *                         user's default tasklist will be used.
     *
     * @return integer  The timestamp for this action.
     *
     * @throws InvalidArgumentException
     */
    public function getActionTimestamp($uid, $action, $tasklist = null)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::READ);
        }

        if (!array_key_exists($tasklist,
            Nag::listTasklists(false, Horde_Perms::READ))) {
                return PEAR::raiseError(_("Permission Denied"));
            }

        return $GLOBALS['injector']->getInstance('Horde_History')->getActionTimestamp('nag:' . $tasklist . ':' . $uid, $action);
    }

    /**
     * Imports one or more tasks represented in the specified content type.
     *
     * If a UID is present in the content and the task is already in the
     * database, a replace is performed rather than an add.
     *
     * @param string $content      The content of the task.
     * @param string $contentType  What format is the data in? Currently supports:
     *                             text/calendar
     *                             text/x-vcalendar
     * @param string $tasklist     The tasklist into which the task will be
     *                             imported.  If 'null', the user's default
     *                             tasklist will be used.
     *
     * @return string  The new UID on one import, an array of UIDs on multiple imports,
     *                 or PEAR_Error on failure.
     */
    public function import($content, $contentType, $tasklist = null)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::EDIT);
        }

        if (!array_key_exists($tasklist, Nag::listTasklists(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        /* Create a Nag_Driver instance. */
        $storage = Nag_Driver::singleton($tasklist);

        switch ($contentType) {
        case 'text/x-vcalendar':
        case 'text/calendar':
        case 'text/x-vtodo':
            $iCal = new Horde_Icalendar();
            if (!($content instanceof Horde_Icalendar_Vtodo)) {
                if (!$iCal->parsevCalendar($content)) {
                    return PEAR::raiseError(_("There was an error importing the iCalendar data."));
                }
            } else {
                $iCal->addComponent($content);
            }

            $components = $iCal->getComponents();
            if (count($components) == 0) {
                return PEAR::raiseError(_("No iCalendar data was found."));
            }

            $ids = array();
            foreach ($components as $content) {
                if ($content instanceof Horde_iCalendar_vtodo) {
                    $task = new Nag_Task();
                    $task->fromiCalendar($content);
                    if (isset($task->uid) &&
                        !is_a(($existing = $storage->getByUID($task->uid)), 'PEAR_Error')) {
                            $taskId = $existing->id;
                            $result = $storage->modify(
                                $taskId,
                                isset($task->name) ? $task->name : $existing->name,
                                isset($task->desc) ? $task->desc : $existing->desc,
                                isset($task->start) ? $task->start : $existing->start,
                                isset($task->due) ? $task->due : $existing->due,
                                isset($task->priority) ? $task->priority : $existing->priority,
                                isset($task->estimate) ? $task->estimate : 0,
                                isset($task->completed) ? (int)$task->completed : $existing->completed,
                                isset($task->category) ? $task->category : $existing->category,
                                isset($task->alarm) ? $task->alarm : $existing->alarm,
                                isset($task->parent_id) ? $task->parent_id : $existing->parent_id,
                                isset($task->private) ? $task->private : $existing->private,
                                isset($task->owner) ? $task->owner : $existing->owner,
                                isset($task->assignee) ? $task->assignee : $existing->assignee);

                            if (is_a($result, 'PEAR_Error')) {
                                return $result;
                            }
                            $ids[] = $task->uid;
                        } else {
                            $newTask = $storage->add(
                                isset($task->name) ? $task->name : '',
                                isset($task->desc) ? $task->desc : '',
                                isset($task->start) ? $task->start : 0,
                                isset($task->due) ? $task->due : 0,
                                isset($task->priority) ? $task->priority : 3,
                                isset($task->estimate) ? $task->estimate : 0,
                                !empty($task->completed),
                                isset($task->category) ? $task->category : '',
                                isset($task->alarm) ? $task->alarm : 0,
                                isset($task->methods) ? $task->methods : null,
                                isset($task->uid) ? $task->uid : null,
                                isset($task->parent_id) ? $task->parent_id : '',
                                !empty($task->private),
                                $GLOBALS['registry']->getAuth(),
                                isset($task->assignee) ? $task->assignee : null);
                            if (is_a($newTask, 'PEAR_Error')) {
                                return $newTask;
                            }
                            // use UID rather than ID
                            $ids[] = $newTask[1];
                        }
                }
            }
            if (count($ids) == 0) {
                return PEAR::raiseError(_("No iCalendar data was found."));
            } else if (count($ids) == 1) {
                return $ids[0];
            }
            return $ids;

        case 'activesync':
            $task = new Nag_Task();
            $task->fromASTask($content);
            $results = $storage->add(
                        isset($task->name) ? $task->name : '',
                        isset($task->desc) ? $task->desc : '',
                        isset($task->start) ? $task->start : 0,
                        isset($task->due) ? $task->due : 0,
                        isset($task->priority) ? $task->priority : 3,
                        isset($task->estimate) ? $task->estimate : 0,
                        !empty($task->completed),
                        isset($task->category) ? $task->category : '',
                        isset($task->alarm) ? $task->alarm : 0,
                        isset($task->methods) ? $task->methods : null,
                        isset($task->uid) ? $task->uid : null,
                        isset($task->parent_id) ? $task->parent_id : '',
                        !empty($task->private),
                        $GLOBALS['registry']->getAuth(),
                        isset($task->assignee) ? $task->assignee : null);

            /* array index 0 is id, 1 is uid */
            return $results[1];
        }

        return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"), $contentType));
    }

    /**
     * Adds a task.
     *
     * @param array $task  A hash with overwriting task information.
     */
    public function addTask($task)
    {
        if (!$GLOBALS['registry']->isAdmin() &&
            !array_key_exists($task['tasklist'],
                              Nag::listTasklists(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $storage = Nag_Driver::singleton($task['tasklist']);

        return $storage->add(
            isset($task['name']) ? $task['name'] : '',
            isset($task['desc']) ? $task['desc'] : '',
            isset($task['start']) ? $task['start'] : 0,
            isset($task['due']) ? $task['due'] : 0,
            isset($task['priority']) ? $task['priority'] : 3,
            isset($task['estimate']) ? $task['estimate'] : 0,
            !empty($task['completed']),
            isset($task['category']) ? $task['category'] : '',
            isset($task['alarm']) ? $task['alarm'] : 0,
            isset($task['methods']) ? $task['alarm'] : null,
            isset($task['uid']) ? $task['uid'] : null,
            isset($task['parent_id']) ? $task['parent_id'] : '',
            !empty($task['private']),
            $GLOBALS['registry']->getAuth(),
            isset($task['assignee']) ? $task['assignee'] : null);
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
    public function quickAdd($text, $tasklist = null)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::EDIT);
        }
        if (!array_key_exists($tasklist, Nag::listTasklists(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        return Nag::createTasksFromText($text, $tasklist);
    }

    /**
     * Toggles the task completion flag.
     *
     * @param string $task_id      The task ID.
     * @param string $tasklist_id  The tasklist that contains the task.
     */
    public function toggleCompletion($task_id, $tasklist_id)
    {
        if (!array_key_exists($tasklist_id,
            Nag::listTasklists(false, Horde_Perms::EDIT))) {
                return PEAR::raiseError(_("Permission Denied"));
            }

        try {
            $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            throw new Nag_Exception($e);
        }
        $task = Nag::getTask($tasklist_id, $task_id);
        if ($task instanceof PEAR_Error) {
            return $task;
        }

        $task->completed = !$task->completed;
        if ($task->completed) {
            $task->completed_date = time();
        } else {
            $task->completed_date = null;
        }

        return $task->save();
    }

    /**
     * Exports a task, identified by UID, in the requested content type.
     *
     * @param string $uid          Identify the task to export.
     * @param string $contentType  What format should the data be in?
     *                             A string with one of:
     * <pre>
     * text/calendar    - (VCALENDAR 2.0. Recommended as this is specified in
     *                    rfc2445)
     * text/x-vcalendar - (old VCALENDAR 1.0 format. Still in wide use)
     * </pre>
     *
     * @return string  The requested data.
     */
    public function export($uid, $contentType)
    {
        $storage = Nag_Driver::singleton();
        $task = $storage->getByUID($uid);
        if (is_a($task, 'PEAR_Error')) {
            return $task;
        }

        if (!array_key_exists($task->tasklist,
                              Nag::listTasklists(false, Horde_Perms::READ))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $version = '2.0';
        switch ($contentType) {
        case 'text/x-vcalendar':
            $version = '1.0';
        case 'text/calendar':
            // Create the new iCalendar container.
            $iCal = new Horde_Icalendar($version);
            $iCal->setAttribute('PRODID', '-//The Horde Project//Nag ' . $GLOBALS['registry']->getVersion() . '//EN');
            $iCal->setAttribute('METHOD', 'PUBLISH');

            // Create new vTodo object.
            $vTodo = $task->toiCalendar($iCal);
            $vTodo->setAttribute('VERSION', $version);

            $iCal->addComponent($vTodo);

            return $iCal->exportvCalendar();
        case 'activesync':
            return $task->toASTask();
        default:
            return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"), $contentType));
        }
    }

    /**
     * Returns a task object.
     *
     * @param string $tasklist  A tasklist id.
     * @param string $id        A task id.
     *
     * @return Nag_Task  The matching task object.
     */
    public function getTask($tasklist, $id)
    {
        if (!array_key_exists($tasklist,
                              Nag::listTasklists(false, Horde_Perms::READ))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $storage = Nag_Driver::singleton($tasklist);
        return $storage->get($id);
    }

    /**
     * Exports a tasklist in the requested content type.
     *
     * @param string $tasklist     The tasklist to export.
     * @param string $contentType  What format should the data be in?
     *                             A string with one of:
     *                             <pre>
     *                             text/calendar (VCALENDAR 2.0. Recommended as
     *                                            this is specified in rfc2445)
     *                             text/x-vcalendar (old VCALENDAR 1.0 format.
     *                                              Still in wide use)
     *                             </pre>
     *
     * @return string  The iCalendar representation of the tasklist.
     */
    public function exportTasklist($tasklist, $contentType)
    {
        if (!array_key_exists($tasklist,
            Nag::listTasklists(false, Horde_Perms::READ))) {
                return PEAR::raiseError(_("Permission Denied"));
            }

        $tasks = Nag::listTasks(null, null, null, array($tasklist), 1);

        $version = '2.0';
        switch ($contentType) {
        case 'text/x-vcalendar':
            $version = '1.0';
        case 'text/calendar':
            $share = $GLOBALS['nag_shares']->getShare($tasklist);

            $iCal = new Horde_Icalendar($version);
            $iCal->setAttribute('X-WR-CALNAME', $share->get('name'));

            $tasks->reset();
            while ($task = $tasks->each()) {
                $iCal->addComponent($task->toiCalendar($iCal));
            }

            return $iCal->exportvCalendar();
        }

        return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"), $contentType));

    }

    /**
     * Deletes a task identified by UID.
     *
     * @param string|array $uid  Identify the task to delete, either a single UID
     *                           or an array.
     *
     * @return boolean  Success or failure.
     */
    public function delete($uid)
    {
        // Handle an arrray of UIDs for convenience of deleting multiple tasks at
        // once.
        if (is_array($uid)) {
            foreach ($uid as $g) {
                $result = $this->delete($g);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }

            return true;
        }

        $storage = Nag_Driver::singleton();
        $task = $storage->getByUID($uid);
        if (is_a($task, 'PEAR_Error')) {
            return $task;
        }

        if (!$GLOBALS['registry']->isAdmin() &&
            !array_key_exists($task->tasklist,
                              Nag::listTasklists(false, Horde_Perms::DELETE))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        return $storage->delete($task->id);
    }

    /**
     * Deletes a task identified by tasklist and ID.
     *
     * @param string $tasklist  A tasklist id.
     * @param string $id        A task id.
     */
    public function deleteTask($tasklist, $id)
    {
        if (!$GLOBALS['registry']->isAdmin() &&
            !array_key_exists($tasklist,
                              Nag::listTasklists(false, Horde_Perms::DELETE))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $storage = Nag_Driver::singleton($tasklist);
        return $storage->delete($id);
    }

    /**
     * Replaces the task identified by UID with the content represented in the
     * specified content type.
     *
     * If you want to replace multiple tasks with the UID specified in the
     * VCALENDAR data, you may use $this->import instead. This automatically does a
     * replace if existings UIDs are found.
     *
     *
     * @param string $uid          Identify the task to replace.
     * @param string $content      The content of the task.
     * @param string $contentType  What format is the data in? Currently supports:
     *                             - text/x-vcalendar
     *                             - text/calendar
     *
     * @return boolean  Success or failure.
     */
    public function replace($uid, $content, $contentType)
    {
        $storage = Nag_Driver::singleton();
        $existing = $storage->getByUID($uid);
        if (is_a($existing, 'PEAR_Error')) {
            return $existing;
        }
        $taskId = $existing->id;

        if (!array_key_exists($existing->tasklist, Nag::listTasklists(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        switch ($contentType) {
        case 'text/calendar':
        case 'text/x-vcalendar':
            if (!($content instanceof Horde_Icalendar_Vtodo)) {
                $iCal = new Horde_Icalendar();
                if (!$iCal->parsevCalendar($content)) {
                    return PEAR::raiseError(_("There was an error importing the iCalendar data."));
                }

                $components = $iCal->getComponents();
                $component = null;
                foreach ($components as $content) {
                    if ($content instanceof Horde_Icalendar_Vtodo) {
                        if ($component !== null) {
                            return PEAR::raiseError(_("Multiple iCalendar components found; only one vTodo is supported."));
                        }
                        $component = $content;
                    }

                }
                if ($component === null) {
                    return PEAR::raiseError(_("No iCalendar data was found."));
                }
            }

            $task = new Nag_Task();
            $task->fromiCalendar($content);
            $result = $storage->modify(
                $taskId,
                isset($task->name) ? $task->name : $existing->name,
                isset($task->desc) ? $task->desc : $existing->desc,
                isset($task->start) ? $task->start : $existing->start,
                isset($task->due) ? $task->due : $existing->due,
                isset($task->priority) ? $task->priority : $existing->priority,
                isset($task->estimate) ? $task->estimate : 0,
                isset($task->completed) ? (int)$task->completed : $existing->completed,
                isset($task->category) ? $task->category : $existing->category,
                isset($task->alarm) ? $task->alarm : $existing->alarm,
                isset($task->parent_id) ? $task->parent_id : $existing->parent_id,
                isset($task->private) ? $task->private : $existing->private,
                isset($task->owner) ? $task->owner : $existing->owner,
                isset($task->assignee) ? $task->assignee : $existing->assignee);

            break;

        case 'activesync':
            $task = new Nag_Task();
            $task->fromASTask($content);
            $result = $storage->modify(
                $taskId,
                isset($task->name) ? $task->name : $existing->name,
                isset($task->desc) ? $task->desc : $existing->desc,
                isset($task->start) ? $task->start : $existing->start,
                isset($task->due) ? $task->due : $existing->due,
                isset($task->priority) ? $task->priority : $existing->priority,
                isset($task->estimate) ? $task->estimate : 0,
                isset($task->completed) ? (int)$task->completed : $existing->completed,
                isset($task->category) ? $task->category : $existing->category,
                isset($task->alarm) ? $task->alarm : $existing->alarm,
                isset($task->parent_id) ? $task->parent_id : $existing->parent_id,
                isset($task->private) ? $task->private : $existing->private,
                isset($task->owner) ? $task->owner : $existing->owner,
                isset($task->assignee) ? $task->assignee : $existing->assignee);
            break;
        default:
            return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"), $contentType));
        }

        return $result;
    }

    /**
     * Changes a task identified by tasklist and ID.
     *
     * @param string $tasklist  A tasklist id.
     * @param string $id        A task id.
     * @param array $task       A hash with overwriting task information.
     */
    public function updateTask($tasklist, $id, $task)
    {
        if (!$GLOBALS['registry']->isAdmin() &&
            !array_key_exists($tasklist,
                              Nag::listTasklists(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $storage = Nag_Driver::singleton($tasklist);
        $existing = $storage->get($id);
        if (is_a($existing, 'PEAR_Error')) {
            return $existing;
        }

        return $storage->modify(
            $id,
            isset($task['name']) ? $task['name'] : $existing->name,
            isset($task['desc']) ? $task['desc'] : $existing->desc,
            isset($task['start']) ? $task['start'] : $existing->start,
            isset($task['due']) ? $task['due'] : $existing->due,
            isset($task['priority']) ? $task['priority'] : $existing->priority,
            isset($task['estimate']) ? $task['estimate'] : $existing->estimate,
            isset($task['completed']) ? (int)$task['completed'] : $existing->completed,
            isset($task['category']) ? $task['category'] : $existing->category,
            isset($task['alarm']) ? $task['alarm'] : $existing->alarm,
            isset($task['methods']) ? $task['methods'] : $existing->methods,
            isset($task['parent_id']) ? $task['parent_id'] : $existing->parent_id,
            isset($task['private']) ? $task['private'] : $existing->private,
            isset($task['owner']) ? $task['owner'] : $existing->owner,
            isset($task['assignee']) ? $task['assignee'] : $existing->assignee,
            $task['completed'] && !$existing->completed ? date() : $existing->completed_date,
            isset($task['tasklist']) ? $task['tasklist'] : $existing->tasklist);
    }

    /**
     * Lists active tasks as cost objects.
     *
     * @todo Implement $criteria parameter.
     *
     * @param array $criteria   Filter attributes
     */
    public function listCostObjects($criteria)
    {
        $tasks = Nag::listTasks(null, null, null, null, 1);
        $result = array();
        $tasks->reset();
        while ($task = $tasks->each()) {
            $result[$task->id] = array('id' => $task->id,
                'active' => !$task->completed,
                'name' => $task->name);
            if (!empty($task->estimate)) {
                $result[$task->id]['estimate'] = $task->estimate;
            }
        }

        if (count($result) == 0) {
            return array();
        } else {
            return array(array('category' => _("Tasks"),
                'objects'  => array_values($result)));
        }
    }

    public function listTimeObjectCategories()
    {
        $categories = array();
        $tasklists = Nag::listTasklists(false, Horde_Perms::SHOW | Horde_Perms::READ);
        foreach ($tasklists as $tasklistId => $tasklist) {
            $categories[$tasklistId] = $tasklist->get('name');
        }
        return $categories;
    }

    /**
     * Lists active tasks as time objects.
     *
     * @param array $categories  The time categories (from
     *                           listTimeObjectCategories) to list.
     * @param mixed $start       The start date of the period.
     * @param mixed $end         The end date of the period.
     */
    public function listTimeObjects($categories, $start, $end)
    {
        $allowed_tasklists = Nag::listTasklists(false, Horde_Perms::READ);
        foreach ($categories as $tasklist) {
            if (!array_key_exists($tasklist, $allowed_tasklists)) {
                return PEAR::raiseError(_("Permission Denied"));
            }
        }

        $timeobjects = array();
        $start = new Horde_Date($start);
        $start_ts = $start->timestamp();
        $end = new Horde_Date($end);
        $end_ts = $end->timestamp();

        // List incomplete tasks.
        $tasks = Nag::listTasks(null, null, null, $categories, 0);
        $tasks->reset();
        while ($task = $tasks->each()) {
            // If there's no due date, it's not a time object.
            if (!$task->due || $task->due + 1 < $start_ts || $task->due > $end_ts) {
                continue;
            }
            $due_date = date('Y-m-d\TH:i:s', $task->due);
            $timeobjects[$task->id] = array(
                'id' => $task->id,
                'title' => $task->name,
                'description' => $task->desc,
                'start' => $due_date,
                'end' => $due_date,
                'category' => $task->category,
                'color' => $allowed_tasklists[$task->tasklist]->get('color'),
                'owner' => $allowed_tasklists[$task->tasklist]->get('owner'),
                'permissions' => $GLOBALS['nag_shares']->getPermissions($task->tasklist, $GLOBALS['registry']->getAuth()),
                'variable_length' => false,
                'params' => array('task' => $task->id,
                                  'tasklist' => $task->tasklist),
                'link' => Horde::url('view.php', true)->add(array('tasklist' => $task->tasklist, 'task' => $task->id)),
                'edit_link' => Horde::url('task.php', true)->add(array('tasklist' => $task->tasklist, 'task' => $task->id, 'actionID' => 'modify_task')),
                'delete_link' => Horde::url('task.php', true)->add(array('tasklist' => $task->tasklist, 'task' => $task->id, 'actionID' => 'delete_task')),
                'ajax_link' => 'task:' . $task->tasklist . ':' . $task->id);
        }

        return $timeobjects;
    }

    /**
     * Saves properties of a time object back to the task that it represents.
     *
     * At the moment only the title, description and due date are saved.
     *
     * @param array $timeobject  A time object hash.
     */
    public function saveTimeObject($timeobject)
    {
        $storage = Nag_Driver::singleton();
        $existing = $storage->get($timeobject['id']);
        if (is_a($existing, 'PEAR_Error')) {
            return $existing;
        }
        if (!array_key_exists($existing->tasklist,
                              Nag::listTasklists(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }
        $storage = Nag_Driver::singleton($existing->tasklist);

        if (isset($timeobject['start'])) {
            $due = new Horde_Date($timeobject['start']);
            $due = $due->timestamp();
        } else {
            $due = $existing->due;
        }

        return $storage->modify(
            $timeobject['id'],
            isset($timeobject['title']) ? $timeobject['title'] : $existing->name,
            isset($timeobject['description']) ? $timeobject['description'] : $existing->desc,
            $existing->start,
            $due,
            $existing->priority,
            $existing->estimate,
            $existing->completed,
            $existing->category,
            $existing->alarm,
            $existing->methods,
            $existing->parent_id,
            $existing->private,
            $existing->owner,
            $existing->assignee,
            $existing->completed_date,
            $existing->tasklist);
    }

    /**
     * Lists alarms for a given moment.
     *
     * @param integer $time  The time to retrieve alarms for.
     * @param string $user   The user to retreive alarms for. All users if null.
     *
     * @return array  An array of UIDs
     */
    public function listAlarms($time, $user = null)
    {
        if ((empty($user) || $user != $GLOBALS['registry']->getAuth()) &&
            !$GLOBALS['registry']->isAdmin()) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $storage = Nag_Driver::singleton();
        $group = $GLOBALS['injector']->getInstance('Horde_Group');
        $alarm_list = array();
        $tasklists = is_null($user) ? array_keys($GLOBALS['nag_shares']->listAllShares()) :  $GLOBALS['display_tasklists'];

        $alarms = Nag::listAlarms($time, $tasklists);
        if ($alarms instanceof PEAR_Error) {
            return $alarms;
        }

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

}
