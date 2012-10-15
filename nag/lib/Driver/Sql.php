<?php
/**
 * Nag storage implementation for PHP's PEAR database abstraction layer.
 *
 * The table structure can be created by the scripts/sql/nag.sql script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Nag
 */
class Nag_Driver_Sql extends Nag_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructs a new SQL storage object.
     *
     * @param string $tasklist  The tasklist to load.
     * @param array $params     A hash containing connection parameters.
     */
    public function __construct($tasklist, $params = array())
    {
        $this->_tasklist = $tasklist;
        $this->_params = $params;
        if (!isset($this->_params['table'])) {
            $this->_params['table'] = 'nag_tasks';
        }
        $this->_db = $this->_params['db'];
    }

    /**
     * Retrieves one task from the database.
     *
     * @param mixed string|array $taskIds  The ids of the task to retrieve.
     *
     * @return Nag_Task A Nag_Task object.
     * @throws Horde_Exception_NotFound
     * @throws Nag_Exception
     */
    public function get($taskIds)
    {
        if (!is_array($taskIds)) {
            $query = sprintf('SELECT * FROM %s WHERE task_id = ?',
                             $this->_params['table']);
            $values = array($taskIds);
        } else {
            if (empty($taskIds)) {
                throw new InvalidArgumentException('Must specify at least one task id');
            }
            $query = sprintf('SELECT * FROM %s WHERE task_id IN ('
                . implode(',', array_fill(0, count($taskIds), '?')) . ')',
                    $this->_params['table']
            );
            $values = $taskIds;
        }

        try {
            $rows = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e);
        }
        if (!$rows) {
            throw new Horde_Exception_NotFound("Tasks not found");
        }

        if (!is_array($taskIds)) {
            $results = new Nag_Task($this, $this->_buildTask(current($rows)));
            $this->_tasklist = $results->tasklist;
        } else {
            $results = new Nag_Task();
            foreach ($rows as $row) {
                $results->add(new Nag_Task($this, $this->_buildTask($row)));
            }
        }

        return $results;
    }

    /**
     * Retrieves one task from the database by UID.
     *
     * @param string $uid  The UID of the task to retrieve.
     *
     * @return Nag_Task  A Nag_Task object.
     * @throws Horde_Exception_NotFound
     * @throws Nag_Exception
     */
    public function getByUID($uids)
    {
        if (!is_array($uids)) {
            $query = sprintf('SELECT * FROM %s WHERE task_uid = ?',
                         $this->_params['table']);
            $values = array($uids);
        } else {
            if (empty($uids)) {
                throw new InvalidArgumentException('Must specify at least one task id');
            }
            $query = sprintf('SELECT * FROM %s WHERE task_uid IN ('
                . implode(',', array_fill(0, count($uids), '?')) . ')',
                $this->_params['table']);
            $values = $uids;
        }
        try {
            $rows = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }
        if (!$rows) {
            throw new Horde_Exception_NotFound(sprintf(_("Task UID %s not found"), $uid));
        }
        if (!is_array($uids)) {
            // @TODO: Check this. Not sure why getByUID should have the side
            //        effect of setting the tasklist while get() does not.
            $this->_tasklist = $row['task_owner'];
            return new Nag_Task($this, $this->_buildTask(current($rows)));
        }

        $results = new Nag_Task();
        foreach ($rows as $row) {
            $results->add(new Nag_Task($this, $this->_buildTask($row)));
        }

        return $results;
    }

    /**
     * Adds a task to the backend storage.
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
     *     - tags: (OPTIONAL, array) The task tags.
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
     * @return string  The Nag ID of the new task.
     * @throws Nag_Exception
     */
    protected function _add(array $task)
    {
        $taskId = strval(new Horde_Support_Randomid());

        $query = sprintf(
            'INSERT INTO %s (task_owner, task_creator, task_assignee, '
            . 'task_id, task_name, task_uid, task_desc, task_start, task_due, '
            . 'task_priority, task_estimate, task_completed, '
            . 'task_alarm, task_alarm_methods, task_private, task_parent, '
            . 'task_recurtype, task_recurinterval, task_recurenddate, '
            . 'task_recurcount, task_recurdays, task_exceptions, '
            . 'task_completions) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $this->_params['table']);

        $values = array($this->_tasklist,
                        $task['owner'],
                        $task['assignee'],
                        $taskId,
                        Horde_String::convertCharset($task['name'], 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($task['uid'], 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($task['desc'], 'UTF-8', $this->_params['charset']),
                        (int)$task['start'],
                        (int)$task['due'],
                        (int)$task['priority'],
                        number_format(floatval($task['estimate']), 2),
                        (int)$task['completed'],
                        (int)$task['alarm'],
                        serialize(Horde_String::convertCharset($task['methods'], 'UTF-8', $this->_params['charset'])),
                        (int)$task['private'],
                        $task['parent']);

        $this->_addRecurrenceFields($values, $task);

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e);
        }

        $this->_addTags($task);

        return $taskId;
    }

    /**
     * Modifies an existing task.
     *
     * @param string $taskId  The task to modify.
     * @param array $properties  A hash with the following possible properties:
     *     - id: (string) The task to modify.
     *     - name: (string) The name (short) of the task.
     *     - desc: (string) The description (long) of the task.
     *     - start: (OPTIONAL, integer) The start date of the task.
     *     - due: (OPTIONAL, integer) The due date of the task.
     *     - priority: (OPTIONAL, integer) The priority of the task.
     *     - estimate: (OPTIONAL, float) The estimated time to complete the
     *                 task.
     *     - completed: (OPTIONAL, integer) The completion state of the task.
     *     - tags: (OPTIONAL, array) The task tags.
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
     * @throws Nag_Exception
     */
    protected function _modify($taskId, array $task)
    {
        $query = sprintf('UPDATE %s SET' .
                         ' task_creator = ?, ' .
                         ' task_assignee = ?, ' .
                         ' task_name = ?, ' .
                         ' task_desc = ?, ' .
                         ' task_start = ?, ' .
                         ' task_due = ?, ' .
                         ' task_priority = ?, ' .
                         ' task_estimate = ?, ' .
                         ' task_completed = ?, ' .
                         ' task_completed_date = ?, ' .
                         ' task_alarm = ?, ' .
                         ' task_alarm_methods = ?, ' .
                         ' task_parent = ?, ' .
                         ' task_private = ?, ' .
                         ' task_recurtype = ?, ' .
                         ' task_recurinterval = ?, ' .
                         ' task_recurenddate = ?, ' .
                         ' task_recurcount = ?, ' .
                         ' task_recurdays = ?, ' .
                         ' task_exceptions = ?, ' .
                         ' task_completions = ? ' .
                         'WHERE task_owner = ? AND task_id = ?',
                         $this->_params['table']);

        $values = array($task['owner'],
                        $task['assignee'],
                        Horde_String::convertCharset($task['name'], 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($task['desc'], 'UTF-8', $this->_params['charset']),
                        (int)$task['start'],
                        (int)$task['due'],
                        (int)$task['priority'],
                        number_format($task['estimate'], 2),
                        (int)$task['completed'],
                        (int)$task['completed_date'],
                        (int)$task['alarm'],
                        serialize(Horde_String::convertCharset($task['methods'], 'UTF-8', $this->_params['charset'])),
                        $task['parent'],
                        (int)$task['private']);
        $this->_addRecurrenceFields($values, $task);
        $values[] = $this->_tasklist;
        $values[] = $taskId;

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }

        if (!empty($task['uid'])) {
            $this->_updateTags($task);
        }

        return true;
    }

    /**
     * Adds recurrence information to the value hash for SQL
     * INSERT/UPDATE queries.
     *
     * @param array $values  The fields to update.
     * @param array $task    The task information.
     */
    protected function _addRecurrenceFields(&$values, $task)
    {
        if (!$task['recurrence']) {
            $values[] = 0;
            for ($i = 0; $i < 6; $i++) {
                $values[] = null;
            }
        } else {
            $recurrence = $task['recurrence'];
            $recur = $recurrence->getRecurType();
            if ($recurrence->hasRecurEnd()) {
                $recur_end = clone $recurrence->recurEnd;
                $recur_end->setTimezone('UTC');
            } else {
                $recur_end = new Horde_Date(array('year' => 9999, 'month' => 12, 'mday' => 31, 'hour' => 23, 'min' => 59, 'sec' => 59));
            }

            $values[] = $recur;
            $values[] = $recurrence->getRecurInterval();
            $values[] = $recur_end->format('Y-m-d H:i:s');
            $values[] = $recurrence->getRecurCount();

            switch ($recur) {
            case Horde_Date_Recurrence::RECUR_WEEKLY:
                $values[] = $recurrence->getRecurOnDays();
                break;
            default:
                $values[] = null;
                break;
            }
            $values[] = implode(',', $recurrence->getExceptions());
            $values[] = implode(',', $recurrence->getCompletions());
        }
    }

    /**
     * Moves a task to a different tasklist.
     *
     * @param string $taskId       The task to move.
     * @param string $newTasklist  The new tasklist.
     *
     * @throws Nag_Exception
     */
    protected function _move($taskId, $newTasklist)
    {
        $query = sprintf('UPDATE %s SET task_owner = ? WHERE task_owner = ? AND task_id = ?',
                         $this->_params['table']);
        $values = array($newTasklist, $this->_tasklist, $taskId);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }
    }

    /**
     * Deletes a task from the backend.
     *
     * @param string $taskId  The task to delete.
     *
     * @throws Nag_Exception
     */
    protected function _delete($taskId)
    {
        /* Get the task's details for use later. */
        $task = $this->get($taskId);

        $query = sprintf('DELETE FROM %s WHERE task_owner = ? AND task_id = ?',
                         $this->_params['table']);
        $values = array($this->_tasklist, $taskId);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw Nag_Exception($e->getMessage());
        }
    }

    /**
     * Deletes all tasks from the backend.
     *
     * @return array  An array of uids that have been removed.
     * @throws Nag_Exception
     */
    protected function _deleteAll()
    {
        // Get the list of ids so we can notify History.
        $query = sprintf('SELECT task_uid FROM %s WHERE task_owner = ?',
            $this->_params['table']);

        $values = array($this->_tasklist);

        try {
            $ids = $this->_db->selectValues($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }

        // Deletion
        $query = sprintf('DELETE FROM %s WHERE task_owner = ?',
            $this->_params['table']);
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }

        return $ids;
    }

    /**
     * Retrieves tasks from the database.
     *
     * @param integer $completed  Which tasks to retrieve (1 = all tasks,
     *                            0 = incomplete tasks, 2 = complete tasks,
     *                            3 = future tasks, 4 = future and incomplete
     *                            tasks).
     * @throws Nag_Exception
     */
    public function retrieve($completed = Nag::VIEW_ALL)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE task_owner = ?',
                         $this->_params['table']);
        $values = array($this->_tasklist);
        switch ($completed) {
        case Nag::VIEW_INCOMPLETE:
            $query .= ' AND task_completed = 0 AND (task_start IS NULL OR task_start = 0 OR task_start < ?)';
            $values[] = time();
            break;

        case Nag::VIEW_COMPLETE:
            $query .= ' AND task_completed = 1';
            break;

        case Nag::VIEW_FUTURE:
            $query .= ' AND task_completed = 0 AND task_start > ?';
            $values[] = time();
            break;

        case Nag::VIEW_FUTURE_INCOMPLETE:
            $query .= ' AND task_completed = 0';
            break;
        }

        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }

        /* Store the retrieved values in a fresh task list. */
        $this->tasks = new Nag_Task();
        $dict = array();

        foreach ($result as $row) {
            $task = new Nag_Task($this, $this->_buildTask($row));

            /* Add task directly if it is a root task, otherwise store it in
             * the dictionary. */
            if (empty($row['task_parent'])) {
                $this->tasks->add($task);
            } else {
                $dict[$row['task_id']] = $task;
            }
        }

        /* Build a tree from the subtasks. */
        foreach (array_keys($dict) as $key) {
            $task = $this->tasks->get($dict[$key]->parent_id);
            if ($task) {
                $task->add($dict[$key]);
            } elseif (isset($dict[$dict[$key]->parent_id])) {
                $dict[$dict[$key]->parent_id]->add($dict[$key]);
            } else {
                $this->tasks->add($dict[$key]);
            }
        }
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
        // Build the SQL query.
        $query = sprintf('SELECT * FROM %s WHERE task_owner = ? AND task_parent = ?',
                         $this->_params['table']);
        $values = array($this->_tasklist, $parentId);

        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }

        // Store the retrieved values in a fresh task list.
        $tasks = array();
        foreach ($result as $row) {
            $task = new Nag_Task($this, $this->_buildTask($row));
            $children = $this->getChildren($task->id);
            $task->mergeChildren($children);
            $tasks[] = $task;
        }

        return $tasks;
    }

    /**
     * Lists all alarms near $date.
     *
     * @param integer $date  The unix epoch time to check for alarms.
     *
     * @return array  An array of tasks that have alarms that match.
     * @throws Nag_Exception
     */
    public function listAlarms($date)
    {
        $q = 'SELECT * FROM ' . $this->_params['table'] .
            ' WHERE task_owner = ?' .
            ' AND task_alarm > 0' .
            ' AND (task_due - (task_alarm * 60) <= ?)' .
            ' AND task_completed = 0';
        $values = array($this->_tasklist, $date);

        try {
            $result = $this->_db->selectAll($q, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Nag_Exception($e->getMessage());
        }

        $tasks = array();
        foreach ($result as $row) {
            $tasks[$row['task_id']] = new Nag_Task($this, $this->_buildTask($row));
        }

        return $tasks;
    }

    /**
     */
    protected function _buildTask($row)
    {
        // Make sure tasks always have a UID.
        if (empty($row['task_uid'])) {
            $row['task_uid'] = strval(new Horde_Support_Guid());

            $query = 'UPDATE ' . $this->_params['table'] .
                ' SET task_uid = ?' .
                ' WHERE task_owner = ? AND task_id = ?';
            $values = array($row['task_uid'], $row['task_owner'], $row['task_id']);

            try {
                $this->_db->update($query, $values);
            } catch (Horde_Db_Exception $e) {}
        }

        if (!$row['task_due'] || !$row['task_recurtype']) {
            $recurrence = null;
        } else {
            $recurrence = new Horde_Date_Recurrence($row['task_due']);
            $recurrence->setRecurType((int)$row['task_recurtype']);
            $recurrence->setRecurInterval((int)$row['task_recurinterval']);
            if (isset($row['task_recurenddate']) &&
                $row['task_recurenddate'] != '9999-12-31 23:59:59') {
                $recur_end = new Horde_Date($row['task_recurenddate'], 'UTC');
                $recur_end->setTimezone(date_default_timezone_get());
                $recurrence->setRecurEnd($recur_end);
            }
            if (isset($row['task_recurcount'])) {
                $recurrence->setRecurCount((int)$row['task_recurcount']);
            }
            if (isset($row['task_recurdays'])) {
                $recurrence->recurData = (int)$row['task_recurdays'];
            }
            if (!empty($row['task_exceptions'])) {
                $recurrence->exceptions = explode(',', $row['task_exceptions']);
            }
            if (!empty($row['task_completions'])) {
                $recurrence->completions = explode(',', $row['task_completions']);
            }
        }

        /* Create a new task based on $row's values. */
        return array(
            'tasklist_id' => $row['task_owner'],
            'task_id' => $row['task_id'],
            'uid' => Horde_String::convertCharset($row['task_uid'], $this->_params['charset'], 'UTF-8'),
            'parent' => $row['task_parent'],
            'owner' => $row['task_creator'],
            'assignee' => $row['task_assignee'],
            'name' => Horde_String::convertCharset($row['task_name'], $this->_params['charset'], 'UTF-8'),
            'desc' => Horde_String::convertCharset($row['task_desc'], $this->_params['charset'], 'UTF-8'),
            'start' => $row['task_start'],
            'due' => $row['task_due'],
            'priority' => $row['task_priority'],
            'estimate' => (float)$row['task_estimate'],
            'completed' => $row['task_completed'],
            'completed_date' => isset($row['task_completed_date']) ? $row['task_completed_date'] : null,
            'alarm' => $row['task_alarm'],
            'methods' => Horde_String::convertCharset(@unserialize($row['task_alarm_methods']), $this->_params['charset'], 'UTF-8'),
            'private' => $row['task_private'],
            'recurrence' => $recurrence
        );
    }

}
