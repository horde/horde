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
    protected $_links = array(
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
            'URI_TASKLIST_EXPORT' => str_replace(
                array('%23', '%2523', '%7B', '%257B', '%7D', '%257D'),
                array('#', '#', '{', '{', '}', '}'),
                strval($GLOBALS['registry']->downloadUrl('#{tasklist}.ics', array('actionID' => 'export', 'exportTasks' => 1, 'exportID' => Horde_Data::EXPORT_ICALENDAR, 'exportList' => '#{tasklist}'))->setRaw(true))),
            'default_tasklist' => Nag::getDefaultTasklist(Horde_Perms::EDIT),
            'default_due' => (bool)$GLOBALS['prefs']->getValue('default_due'),
            'default_due_days' => (int)$GLOBALS['prefs']->getValue('default_due_days'),
            'default_due_time' => $GLOBALS['prefs']->getValue('default_due_time'),
            'prefs_url' => strval($GLOBALS['registry']->getServiceLink('prefs', 'nag')->setRaw(true)),
        );
    }

    /**
     * Retrieves the current user's task list from storage.
     *
     * This function will also sort the resulting list, if requested.
     * @param arary $options  Options array:
     *   - altsortby: (string) The secondary sort field. Same values as sortdir.
     *                DEFAULT: altsortby pref is used.
     *   - completed: (integer) Which task to retrieve.
     *                DEFAULT: show_completed pref is used.
     *   - sortby: (string)  A Nag::SORT_* constant for the field to sort by.
     *             DEFAULT: sortby pref is used.
     *   - sortdir: (string) Direction of sort. NAG::SORT_ASCEND or NAG::SORT_DESCEND.
     *              DEFAULT: sortdir pref is used.
     *   - include_tags: (boolean) Autoload all tags.
     *                   DEFAULT: false (Tags are lazy loaded as needed.)
     *   - json: (boolean) Return data as JSON.
     *           DEFAULT: false (Data is returned as Nag_Task)
     *   - tasklists: (array) An array of tasklists to include.
     *                DEFAULT: Use $GLOBALS['display_tasklists'];
     *
     * @return array  An array of the requested tasks.
     */
    public function listTasks(array $options = array())
    {
        global $prefs;

        $completedArray = array(
            'incomplete' => Nag::VIEW_INCOMPLETE,
            'all' => Nag::VIEW_ALL,
            'complete' => Nag::VIEW_COMPLETE,
            'future' => Nag::VIEW_FUTURE,
            'future_incomplete' => Nag::VIEW_FUTURE_INCOMPLETE);

        // Prevent null tasklists value from obscuring the default value.
        if (array_key_exists('tasklists', $options) && empty($options['tasklists'])) {
            unset($options['tasklists']);
        }
        if (is_null($options['completed']) || !isset($completedArray[$options['completed']])) {
            $options['completed'] = $prefs->getValue('show_completed');
        } else {
            $options['completed'] = $completedArray[$options['completed']];
        }
        $options = array_merge(
            array(
                'sortby' => $prefs->getValue('sortby'),
                'sortdir' => $prefs->getValue('sortdir'),
                'altsortby' => $prefs->getValue('altsortby'),
                'tasklists' => $GLOBALS['display_tasklists'],
                'include_tags' => false,
                'external' => false,
                'json' => false
            ),
            $options
        );

        $tasks = Nag::listTasks($options);
        $tasks->reset();
        $list = array();
        while ($task = $tasks->each()) {
            $list[$task->id] = $options['json'] ? $task->toJson() : $task->toHash();
        }

        return $list;
    }

    /**
     * Returns a list of task lists.
     *
     * @param boolean $owneronly   Only return tasklists that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter tasklists by.
     * @param boolean $smart       Include smart tasklists in results.
     *
     * @return array  The task lists.
     */
    public function listTasklists($owneronly = false, $permission = Horde_Perms::SHOW, $smart = true)
    {
        return Nag::listTasklists($owneronly, $permission, $smart);
    }

    /**
     * Returns a task list.
     *
     * @param string $name   A task list name.
     *
     * @return Horde_Share_Object  The task list.
     */
    public function getTasklist($name)
    {
        try {
            $tasklist = $GLOBALS['nag_shares']->getShare($name);
        } catch (Horde_Share_Exception $e) {
            Horde::log($e->getMessage(), 'ERR');
            throw new Nag_Exception($e);
        }
        if (!$tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied(_("You are not allowed to retrieve this task list."));
        }
        return $tasklist;
    }

    /**
     * Adds a new task list.
     *
     * @param string $name        Task list name.
     * @param string $description Task list description.
     * @param string $color       Task list color.
     * @param array  $params      Any addtional parameters needed. @since 4.2.1
     *     - synchronize:   (boolean) If true, add task list to the list of
     *                                task lists to syncronize.
     *                      DEFAULT: false (do not add to the list).
     *
     * @return string  The new tasklist's id.
     */
    public function addTasklist($name, $description = '', $color = '', array $params = array())
    {
        $tasklist = Nag::addTasklist(array('name' => $name, 'description' => $description, 'color' => $color));

        $name = $tasklist->getName();
        if (!empty($params['synchronize'])) {
            $sync = @unserialize($GLOBALS['prefs']->getValue('sync_lists'));
            $sync[] = $name;
            $GLOBALS['prefs']->setValue('sync_lists', serialize($sync));
        }

        return $name;
    }

    /**
     * Updates an existing task list.
     *
     * @param string $name   A task list name.
     * @param array $info  Hash with task list information.
     */
    public static function updateTasklist($name, $info)
    {
        try {
            $tasklist = $GLOBALS['nag_shares']->getShare($name);
        } catch (Horde_Share_Exception $e) {
            Horde::log($e->getMessage(), 'ERR');
            throw new Nag_Exception($e);
        }

        return Nag::updateTasklist($tasklist, $info);
    }

    /**
     * Updates an attendee's response status for a specified task assignment.
     *
     * @param Horde_Icalendar_Vtodo $response  A Horde_Icalendar_Vtodo
     *                                          object, with a valid UID
     *                                          attribute that points to an
     *                                          existing task.  This is
     *                                          typically the vTodo portion
     *                                          of an iTip task-request
     *                                          response, with the attendee's
     *                                          response in an ATTENDEE
     *                                          parameter.
     * @param string $sender                    The email address of the
     *                                          person initiating the
     *                                          update. Attendees are only
     *                                          updated if this address
     *                                          matches.
     *
     * @throws Nag_Exception, Horde_Exception_PermissionDenied
     */
    public function updateAttendee($response, $sender = null)
    {
        try {
            $uid = $response->getAttribute('UID');
        } catch (Horde_Icalendar_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $task = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create('')->getByUID($uid);
        $taskId = $task->id;
        $owner = $task->owner;
        if (!Nag::hasPermission($task->tasklist, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }

        try {
            $atnames = $response->getAttribute('ATTENDEE');
        } catch (Horde_Icalendar_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }
        if (!is_array($atnames)) {
            $atnames = array($atnames);
        }

        $atparms = $response->getAttribute('ATTENDEE', true);
        $found = false;
        $error = _("No attendees have been updated because none of the provided email addresses have been found in the event's attendees list.");

        foreach ($atnames as $index => $attendee) {
            if ($response->getAttribute('VERSION') < 2) {
                $addr_ob = new Horde_Mail_Rfc822_Address($attendee);
                if (!$addr_ob->valid) {
                    continue;
                }

                $attendee = $addr_ob->bare_address;
                $name = $addr_ob->personal;
            } else {
                $attendee = str_ireplace('mailto:', '', $attendee);
                $name = isset($atparms[$index]['CN']) ? $atparms[$index]['CN'] : null;
            }
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($task->assignee);
            $all_addrs = $identity->getAll('from_addr');
            if (in_array($attendee, $all_addrs)) {
                if (is_null($sender) || $sender == $attendee) {
                    $task->status = Nag::responseFromICal($atparms[$index]['PARTSTAT']);
                    $found = true;
                    break;
                } else {
                    $error = _("The attendee hasn't been updated because the update was not sent from the attendee.");
                }
            }
        }
        $task->save();

        if (!$found) {
            throw new Nag_Exception($error);
        }
    }

    /**
     * Deletes a task list.
     *
     * @param string $id  A task list id.
     */
    public function deleteTasklist($id)
    {
        $tasklist = $GLOBALS['nag_shares']->getShare($id);
        return Nag::deleteTasklist($tasklist);
    }

    /**
     * Returns the displayed task lists.
     *
     * @return array  Displayed tasklists.
     */
    public function getDisplayedTasklists()
    {
        return $GLOBALS['display_tasklists'];
    }

    /**
     * Sets the displayed task lists.
     *
     * @param array $list  Displayed tasklists.
     */
    public function setDisplayedTasklists($list)
    {
        $GLOBALS['display_tasklists'] = $list;
        $GLOBALS['prefs']->setValue('display_tasklists', serialize($list));
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
        global $injector, $nag_shares, $registry;

        // Default properties.
        if (!$properties) {
            $properties = array('name', 'icon', 'browseable');
        }

        if (substr($path, 0, 3) == 'nag') {
            $path = substr($path, 3);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $currentUser = $registry->getAuth();

        if (empty($path)) {
            // This request is for a list of all users who have tasklists
            // visible to the requesting user.
            $tasklists = Nag::listTasklists(false, Horde_Perms::READ);
            $owners = array();
            foreach ($tasklists as $tasklist) {
                $owners[$tasklist->get('owner') ? $registry->convertUsername($tasklist->get('owner'), false) : '-system-'] = $tasklist->get('owner') ?: '-system-';
            }

            $results = array();
            foreach ($owners as $externalOwner => $internalOwner) {
                if (in_array('name', $properties)) {
                    $results['nag/' . $externalOwner]['name'] = $injector
                        ->getInstance('Horde_Core_Factory_Identity')
                        ->create($internalOwner)
                        ->getName();
                }
                if (in_array('icon', $properties)) {
                    $results['nag/' . $externalOwner]['icon'] = Horde_Themes::img('user.png');
                }
                if (in_array('browseable', $properties)) {
                    $results['nag/' . $externalOwner]['browseable'] = true;
                }
                if (in_array('read-only', $properties)) {
                    $results['nag/' . $externalOwner]['read-only'] = true;
                }
            }
            return $results;

        } elseif (count($parts) == 1) {
            // This request is for all tasklists owned by the requested user
            $owner = $parts[0] == '-system-' ? '' : $registry->convertUsername($parts[0], true);
            $tasklists = $nag_shares->listShares(
                $currentUser,
                array('perm' => Horde_Perms::SHOW,
                      'attributes' => $owner));

            $results = array();
            foreach ($tasklists as $tasklistId => $tasklist) {
                if ($parts[0] == '-system-' && $tasklist->get('owner')) {
                    continue;
                }
                $retpath = 'nag/' . $parts[0] . '/' . $tasklistId;
                if (in_array('name', $properties)) {
                    $results[$retpath]['name'] = sprintf(_("Tasks from %s"), Nag::getLabel($tasklist));
                    $results[$retpath . '.ics']['name'] = Nag::getLabel($tasklist);
                }
                if (in_array('displayname', $properties)) {
                    $results[$retpath]['displayname'] = Nag::getLabel($tasklist);
                    $results[$retpath . '.ics']['displayname'] = Nag::getLabel($tasklist) . '.ics';
                }
                if (in_array('owner', $properties)) {
                    $results[$retpath]['owner'] = $results[$retpath . '.ics']['owner'] = $tasklist->get('owner') ? $registry->convertUsername($tasklist->get('owner'), false) : '-system-';
                }
                if (in_array('icon', $properties)) {
                    $results[$retpath]['icon'] = Horde_Themes::img('nag.png');
                    $results[$retpath . '.ics']['icon'] = Horde_Themes::img('mime/icalendar.png');
                }
                if (in_array('browseable', $properties)) {
                    $results[$retpath]['browseable'] = $tasklist->hasPermission($currentUser, Horde_Perms::READ);
                    $results[$retpath . '.ics']['browseable'] = false;
                }
                if (in_array('read-only', $properties)) {
                    $results[$retpath]['read-only'] = $results[$retpath . '.ics']['read-only'] = !$tasklist->hasPermission($currentUser, Horde_Perms::EDIT);
                }
                if (in_array('contenttype', $properties)) {
                    $results[$retpath . '.ics']['contenttype'] = 'text/calendar';
                }
            }
            return $results;

        } elseif (count($parts) == 2 && substr($parts[1], -4) == '.ics') {
            //
            // This is a request for the entire tasklist in iCalendar format.
            //
            $tasklist = substr($parts[1], 0, -4);
            if (!Nag::hasPermission($tasklist, Horde_Perms::READ)) {
                throw new Nag_Exception(_("Invalid task list file requested."), 404);
            }
            $ical_data = $this->exportTasklist($tasklist, 'text/calendar');
            return array(
                'data'          => $ical_data,
                'mimetype'      => 'text/calendar',
                'contentlength' => strlen($ical_data),
                'mtime'         => $_SERVER['REQUEST_TIME']
            );

        } elseif (count($parts) == 2) {
            //
            // This request is browsing into a specific tasklist.  Generate the
            // list of items and represent them as files within the directory.
            //
            try {
                $tasklist = $nag_shares->getShare($parts[1]);
            } catch (Horde_Exception_NotFound $e) {
                throw new Nag_Exception(_("Invalid task list requested."), 404);
            } catch (Horde_Share_Exception $e) {
                throw new Nag_Exception($e->getMessage, 500);
            }
            if (!$tasklist->hasPermission($currentUser, Horde_Perms::READ)) {
                throw new Nag_Exception(_("Invalid task list requested."), 404);
            }
            $storage = $injector->getInstance('Nag_Factory_Driver')->create($parts[1]);
            try {
                $storage->retrieve();
            } catch (Nag_Exception $e) {
                 throw new Nag_Exception($e->getMessage, 500);
            }
            $icon = Horde_Themes::img('nag.png');
            $owner = $tasklist->get('owner')
                ? $registry->convertUsername($tasklist->get('owner'), false)
                : '-system-';
            $results = array();
            $storage->tasks->reset();
            while ($task = $storage->tasks->each()) {
                $key = 'nag/' . $parts[0] . '/' . $parts[1] . '/' . $task->id;
                if (in_array('modified', $properties) ||
                    in_array('etag', $properties)) {
                    $modified = $this->modified($task->uid, $parts[1]);
                }
                if (in_array('name', $properties)) {
                    $results[$key]['name'] = $task->name;
                }
                if (in_array('owner', $properties)) {
                    $results[$key]['owner'] = $owner;
                }
                if (in_array('icon', $properties)) {
                    $results[$key]['icon'] = $icon;
                }
                if (in_array('browseable', $properties)) {
                    $results[$key]['browseable'] = false;
                }
                if (in_array('read-only', $properties)) {
                    $results[$key]['read-only'] = !$tasklist->hasPermission($currentUser, Horde_Perms::EDIT);
                }
                if (in_array('contenttype', $properties)) {
                    $results[$key]['contenttype'] = 'text/calendar';
                }
                if (in_array('modified', $properties)) {
                    $results[$key]['modified'] = $modified;
                }
                if (in_array('created', $properties)) {
                    $results[$key]['created'] = $this->getActionTimestamp($task->uid, 'add', $parts[1]);
                }
                if (in_array('etag', $properties)) {
                    $results[$key]['etag'] = '"' . md5($task->id . '|' . $modified) . '"';
                }
            }
            return $results;
        } else {
            //
            // The only valid request left is for either a specific task item.
            //
            if (count($parts) == 3 &&
                Nag::hasPermission($parts[1], Horde_Perms::READ)) {
                //
                // This request is for a specific item within a given task list.
                //
                /* Create a Nag storage instance. */
                $storage = $injector->getInstance('Nag_Factory_Driver')
                    ->create($parts[1]);
                $storage->retrieve();
                try {
                    $task = $storage->get($parts[2]);
                } catch (Nag_Exception $e) {
                    throw new Nag_Exception($e->getMessage(), 500);
                }
                $result = array(
                    'data' => $this->export($task->uid, 'text/calendar'),
                    'mimetype' => 'text/calendar');
                $modified = $this->modified($task->uid, $parts[1]);
                if (!empty($modified)) {
                    $result['mtime'] = $modified;
                }
                return $result;
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
     * @return array  The event UIDs
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
            // the right content type.  Assume the same format we send
            // individual tasklist items: text/calendar
            if ($content_type == 'application/octet-stream') {
                $content_type = 'text/calendar';
            }
        } else {
            throw new Nag_Exception(_("Invalid task list name supplied."), 403);
        }

        if (!Nag::hasPermission($tasklist, Horde_Perms::EDIT)) {
            // FIXME: Should we attempt to create a tasklist based on the
            // filename in the case that the requested tasklist does not exist?
            throw new Nag_Exception(_("Task list does not exist or no permission to edit"), 403);
        }

        // Store all currently existings UIDs. Use this info to delete UIDs not
        // present in $content after processing.
        $ids = array();
        if (count($parts) == 2) {
            $uids_remove = array_flip($this->listUids($tasklist));
        } else {
            $uids_remove = array();
        }

        $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($tasklist);

        switch ($content_type) {
        case 'text/calendar':
        case 'text/x-vcalendar':
            $iCal = new Horde_Icalendar();
            if (!($content instanceof Horde_Icalendar_Vtodo)) {
                if (!$iCal->parsevCalendar($content)) {
                    throw new Nag_Exception(_("There was an error importing the iCalendar data."), 400);
                }
            } else {
                $iCal->addComponent($content);
            }

            foreach ($iCal->getComponents() as $content) {
                if (!($content instanceof Horde_Icalendar_Vtodo)) {
                    continue;
                }
                $task = new Nag_Task();
                $task->fromiCalendar($content);
                $task->tasklist = $tasklist;
                $create = true;
                if (isset($task->uid)) {
                    try {
                        $existing = $storage->getByUID($task->uid);
                        $create = false;
                    } catch (Horde_Exception_NotFound $e) {
                    }
                }
                if (!$create) {
                    // Entry exists, remove from uids_remove list so we
                    // won't delete in the end.
                    unset($uids_remove[$task->uid]);
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
                    } catch (Exception $e) {
                    }
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
                    $task->owner = $existing->owner;
                    try {
                        $storage->modify($existing->id, $task->toHash());
                    } catch (Nag_Exception $e) {
                        throw new Nag_Exception($e->getMessage(), 500);
                    }
                    $ids[] = $task->uid;
                } else {
                    try {
                        $newTask = $storage->add($task->toHash());
                    } catch (Nag_Exception $e) {
                        throw new Nag_Exception($e->getMessage(), 500);
                    }
                    // use UID rather than ID
                    $ids[] = $newTask[1];
                }
            }
            break;

        default:
            throw new Nag_Exception(sprintf(_("Unsupported Content-Type: %s"), $content_type), 400);
        }

        if (Nag::hasPermission($tasklist, Horde_Perms::DELETE)) {
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
     * @return string  The event's UID
     * @throws Nag_Exception
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
            // Allow users to delete tasklists but not create them via WebDAV
            // will be more confusing than helpful.  They are, however, still
            // able to delete individual task items within the tasklist folder.
            throw Nag_Exception(_("Deleting entire task lists is not supported."), 403);
            // To re-enable the functionality just remove this if {} block.
        }

        if (substr($parts[1], -4) == '.ics') {
            $tasklistID = substr($parts[1], 0, -4);
        } else {
            $tasklistID = $parts[1];
        }

        if (!(count($parts) == 2 || count($parts) == 3) ||
            !Nag::hasPermission($tasklistID, Horde_Perms::DELETE)) {

            throw new Nag_Exception(_("Task list does not exist or no permission to delete"), 403);
        }

        /* Create a Nag storage instance. */
        try {
            $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($tasklistID);
            $storage->retrieve();
        } catch (Nag_Exception $e) {
            throw new Nag_Exception(sprintf(_("Connection failed: %s"), $e->getMessage()), 500);
        }
        if (count($parts) == 3) {
            // Delete just a single entry
            return $storage->delete($parts[2]);
        } else {
            // Delete the entire task list
            try {
                $storage->deleteAll();
            } catch (Nag_Exception $e) {
                throw new Nag_Exception(sprintf(_("Unable to delete task list \"%s\": %s"), $tasklistID, $e->getMessage()), 500);
            }

            // Remove share and all groups/permissions.
            $share = $GLOBALS['nag_shares']->getShare($tasklistID);
            try {
                $GLOBALS['nag_shares']->removeShare($share);
            } catch (Horde_Share_Exception $e) {
                throw new Nag_Exception($e->getMessage());
            }
        }
    }

    /**
     * Returns an array of UIDs for all tasks that the current user is authorized
     * to see.
     *
     * @param mixed $tasklists  The tasklist or an array of taskslists to list.
     *
     * @return array             An array of UIDs for all tasks
     *                           the user can access.
     *
     * @throws Horde_Exception_PermissionDenied
     * @throws Nag_Exception
     */
    public function listUids($tasklists = null)
    {
        if (!isset($GLOBALS['conf']['storage']['driver'])) {
            throw new Nag_Exception(_("Not configured"));
        }

        if (empty($tasklists)) {
            $tasklists = Nag::getSyncLists();
        } else {
            if (!is_array($tasklists)) {
                $tasklists = array($tasklists);
            }
            foreach ($tasklists as $list) {
                if (!Nag::hasPermission($list, Horde_Perms::READ)) {
                    throw new Horde_Exception_PermissionDenied();
                }
            }
        }

        $tasks = Nag::listTasks(array(
            'tasklists' => $tasklists,
            'completed' => Nag::VIEW_ALL,
            'include_history' => false)
        );
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
     * @param mixed   $tasklists  The tasklists to be used. If 'null', the
     *                            user's default tasklist will be used.
     * @param integer $end        The optional ending timestamp.
     * @param boolean $isModSeq   If true, $timestamp and $end are modification
     *                            sequences and not timestamps. @since 4.1.1
     *
     * @return array  An array of UIDs matching the action and time criteria.
     *
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function listBy($action, $timestamp, $tasklist = null, $end = null, $isModSeq = false)
    {
        if (empty($tasklist)) {
            $tasklist = Nag::getSyncLists();
            $results = array();
            foreach ($tasklist as $list) {
                $results = array_merge($results, $this->listBy($action, $timestamp, $list, $end, $isModSeq));
            }
            return $results;
        }

        $filter = array(array('op' => '=', 'field' => 'action', 'value' => $action));
        if (!empty($end) && !$isModSeq) {
            $filter[] = array('op' => '<', 'field' => 'ts', 'value' => $end);
        }
        if (!$isModSeq) {
            $histories = $GLOBALS['injector']
                ->getInstance('Horde_History')
                ->getByTimestamp('>', $timestamp, $filter, 'nag:' . $tasklist);
        } else {
            $histories = $GLOBALS['injector']
                ->getInstance('Horde_History')
                ->getByModSeq($timestamp, $end, $filter, 'nag:' . $tasklist);
        }

        // Strip leading nag:username:.
        return preg_replace('/^([^:]*:){2}/', '', array_keys($histories));
    }

    /**
     * Method for obtaining all server changes between two timestamps. Basically
     * a wrapper around listBy(), but returns an array containing all adds,
     * edits and deletions.
     *
     * @param integer $start             The starting timestamp
     * @param integer $end               The ending timestamp.
     * @param boolean $isModSeq          If true, $timestamp and $end are
     *                                   modification sequences and not
     *                                   timestamps. @since 4.1.1
     * @param string|array $tasklists    The sources to check. @since 4.2.0
     *
     * @return array  An hash with 'add', 'modify' and 'delete' arrays.
     */
    public function getChanges($start, $end, $isModSeq = false, $tasklists = null)
    {
        return array(
            'add' => $this->listBy('add', $start, $tasklists, $end, $isModSeq),
            'modify' => $this->listBy('modify', $start, $tasklists, $end, $isModSeq),
            'delete' => $this->listBy('delete', $start, $tasklists, $end, $isModSeq));
    }

    /**
     * Return all changes occuring between the specified modification
     * sequences.
     *
     * @param integer $start             The starting modseq.
     * @param integer $end               The ending modseq.
     * @param string|array $tasklists    The sources to check. @since 4.2.0
     *
     * @return array  The changes @see getChanges()
     * @since 4.1.1
     */
    public function getChangesByModSeq($start, $end, $tasklists = null)
    {
        return $this->getChanges($start, $end, true, $tasklists);
    }

    /**
     * Returns the timestamp of an operation for a given uid an action.
     *
     * @param string $uid      The uid to look for.
     * @param string $action   The action to check for - add, modify, or delete.
     * @param string $tasklist The tasklist to be used. If 'null', the
     *                         user's default tasklist will be used.
     * @param boolean $modSeq  Request a modification sequence instead of a
     *                         timestamp. @since 4.1.1
     *
     * @return integer  The timestamp for this action.
     *
     * @throws InvalidArgumentException
     * @throws Horde_Exception_PermissionDenied
     * @thorws Horde_History_Exception
     */
    public function getActionTimestamp($uid, $action, $tasklist = null, $modSeq = false)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::READ);
        } elseif (!Nag::hasPermission($tasklist, Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied();
        }

        if (!$modSeq) {
            return $GLOBALS['injector']
                ->getInstance('Horde_History')
                ->getActionTimestamp('nag:' . $tasklist . ':' . $uid, $action);
        } else {
            return $GLOBALS['injector']
                ->getInstance('Horde_History')
                ->getActionModSeq('nag:' . $tasklist . ':' . $uid, $action);
        }
    }

    /**
     * Return the largest modification sequence from the history backend.
     *
     * @param string $id  Limit the check to this tasklist. @since 4.2.0
     *
     * @return integer  The modseq.
     * @since 4.1.1
     */
    public function getHighestModSeq($id = null)
    {
        $parent = 'nag';
        if (!empty($id)) {
            $parent .= ':' . $id;
        }
        return $GLOBALS['injector']->getInstance('Horde_History')->getHighestModSeq($parent);
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
     */
    public function import($content, $contentType, $tasklist = null)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::EDIT);
        } elseif (!Nag::hasPermission($tasklist, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }

        /* Create a Nag_Driver instance. */
        $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($tasklist);

        switch ($contentType) {
        case 'text/x-vcalendar':
        case 'text/calendar':
        case 'text/x-vtodo':
            $iCal = new Horde_Icalendar();
            if (!($content instanceof Horde_Icalendar_Vtodo)) {
                if (!$iCal->parsevCalendar($content)) {
                    throw new Nag_Exception(_("There was an error importing the iCalendar data."));
                }
            } else {
                $iCal->addComponent($content);
            }

            $components = $iCal->getComponents();
            if (count($components) == 0) {
                throw new Nag_Exception(_("No iCalendar data was found."));
            }

            $ids = array();

            foreach ($components as $content) {
                if ($content instanceof Horde_Icalendar_Vtodo) {
                    $task = new Nag_Task($storage);
                    $task->fromiCalendar($content);
                    if (isset($task->uid)) {
                        try {
                            $existing = $storage->getByUID($task->uid, null, false);
                            $task->owner = $existing->owner;
                            $storage->modify($existing->id, $task->toHash());
                        } catch ( Horde_Exception_NotFound $e ) {
                            $hash = $task->toHash();
                            unset($hash['tasklist_id']);
                            unset($hash['task_id']);
                            unset($hash['parent']);
                            $storage->open($tasklist);
                            $storage->add($hash);
                        }
                        $ids[] = $task->uid;
                    } else {
                        $hash = $task->toHash();
                        unset($hash['uid']);
                        $newTask = $storage->add($hash);
                        // use UID rather than ID
                        $ids[] = $newTask[1];
                    }
                }
            }

            if (count($ids) == 0) {
                throw new Nag_Exception(_("No iCalendar data was found."));
            } else if (count($ids) == 1) {
                return $ids[0];
            }
            return $ids;

        case 'activesync':
            $task = new Nag_Task();
            $task->fromASTask($content);
            $hash = $task->toHash();
            unset($hash['uid']);
            $results = $storage->add($hash);

            /* array index 0 is id, 1 is uid */
            return $results[1];
        }

        throw new Nag_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
    }

    /**
     * Adds a task.
     *
     * @param array $task  A hash with task information.
     *
     * @throws Horde_Exception_PermissionDenied
     */
    public function addTask(array $task)
    {
        if (!$GLOBALS['registry']->isAdmin() &&
            !Nag::hasPermission($task['tasklist'], Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }

        $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($task['tasklist']);
        return $storage->add($task);
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
     * @throws Horde_Exception_PermissionDenied
     */
    public function quickAdd($text, $tasklist = null)
    {
        if ($tasklist === null) {
            $tasklist = Nag::getDefaultTasklist(Horde_Perms::EDIT);
        } elseif (!Nag::hasPermission($tasklist, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }

        return Nag::createTasksFromText($text, $tasklist);
    }

    /**
     * Toggles the task completion flag.
     *
     * @param string $task_id      The task ID.
     * @param string $tasklist_id  The tasklist that contains the task.
     *
     * @return boolean|string  True if the task has been toggled, a due date if
     *                         there are still incomplete recurrences.
     */
    public function toggleCompletion($task_id, $tasklist_id)
    {
        if (!Nag::hasPermission($tasklist_id, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }
        try {
            $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
        } catch (Horde_Share_Exception $e) {
            Horde::log($e->getMessage(), 'ERR');
            throw new Nag_Exception($e);
        }
        $task = Nag::getTask($tasklist_id, $task_id);
        $completed = $task->completed;
        $task->toggleComplete();
        $task->save();
        $due = $task->getNextDue();
        if ($task->completed == $completed) {
            if ($due) {
                return $due->toJson();
            }
            return false;
        }
        return true;
    }

    /**
     * Exports a task, identified by UID, in the requested content type.
     *
     * @param string $uid          Identify the task to export.
     * @param string $contentType  What format should the data be in?
     *                             A string with one of:
     * - text/calendar:    iCalendar 2.0. Recommended as this is specified in
     *                     RFC 2445.
     * - text/x-vcalendar: vCalendar 1.0 format. Still in wide use.
     * - activesync:       Horde_ActiveSync_Message_Task.
     * - raw:              Nag_Task.
     * @param array $options      Any additional options for the exporter.
     *
     * @return string  The requested data.
     */
    public function export($uid, $contentType, array $options = array())
    {
        $task = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create('')
            ->getByUID($uid);
        if (!Nag::hasPermission($task->tasklist, Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied();
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
            return $task->toASTask($options);
        case 'raw':
            return $task;
        default:
            throw new Nag_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
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
        if (!Nag::hasPermission($tasklist, Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied();
        }

        return Nag::getTask($tasklist, $id);
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
        if (!Nag::hasPermission($tasklist, Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied();
        }

        $tasks = Nag::listTasks(array(
            'tasklists' => array($tasklist),
            'completed' => Nag::VIEW_ALL,
            'external' => false,
            'include_tags' => true));

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

        throw new Nag_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));

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
        // Handle an arrray of UIDs for convenience
        if (is_array($uid)) {
            foreach ($uid as $g) {
                $result = $this->delete($g);
            }

            return true;
        }

        $factory = $GLOBALS['injector']->getInstance('Nag_Factory_Driver');
        $task = $factory->create('')->getByUID($uid);

        if (!$GLOBALS['registry']->isAdmin() &&
            !Nag::hasPermission($task->tasklist, Horde_Perms::DELETE)) {

             throw new Horde_Exception_PermissionDenied();
        }

        return $factory->create($task->tasklist)->delete($task->id);
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
            !Nag::hasPermission($tasklist, Horde_Perms::DELETE)) {

            throw new Horde_Exception_PermissionDenied();
        }

        $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($tasklist);
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
        $factory = $GLOBALS['injector']->getInstance('Nag_Factory_Driver');
        $existing = $factory->create('')->getByUID($uid);
        $taskId = $existing->id;
        $owner = $existing->owner;
        if (!Nag::hasPermission($existing->tasklist, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }

        switch ($contentType) {
        case 'text/calendar':
        case 'text/x-vcalendar':
            if (!($content instanceof Horde_Icalendar_Vtodo)) {
                $iCal = new Horde_Icalendar();
                if (!$iCal->parsevCalendar($content)) {
                    throw new Nag_Exception(_("There was an error importing the iCalendar data."));
                }

                $components = $iCal->getComponents();
                $component = null;
                foreach ($components as $content) {
                    if ($content instanceof Horde_Icalendar_Vtodo) {
                        if ($component !== null) {
                            throw new Nag_Exception(_("Multiple iCalendar components found; only one vTodo is supported."));
                        }
                        $component = $content;
                    }

                }
                if ($component === null) {
                    throw new Nag_Exception(_("No iCalendar data was found."));
                }
            }

            $task = new Nag_Task();
            $task->fromiCalendar($content);
            $task->owner = $owner;
            $factory->create($existing->tasklist)->modify($taskId, $task->toHash());
            break;

        case 'activesync':
            $task = new Nag_Task();
            $task->fromASTask($content);
            $task->owner = $owner;
            $factory->create($existing->tasklist)->modify($taskId, $task->toHash());
            break;
        default:
            throw new Nag_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
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
            !Nag::hasPermission($tasklist, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }

        $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($tasklist);
        $existing = $storage->get($id);
        $task['owner'] = $existing->owner;

        return $storage->modify($id, $task);
    }

    /**
     * CostObject API: Lists active tasks as cost objects.
     *
     * @todo Implement $criteria parameter.
     *
     * @param array $criteria   Filter attributes
     */
    public function listCostObjects($criteria)
    {
        $tasks = Nag::listTasks(array(
            'completed' => Nag::VIEW_ALL,
            'include_history' => false)
        );
        $result = array();
        $tasks->reset();
        $last_week = $_SERVER['REQUEST_TIME'] - 7 * 86400;
        while ($task = $tasks->each()) {
            if (($task->completed && $task->completed_date < $last_week) ||
                ($task->start && $task->start > $_SERVER['REQUEST_TIME'])) {
                continue;
            }
            $result[$task->id] = array(
                'id' => $task->id,
                'active' => !$task->completed,
                'name' => $task->name
            );
            for ($parent = $task->parent; $parent->parent; $parent = $parent->parent) {
                $result[$task->id]['name'] = $parent->name . ': '
                    . $result[$task->id]['name'];
            }
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

    /**
     * CostObject API:  Update a single costobject.
     *
     * @param string $id   The task id.
     * @param array  $data The data to update. Currently support:
     *   -: hours  The amount of hours to add to the existing actual hours.
     */
    public function updateCostObject($id, $data)
    {
        $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create('');
        $task = $storage->get($id);
        if (!$GLOBALS['registry']->isAdmin() &&
            !Nag::hasPermission($task->tasklist, Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }
        $adjust = array('actual' => $task->actual += $data['hours']);
        $this->updateTask($task->tasklist, $id, $adjust);
    }

    public function listTimeObjectCategories()
    {
        $categories = array();
        $tasklists = Nag::listTasklists(false, Horde_Perms::SHOW | Horde_Perms::READ);
        foreach ($tasklists as $tasklistId => $tasklist) {
            $categories[$tasklistId] = array('title' => Nag::getLabel($tasklist), 'type' => 'share');
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
            if (!isset($allowed_tasklists[$tasklist])) {
                throw new Horde_Exception_PermissionDenied();
            }
        }

        $timeobjects = array();
        $start = new Horde_Date($start);
        $start_ts = $start->timestamp();
        $end = new Horde_Date($end);
        $end_ts = $end->timestamp();

        // List incomplete tasks.
        $tasks = Nag::listTasks(array(
            'tasklists' => $categories,
            'completed' => Nag::VIEW_INCOMPLETE,
            'include_history' => false)
        );

        $tasks->reset();
        while ($task = $tasks->each()) {
            // If there's no due date, it's not a time object.
            if (!$task->due ||
                $task->due > $end_ts ||
                (!$task->recurs() && $task->due + 1 < $start_ts) ||
                ($task->recurs() && $task->recurrence->getRecurEnd() &&
                 $task->recurrence->getRecurEnd()->timestamp() + 1 < $start_ts)) {
                continue;
            }
            $due_date = date('Y-m-d\TH:i:s', $task->due);
            $recurrence = null;
            if ($task->recurs()) {
                $recurrence = array(
                    'type'        => $task->recurrence->getRecurType(),
                    'interval'    => $task->recurrence->getRecurInterval(),
                    'end'         => $task->recurrence->getRecurEnd(),
                    'count'       => $task->recurrence->getRecurCount(),
                    'days'        => $task->recurrence->getRecurOnDays(),
                    'exceptions'  => $task->recurrence->getExceptions(),
                    'completions' => $task->recurrence->getCompletions());
            }
            $timeobjects[$task->id] = array(
                'id' => $task->id,
                'title' => $task->name,
                'description' => $task->desc,
                'start' => $due_date,
                'end' => $due_date,
                'recurrence' => $recurrence,
                'color' => $allowed_tasklists[$task->tasklist]->get('color'),
                'owner' => $allowed_tasklists[$task->tasklist]->get('owner'),
                'permissions' => $GLOBALS['nag_shares']->getPermissions($task->tasklist, $GLOBALS['registry']->getAuth()),
                'variable_length' => false,
                'params' => array(
                    'task' => $task->id,
                    'tasklist' => $task->tasklist,
                ),
                'link' => Horde::url('view.php', true)->add(array('tasklist' => $task->tasklist, 'task' => $task->id)),
                'edit_link' => Horde::url('task.php', true)->add(array('tasklist' => $task->tasklist, 'task' => $task->id, 'actionID' => 'modify_task')),
                'delete_link' => Horde::url('task.php', true)->add(array('tasklist' => $task->tasklist, 'task' => $task->id, 'actionID' => 'delete_task')),
                'ajax_link' => 'task:' . $task->tasklist . ':' . $task->id
            );
        }

        return $timeobjects;
    }

    /**
     * Saves properties of a time object back to the task that it represents.
     *
     * At the moment only the title, description and due date are saved.
     *
     * @param array $timeobject  A time object hash.
     * @throws Nag_Exception
     */
    public function saveTimeObject(array $timeobject)
    {
        if (!Nag::hasPermission($timeobject['params']['tasklist'], Horde_Perms::EDIT)) {
            throw new Horde_Exception_PermissionDenied();
        }
        $storage = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create($timeobject['params']['tasklist']);
        $existing = $storage->get($timeobject['id']);
        $info = array();
        if (isset($timeobject['start'])) {
            $info['due'] = new Horde_Date($timeobject['start']);
            $info['due'] = $info['due']->timestamp();
        }

        if (isset($timeobject['title'])) {
            $info['name'] = $timeobject['title'];
        }
        if (isset($timeobject['description'])) {
            $info['desc'] = $timeobject['description'];
        }
        $storage->modify($timeobject['id'], $info);
    }

    /**
     * Returns a list of available sources.
     *
     * @param boolean $writeable  If true, limits to writeable sources.
     * @param boolean $sync_only  Only include synchable address books.
     *
     * @return array  An array of the available sources. Keys are source IDs,
     *                values are source titles.
     * @since 4.2.0
     */
    public function sources($writeable = false, $sync_only = false)
    {
        $out = array();

        foreach (Nag::listTasklists(false, $writeable ? Horde_Perms::EDIT : Horde_Perms::READ, false) as $key => $val) {
            $out[$key] = $val->get('name');
        }

        if ($sync_only) {
            $syncable = Nag::getSyncLists();
            $out = array_intersect_key($out, array_flip($syncable));
        }

        return $out;
    }

    /**
     * Retrieve the UID for the current user's default tasklist.
     *
     * @return string  UID.
     * @since 4.2.0
     */
    public function getDefaultShare()
    {
        return Nag::getDefaultTasklist(Horde_Perms::EDIT);
    }

    /**
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags  An optional array of tag_ids. If omitted, all tags
     *                     will be included.
     *
     * @return array  An array containing tag_name, and total
     */
    public function listTagInfo($tags = null, $user = null)
    {
        return $GLOBALS['injector']->getInstance('Nag_Tagger')
            ->getTagInfo($tags, 500, null, $user);
    }

    /**
     * SearchTags API:
     * Returns an application-agnostic array (useful for when doing a tag search
     * across multiple applications)
     *
     * The 'raw' results array can be returned instead by setting $raw = true.
     *
     * @param array $names           An array of tag_names to search for.
     * @param integer $max           The maximum number of resources to return.
     * @param integer $from          The number of the resource to start with.
     * @param string $resource_type  The resource type [bookmark, '']
     * @param string $user           Restrict results to resources owned by $user.
     * @param boolean $raw           Return the raw data?
     *
     * @return array An array of results:
     * <pre>
     *  'title'    - The title for this resource.
     *  'desc'     - A terse description of this resource.
     *  'view_url' - The URL to view this resource.
     *  'app'      - The Horde application this resource belongs to.
     *  'icon'     - URL to an image.
     * </pre>
     */
    public function searchTags($names, $max = 10, $from = 0,
                               $resource_type = '', $user = null, $raw = false)
    {
        // TODO: $max, $from, $resource_type not honored
        global $injector, $registry;

        $results = $injector
            ->getInstance('Nag_Tagger')
            ->search(
                $names,
                array('user' => $user));

        // Check for error or if we requested the raw data array.
        if ($raw) {
            return $results;
        }

        $return = array();
        $redirectUrl = Horde::url('redirect.php');
        foreach ($results as $task_id) {
            try {
                $task = $injector->getInstance('Nag_Factory_Driver')
                    ->create(null)
                    ->getByUID($task_id);
                $return[] = array(
                    'title' => $task->name,
                    'desc' => $task->description,
                    'view_url' => $redirectUrl->add('b', $task->id),
                    'app' => 'nag'
                );
            } catch (Exception $e) {
            }
        }

        return $return;
    }
}
