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
                ($task->due - ($task->alarm * 60)) <= $date) {
                $alarms[$task_id] = $task;
            }
        }
        return $alarms;
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
                  'assignee' => null,
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
            Horde::logMessage($e, 'ERR');
        }

        /* Log completion status changes. */
        if ($task->completed) {
            try {
                $history->log('nag:' . $this->_tasklist . ':' . $task->uid,
                              array('action' => 'complete'),
                              true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Notify users about the new event. */
        $result = Nag::sendNotification('add', $task);

        /* Add an alarm if necessary. */
        if (!empty($task->alarm) &&
            ($alarm = $task->toAlarm())) {
            $alarm['start'] = new Horde_Date($alarm['start']);
            $GLOBALS['injector']->getInstance('Horde_Alarm')->set($alarm);
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
        if (isset($properties['completed']) &&
            $properties['completed'] != $task->completed) {
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

        $new_task = $this->get($task->id);
        $log_tasklist = $this->_tasklist;
        if (isset($properties['tasklist']) &&
            $task->tasklist != $properties['tasklist']) {
            /* Moving the task to another tasklist. */
            try {
                $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                throw new Nag_Exception($e);
            }

            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                $GLOBALS['notification']->push(sprintf(_("Access denied removing task from %s."), $share->get('name')), 'horde.error');
                return false;
            }

            try {
                $share = $GLOBALS['nag_shares']->getShare($properties['tasklist']);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                throw new Nag_Exception($e);
            }

            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $GLOBALS['notification']->push(sprintf(_("Access denied moving the task to %s."), $share->get('name')), 'horde.error');
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
                    Horde::logMessage($e, 'ERR');
                }
                try {
                    $history->log('nag:' . $properties['tasklist'] . ':' . $task->uid, array('action' => 'add'), true);
                } catch (Exception $e) {
                    Horde::logMessage($e, 'ERR');
                }
                $log_tasklist = $properties['tasklist'];
            }
        }

        /* Update alarm if necessary. */
        $horde_alarm = $GLOBALS['injector']->getInstance('Horde_Alarm');
        if ((isset($properties['alarm']) && empty($properties['alarm'])) ||
            !empty($properties['completed'])) {
            $horde_alarm->delete($task->uid);
        } else {
            $task = $this->get($taskId);
            $task->process();
            $alarm = $task->toAlarm();
            if ($alarm) {
                $alarm['start'] = new Horde_Date($alarm['start']);
                $horde_alarm->set($alarm);
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
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Log completion status changes. */
        if (isset($properties['completed']) &&
            $task->completed != $properties['completed']) {
            $attributes = array('action' => 'complete');
            if (!$properties['completed']) {
                $attributes['ts'] = 0;
            }
            try {
                $GLOBALS['injector']->getInstance('Horde_History')
                  ->log('nag:' . $log_tasklist . ':' . $task->uid,
                        $attributes,
                        true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Notify users about the changed event. */
        try {
            $result = Nag::sendNotification('edit', $new_task, $task);
        } catch (Nag_Exception $e) {
            Horde::logMessage($e, 'ERR');
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

        /* Log the deletion of this item in the history log. */
        if (!empty($task->uid)) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('nag:' . $this->_tasklist . ':' . $task->uid, array('action' => 'delete'), true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Notify users about the deleted event. */
        try {
            $result = Nag::sendNotification('delete', $task);
        } catch (Nag_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        /* Delete alarm if necessary. */
        if (!empty($task->alarm)) {
            $GLOBALS['injector']->getInstance('Horde_Alarm')->delete($task->uid);
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

        // Update History.
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        try {
            foreach ($ids as $id) {
                $history->log(
                    'nag:' . $this->_tasklist . ':' . $id,
                    array('action' => 'delete'),
                    true);
            }
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
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
     *
     * @return array  List of sub-tasks.
     * @throws Nag_Exception
     */
    public function getChildren($parentId)
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
     * Retrieves one task from the database by UID.
     *
     * @param string $uid  The UID of the task to retrieve.
     *
     * @return Nag_Task  A Nag_Task object.
     * @throws Nag_Exception
     */
    public function getByUID($uid)
    {
        throw new Nag_Exception($this->_errormsg);
    }

    /**
     * Helper function to update an existing event's tags to tagger storage.
     *
     * @param array $task  The task to update
     */
    protected function _updateTags(array $task)
    {
        Nag::getTagger()->replaceTags(
            $task['uid'],
            $task['tags'],
            $task['owner'],
            'task'
        );
    }

    /**
     * Helper function to add tags from a newly creted event to the tagger.
     *
     * @param array $task  The task to save tags to storage for.
     */
    protected function _addTags(array $task)
    {
        Nag::getTagger()->tag(
            $task['uid'],
            $task['tags'],
            $task['owner'],
            'task'
        );
    }

}
