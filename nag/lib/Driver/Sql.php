<?php
/**
 * Nag storage implementation for PHP's PEAR database abstraction layer.
 *
 * The table structure can be created by the scripts/sql/nag.sql script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Nag
 */
class Nag_Driver_Sql extends Nag_Driver {

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    var $_db;

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
    }

    /**
     * Retrieves one task from the database.
     *
     * @param string $taskId  The id of the task to retrieve.
     *
     * @return Nag_Task  A Nag_Task object.
     */
    function get($taskId)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE task_id = ?',
                         $this->_params['table']);
        $values = array($taskId);

        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        if (!$row) {
            return PEAR::raiseError(_("Task not found"));
        }

        /* Decode and return the task. */
        return new Nag_Task($this->_buildTask($row));
    }

    /**
     * Retrieves one task from the database by UID.
     *
     * @param string $uid  The UID of the task to retrieve.
     *
     * @return Nag_Task  A Nag_Task object.
     */
    function getByUID($uid)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE task_uid = ?',
                         $this->_params['table']);
        $values = array($uid);

        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        if (!$row) {
            return PEAR::raiseError(_("Task UID not found"));
        }

        /* Decode and return the task. */
        $this->_tasklist = $row['task_owner'];

        return new Nag_Task($this->_buildTask($row));
    }

    /**
     * Adds a task to the backend storage.
     *
     * @param string $name        The name (short) of the task.
     * @param string $desc        The description (long) of the task.
     * @param integer $start      The start date of the task.
     * @param integer $due        The due date of the task.
     * @param integer $priority   The priority of the task.
     * @param float $estimate     The estimated time to complete the task.
     * @param integer $completed  The completion state of the task.
     * @param string $category    The category of the task.
     * @param integer $alarm      The alarm associated with the task.
     * @param array $methods      The overridden alarm notification methods.
     * @param string $uid         A Unique Identifier for the task.
     * @param string $parent      The parent task id.
     * @param boolean $private    Whether the task is private.
     * @param string $owner       The owner of the event.
     * @param string $assignee    The assignee of the event.
     *
     * @return string  The Nag ID of the new task.
     */
    function _add($name, $desc, $start = 0, $due = 0, $priority = 0,
                  $estimate = 0.0, $completed = 0, $category = '', $alarm = 0,
                  $methods = null, $uid = null, $parent = '', $private = false,
                  $owner = null, $assignee = null)
    {
        $taskId = strval(new Horde_Support_Randomid());
        if (is_null($uid)) {
            $uid = strval(new Horde_Support_Guid());
        }

        $query = sprintf(
            'INSERT INTO %s (task_owner, task_creator, task_assignee, '
            . 'task_id, task_name, task_uid, task_desc, task_start, task_due, '
            . 'task_priority, task_estimate, task_completed, task_category, '
            . 'task_alarm, task_alarm_methods, task_private, task_parent) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $this->_params['table']);
        $values = array($this->_tasklist,
                        $owner,
                        $assignee,
                        $taskId,
                        Horde_String::convertCharset($name, 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($uid, 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']),
                        (int)$start,
                        (int)$due,
                        (int)$priority,
                        number_format($estimate, 2),
                        (int)$completed,
                        Horde_String::convertCharset($category, 'UTF-8', $this->_params['charset']),
                        (int)$alarm,
                        serialize(Horde_String::convertCharset($methods, 'UTF-8', $this->_params['charset'])),
                        (int)$private,
                        $parent);

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return $taskId;
    }

    /**
     * Modifies an existing task.
     *
     * @param string $taskId           The task to modify.
     * @param string $name             The name (short) of the task.
     * @param string $desc             The description (long) of the task.
     * @param integer $start           The start date of the task.
     * @param integer $due             The due date of the task.
     * @param integer $priority        The priority of the task.
     * @param float $estimate          The estimated time to complete the task.
     * @param integer $completed       The completion state of the task.
     * @param string $category         The category of the task.
     * @param integer $alarm           The alarm associated with the task.
     * @param array $methods           The overridden alarm notification
     *                                 methods.
     * @param string $parent           The parent task id.
     * @param boolean $private         Whether the task is private.
     * @param string $owner            The owner of the event.
     * @param string $assignee         The assignee of the event.
     * @param integer $completed_date  The task's completion date.
     */
    function _modify($taskId, $name, $desc, $start = 0, $due = 0,
                     $priority = 0, $estimate = 0.0, $completed = 0,
                     $category = '', $alarm = 0, $methods = null,
                     $parent = '', $private = false, $owner = null,
                     $assignee = null, $completed_date = null)
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
                         ' task_category = ?, ' .
                         ' task_alarm = ?, ' .
                         ' task_alarm_methods = ?, ' .
                         ' task_parent = ?, ' .
                         ' task_private = ? ' .
                         'WHERE task_owner = ? AND task_id = ?',
                         $this->_params['table']);
        $values = array($owner,
                        $assignee,
                        Horde_String::convertCharset($name, 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']),
                        (int)$start,
                        (int)$due,
                        (int)$priority,
                        number_format($estimate, 2),
                        (int)$completed,
                        (int)$completed_date,
                        Horde_String::convertCharset($category, 'UTF-8', $this->_params['charset']),
                        (int)$alarm,
                        serialize(Horde_String::convertCharset($methods, 'UTF-8', $this->_params['charset'])),
                        $parent,
                        (int)$private,
                        $this->_tasklist,
                        $taskId);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return true;
    }

    /**
     * Moves a task to a different tasklist.
     *
     * @param string $taskId       The task to move.
     * @param string $newTasklist  The new tasklist.
     */
    function _move($taskId, $newTasklist)
    {
        $query = sprintf('UPDATE %s SET task_owner = ? WHERE task_owner = ? AND task_id = ?',
                         $this->_params['table']);
        $values = array($newTasklist, $this->_tasklist, $taskId);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return true;
    }

    /**
     * Deletes a task from the backend.
     *
     * @param string $taskId  The task to delete.
     */
    function _delete($taskId)
    {
        /* Get the task's details for use later. */
        $task = $this->get($taskId);

        $query = sprintf('DELETE FROM %s WHERE task_owner = ? AND task_id = ?',
                         $this->_params['table']);
        $values = array($this->_tasklist, $taskId);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return true;
    }

    /**
     * Deletes all tasks from the backend.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function deleteAll()
    {
        $query = sprintf('DELETE FROM %s WHERE task_owner = ?',
                         $this->_params['table']);
        $values = array($this->_tasklist);

        /* Attempt the delete query. */
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        return true;
    }

    /**
     * Retrieves tasks from the database.
     *
     * @param integer $completed  Which tasks to retrieve (1 = all tasks,
     *                            0 = incomplete tasks, 2 = complete tasks,
     *                            3 = future tasks, 4 = future and incomplete
     *                            tasks).
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function retrieve($completed = Nag::VIEW_ALL)
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
            return PEAR::raiseError($e->getMessage());
        }

        /* Store the retrieved values in a fresh task list. */
        $this->tasks = new Nag_Task();
        $dict = array();

        foreach ($result as $row) {
            $task = new Nag_Task($this->_buildTask($row));

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

        return true;
    }

    /**
     * Retrieves sub-tasks from the database.
     *
     * @param string $parentId  The parent id for the sub-tasks to retrieve.
     *
     * @return array  List of sub-tasks.
     */
    function getChildren($parentId)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE task_owner = ? AND task_parent = ?',
                         $this->_params['table']);
        $values = array($this->_tasklist, $parentId);

        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        /* Store the retrieved values in a fresh task list. */
        $tasks = array();
        foreach ($result as $row) {
            $task = new Nag_Task($this->_buildTask($row));
            $children = $this->getChildren($task->id);
            if ($children instanceof PEAR_Error) {
                return $children;
            }
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
     */
    function listAlarms($date)
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
            return PEAR::raiseError($e->getMessage());
        }

        $tasks = array();
        foreach ($result as $row) {
            $tasks[$row['task_id']] = new Nag_Task($this->_buildTask($row));
        }

        return $tasks;
    }

    /**
     */
    function _buildTask($row)
    {
        /* Make sure tasks always have a UID. */
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
            'category' => Horde_String::convertCharset($row['task_category'], $this->_params['charset'], 'UTF-8'),
            'start' => $row['task_start'],
            'due' => $row['task_due'],
            'priority' => $row['task_priority'],
            'estimate' => (float)$row['task_estimate'],
            'completed' => $row['task_completed'],
            'completed_date' => isset($row['task_completed_date']) ? $row['task_completed_date'] : null,
            'alarm' => $row['task_alarm'],
            'methods' => Horde_String::convertCharset(@unserialize($row['task_alarm_methods']), $this->_params['charset'], 'UTF-8'),
            'private' => $row['task_private']
        );
    }

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @return boolean  True on success, PEAR_Error on failure.
     */
    function initialize()
    {
        if (!isset($this->_params['table'])) {
            $this->_params['table'] = 'nag_tasks';
        }

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create('nag', 'storage');

        return true;
    }

}
