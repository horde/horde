<?php
/**
 * Nag_Driver:: defines an API for implementing storage backends for Nag.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
abstract class Nag_Driver
{
    /**
     * A Nag_Task instance holding the current task list.
     *
     * @var Nag_Task
     */
    public $tasks;

    /**
     * String containing the current tasklist.
     *
     * @var string
     */
    protected $_tasklist = '';

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * An error message to throw when something is wrong.
     *
     * @var string
     */
    protected $_errormsg;

    /**
     * Constructor - just store the $params in our newly-created
     * object. All other work is done by initialize().
     *
     * @param array $params     Any parameters needed for this driver.
     * @param string $errormsg  Custom error message
     *
     * @return Nag_Driver
     */
    public function __construct(array $params = array(), $errormsg = null)
    {
        $this->tasks = new Nag_Task();
        $this->_params = $params;
        if (is_null($errormsg)) {
            $this->_errormsg = _("The Tasks backend is not currently available.");
        } else {
            $this->_errormsg = $errormsg;
        }
    }

    /**
     * List all alarms near $date.
     *
     * @param integer $date  The unix epoch time to check for alarms.
     *
     * @return array  An array of tasks that have alarms that match.
     */
    public function listAlarms($date)
    {
        if (!$this->tasks->count()) {
            $result = $this->retrieve(0);
        }
        $alarms = array();
        $this->tasks->reset();
        while ($task = $this->tasks->each()) {
            if ($task->alarm &&
                ($due = $task->getNextDue()) &&
                ($due->timestamp() - ($task->alarm * 60)) <= $date) {
                $alarms[$task_id] = $task;
            }
        }
        return $alarms;
    }

    /**
     * Sets the currently open tasklist.
     *
     * @param  string $tasklist  The tasklist.
     */
    public function open($tasklist)
    {
        $this->_tasklist = $tasklist;
    }

    /**
     * Adds a task and handles notification.
     *
     * @param array $task  A hash with the following possible properties:
     *     - name: (string) The name (short) of the task.
     *     - desc: (string) The description (long) of the task.
     *     - start: (OPTIONAL, integer) The start date of the task.
     *     - due: (OPTIONAL, integer) The due date of the task.
     *     - priority: (OPTIONAL, integer) The priority of the task.
     *     - estimate: (OPTIONAL, float) The estimated time to complete the
     *                 task.
     *     - completed: (OPTIONAL, integer) The completion state of the task.
     *     - tags: (OPTIONAL, string) The comma delimited list of tags.
     *     - alarm: (OPTIONAL, integer) The alarm associated with the task.
     *     - methods: (OPTIONAL, array) The overridden alarm notification
     *                methods.
     *     - uid: (OPTIONAL, string) A Unique Identifier for the task.
     *     - parent: (OPTIONAL, string) The parent task.
     *     - private: (OPTIONAL, boolean) Whether the task is private.
     *     - owner: (OPTIONAL, string) The owner of the event.
     *     - assignee: (OPTIONAL, string) The assignee of the event.
     *     - completed_date: (OPTIONAL, integer) The task's completion date.
     *     - recurrence: (OPTIONAL, Horde_Date_Recurrence|array) Recurrence
     *                   information.
     *
     * @return array  array(ID,UID) of new task
     */
    public function add(array $task)
    {
        $task = array_merge(
            array('start' => 0,
                  'due' => 0,
                  'priority' => 3,
                  'estimate' => 0.0,
                  'completed' => 0,
                  'tags' => '',
                  'alarm' => 0,
                  'methods' => null,
                  'uid' => strval(new Horde_Support_Guid()),
                  'parent' => '',
                  'private' => false,
                  'owner' => $GLOBALS['registry']->getAuth(),
                  'organizer' => null,
                  'assignee' => null,
                  'status' => null,
                  'completed_date' => 0,
                  'recurrence' => null),
            $task
        );

        $taskId = $this->_add($task);
        $task = $this->get($taskId);
        $task->process();

        /* Log the creation of this item in the history log. */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        try {
            $history->log('nag:' . $this->_tasklist . ':' . $task->uid,
                          array('action' => 'add'),
                          true);
        } catch (Exception $e) {
            Horde::log($e, 'ERR');
        }

        /* Log completion status changes. */
        if ($task->completed) {
            try {
                $history->log('nag:' . $this->_tasklist . ':' . $task->uid,
                              array('action' => 'complete'),
                              true);
            } catch (Exception $e) {
                Horde::log($e, 'ERR');
            }
        }

        /* Notify users about the new event. */
        $result = Nag::sendNotification('add', $task);

        /* Add an alarm if necessary. */
        if (!empty($task->alarm) &&
            ($alarm = $task->toAlarm())) {
            $hordeAlarm = $GLOBALS['injector']->getInstance('Horde_Alarm');
            $hordeAlarm->set($alarm);
            if ($task->snooze) {
                $hordeAlarm->snooze($task->uid, $GLOBALS['registry']->getAuth(), $task->snooze);
            }
        }

        return array($taskId, $task->uid);
    }

    /**
     * @see add()
     */
    abstract protected function _add(array $task);

    /**
     * Modifies an existing task and handles notification.
     *
     * @param string $taskId  The task to modify.
     * @param array $properties  A hash with the following possible properties:
     *     - name: (string) The name (short) of the task.
     *     - desc: (string) The description (long) of the task.
     *     - start: (OPTIONAL, integer) The start date of the task.
     *     - due: (OPTIONAL, integer) The due date of the task.
     *     - priority: (OPTIONAL, integer) The priority of the task.
     *     - estimate: (OPTIONAL, float) The estimated time to complete the
     *                 task.
     *     - completed: (OPTIONAL, integer) The completion state of the task.
     *     - tags: (OPTIONAL, string) The tags of the task.
     *     - alarm: (OPTIONAL, integer) The alarm associated with the task.
     *     - methods: (OPTIONAL, array) The overridden alarm notification
     *                methods.
     *     - uid: (OPTIONAL, string) A Unique Identifier for the task.
     *     - parent: (OPTIONAL, string) The parent task.
     *     - private: (OPTIONAL, boolean) Whether the task is private.
     *     - owner: (OPTIONAL, string) The owner of the event.
     *     - assignee: (OPTIONAL, string) The assignee of the event.
     *     - completed_date: (OPTIONAL, integer) The task's completion date.
     *     - tasklist: (OPTIONAL, string) The new tasklist.
     *     - recurrence: (OPTIONAL, Horde_Date_Recurrence|array) Recurrence
     *                   information.
     *
     * @throws Nag_Exception
     */
    public function modify($taskId, array $properties)
    {
        /* Retrieve unmodified task. */
        $task = $this->get($taskId);

        /* Avoid circular reference. */
        if (isset($properties['parent']) &&
            $properties['parent'] == $taskId) {
            unset($properties['parent']);
        }

        /* Toggle completion. We cannot simply set the completion flag
         * because this might be a recurring task, and marking the
         * task complete might only shift the due date to the next
         * recurrence. */
        $task_completed = $task->completed;
        $completed_changed = isset($properties['completed']) &&
            $properties['completed'] != $task->completed;
        $new_completed = !empty($properties['completed']);
        if ($completed_changed) {
            if (isset($properties['recurrence'])) {
                if ($task->recurs()) {
                    $completions = $task->recurrence->completions;
                    $exceptions = $task->recurrence->exceptions;
                } else {
                    $completions = $exceptions = array();
                }
                $task->recurrence = $properties['recurrence'];
                $task->recurrence->completions = $completions;
                $task->recurrence->exceptions = $exceptions;
                unset($properties['recurrence']);
            }
            $task->toggleComplete();
            unset($properties['completed']);
        }

        $this->_modify($taskId, array_merge($task->toHash(), $properties));

        // Must persist the original completed value so we can detect the
        // change when notifications are sent.
        $task->completed = $task_completed;

        $new_task = $this->get($task->id);
        $log_tasklist = $this->_tasklist;
        if (isset($properties['tasklist']) &&
            $task->tasklist != $properties['tasklist']) {
            /* Moving the task to another tasklist. */
            try {
                $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
            } catch (Horde_Share_Exception $e) {
                Horde::log($e->getMessage(), 'ERR');
                throw new Nag_Exception($e);
            }

            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                $GLOBALS['notification']->push(_("Access denied removing task from this task list."), 'horde.error');
                return false;
            }

            try {
                $share = $GLOBALS['nag_shares']->getShare($properties['tasklist']);
            } catch (Horde_Share_Exception $e) {
                Horde::log($e->getMessage(), 'ERR');
                throw new Nag_Exception($e);
            }

            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $GLOBALS['notification']->push(_("Access denied moving the task to this task list."), 'horde.error');
            }

            $moved = $this->_move($task->id, $properties['tasklist']);
            $new_storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($properties['tasklist']);
            $new_task = $new_storage->get($task->id);

            /* Log the moving of this item in the history log. */
            if (!empty($task->uid)) {
                $history = $GLOBALS['injector']->getInstance('Horde_History');
                try {
                    $history->log('nag:' . $task->tasklist . ':' . $task->uid, array('action' => 'delete'), true);
                } catch (Exception $e) {
                    Horde::log($e, 'ERR');
                }
                try {
                    $history->log('nag:' . $properties['tasklist'] . ':' . $task->uid, array('action' => 'add'), true);
                } catch (Exception $e) {
                    Horde::log($e, 'ERR');
                }
                $log_tasklist = $properties['tasklist'];
            }
        }

        /* Update alarm if necessary. */
        $horde_alarm = $GLOBALS['injector']->getInstance('Horde_Alarm');
        if ((isset($properties['alarm']) && empty($properties['alarm'])) ||
            $new_completed) {
            $horde_alarm->delete($task->uid);
        } else {
            $task = $this->get($taskId);
            $task->process();
            if (!$task->recurs()) {
                $alarm = $task->toAlarm();
            } else {
                $alarm = null;
                /* Get current occurrence (task due date) */
                $current = $task->recurrence->nextActiveRecurrence(new Horde_Date($task->due));
                if ($current) {
                    /* Advance this occurence by a day to indicate that we
                     * want the following occurence (Recurrence uses days
                     * as minimal time duration between occurrences). */
                    $current->mday++;
                    /* Only mark this due date completed if there is another
                     * occurence. */
                    if ($next = $task->recurrence->nextActiveRecurrence($current)) {
                        $alarm = $task->toAlarm();
                        if ($alarm) {
                            $alarm['start'] = new Horde_Date($next);
                            $horde_alarm->set($alarm);
                        }
                    }
                }
            }
            if ($alarm) {
                $alarm['start'] = new Horde_Date($alarm['start']);
                $horde_alarm->set($alarm);
                if ($task->snooze) {
                    $hordeAlarm->snooze($task->uid, $GLOBALS['registry']->getAuth(), $task->snooze);
                }
            }
        }

        /* Log the modification of this item in the history log. */
        if (!empty($task->uid)) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')
                  ->log('nag:' . $log_tasklist . ':' . $task->uid,
                        array('action' => 'modify'),
                        true);
            } catch (Exception $e) {
                Horde::log($e, 'ERR');
            }
        }

        /* Log completion status changes. */
        if ($completed_changed) {
            $attributes = array('action' => 'complete');
            if (!$new_completed) {
                $attributes['ts'] = 0;
            }
            try {
                $GLOBALS['injector']->getInstance('Horde_History')
                  ->log('nag:' . $log_tasklist . ':' . $task->uid,
                        $attributes,
                        true);
            } catch (Exception $e) {
                Horde::log($e, 'ERR');
            }
        }

        /* Notify users about the changed event. */
        try {
            Nag::sendNotification('edit', $new_task, $task);
        } catch (Nag_Exception $e) {
            Horde::log($e, 'ERR');
        }
    }

    /**
     * @see modify()
     */
    abstract protected function _modify($taskId, array $task);

    /**
     * Deletes a task and handles notification.
     *
     * @param string $taskId  The task to delete.
     */
    public function delete($taskId)
    {
        /* Get the task's details for use later. */
        $task = $this->get($taskId);
        $delete = $this->_delete($taskId);

        /* Remove tags */
        $task->tags = array();
        $this->_updateTags($task->toHash());

        /* Tell content we removed the object */
        $GLOBALS['injector']->getInstance('Content_Objects_Manager')
            ->delete(array($task->uid), 'task');

        /* Log the deletion of this item in the history log. */
        if (!empty($task->uid)) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('nag:' . $this->_tasklist . ':' . $task->uid, array('action' => 'delete'), true);
            } catch (Exception $e) {
                Horde::log($e, 'ERR');
            }
        }

        /* Notify users about the deleted event. */
        try {
            $result = Nag::sendNotification('delete', $task);
        } catch (Nag_Exception $e) {
            Horde::log($e, 'ERR');
        }

        /* Delete alarm if necessary. */
        if (!empty($task->alarm)) {
            $GLOBALS['injector']->getInstance('Horde_Alarm')->delete($task->uid);
        }

        /* Remove any CalDAV mappings. */
        try {
            $davStorage = $GLOBALS['injector']
                ->getInstance('Horde_Dav_Storage');
            try {
                $davStorage
                    ->deleteInternalObjectId($task->id, $task->tasklist);
            } catch (Horde_Exception $e) {
                Horde::log($e);
            }
        } catch (Horde_Exception $e) {
        }
    }

    /**
     * Deletes all tasks for the current task list.
     *
     * @throws Nag_Exception
     */
    public function deleteAll()
    {
        $ids = $this->_deleteAll();

        // Update History and Tagger
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        try {
            foreach ($ids as $uid) {
                $history->log(
                    'nag:' . $this->_tasklist . ':' . $uid,
                    array('action' => 'delete'),
                    true);

                $GLOBALS['injector']->getInstance('Nag_Tagger')
                    ->replaceTags($uid, array(), $GLOBALS['registry']->getAuth(), 'task');

                /* Tell content we removed the object */
                $GLOBALS['injector']->getInstance('Content_Objects_Manager')
                    ->delete(array($uid), 'task');
            }
        } catch (Exception $e) {
            Horde::log($e, 'ERR');
        }
    }

    /**
     * Retrieves tasks from the database.
     *
     * @throws Nag_Exception
     */
    public function retrieve()
    {
        throw new Nag_Exception($this->_errormsg);
    }

    /**
     * Retrieves sub-tasks from the database.
     *
     * @param string $parentId  The parent id for the sub-tasks to retrieve.
     * @param boolean $include_history  Include created/modified info?
     *
     * @return array  List of sub-tasks.
     * @throws Nag_Exception
     */
    public function getChildren($parentId, $include_history = true)
    {
        throw new Nag_Exception($this->_errormsg);
    }

    /**
     * Retrieves one task from the database.
     *
     * @param string $taskId  The id of the task to retrieve.
     *
     * @return Nag_Task  A Nag_Task object.
     * @throws Nag_Exception
     */
    public function get($taskId)
    {
        throw new Nag_Exception($this->_errormsg);
    }

    /**
     * Retrieves one or multiple tasks from the database by UID.
     *
     * @param string|array $uid  The UID(s) of the task to retrieve.
     * @param array $tasklists   An optional array of tasklists to search.
     * @param boolean $getall    If true, return all instances of the task,
     *                           otherwise only one. Attempts to find the
     *                           instance owned by the current user.
     *
     * @return Nag_Task  A Nag_Task object.
     * @throws Horde_Exception_NotFound
     * @throws Nag_Exception
     */
    public function getByUID($uids, array $tasklists = null, $getall = true)
    {
        throw new Nag_Exception($this->_errormsg);
    }

    /**
     * Synchronize with the Kolab backend.
     *
     * @param mixed  $token  A value indicating the last synchronization point,
     *                       if available.
     */
    public function synchronize($token = false)
    {
    }

    /**
     * Helper function to update an existing event's tags to tagger storage.
     *
     * @param array $task  The task to update
     */
    protected function _updateTags(array $task)
    {
        $GLOBALS['injector']->getInstance('Nag_Tagger')->replaceTags(
            $task['uid'],
            $task['tags'],
            $task['owner'],
            'task'
        );
    }

    /**
     * Helper function to add tags from a newly created event to the tagger.
     *
     * @param array $task  The task to save tags to storage for.
     */
    protected function _addTags(array $task)
    {
        $GLOBALS['injector']->getInstance('Nag_Tagger')->tag(
            $task['uid'],
            $task['tags'],
            $task['owner'],
            'task'
        );
    }

}
