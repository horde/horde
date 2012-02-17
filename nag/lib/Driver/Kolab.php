<?php
/**
 * Nag driver classes for the Kolab IMAP server.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @package Nag
 */
class Nag_Driver_Kolab extends Nag_Driver
{
    /**
     * The Kolab_Storage backend.
     *
     * @var Horde_Kolab_Storage
     */
    protected $_kolab;

    /**
     * The current tasklist.
     *
     * @var Horde_Kolab_Storage_Data
     */
    private $_data;

    /**
     * Constructs a new Kolab storage object.
     *
     * @param string $tasklist  The tasklist to load.
     * @param array $params     A hash containing connection parameters.
     */
    public function __construct($tasklist, $params = array())
    {
        $this->_tasklist = $tasklist;
        $this->_kolab = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage');
    }

    /**
     * Return the Kolab data handler for the current tasklist.
     *
     * @return Horde_Kolab_Storage_Date The data handler.
     */
    private function _getData()
    {
        if (empty($this->_tasklist)) {
            throw new Nag_Exception(
                'The tasklist has been left undefined but is required!'
            );
        }
        if ($this->_data === null) {
            $this->_data = $this->_getDataForTasklist($this->_tasklist);
        }
        return $this->_data;
    }

    /**
     * Return the Kolab data handler for the specified tasklist.
     *
     * @param string $tasklist The tasklist name.
     *
     * @return Horde_Kolab_Storage_Date The data handler.
     */
    private function _getDataForTasklist($tasklist)
    {
        return $this->_kolab->getData(
            $GLOBALS['nag_shares']->getShare($tasklist)->get('folder'),
            'task'
        );
    }

    /**
     * Retrieves one task from the backend.
     *
     * @param string $taskId  The id of the task to retrieve.
     *
     * @return Nag_Task  A Nag_Task object.
     * @throws Horde_Exception_NotFound
     * @throws Nag_Exception
     */
    public function get($taskId)
    {
        if ($this->_getData()->objectIdExists($taskId)) {
            $task = $this->_getData()->getObject($taskId);
            $nag_task = $this->_buildTask($task);
            $nag_task['tasklist_id'] = $this->_tasklist;
            return new Nag_Task($nag_task);
        } else {
            throw new Horde_Exception_NotFound(_("Not Found"));
        }
    }

    /**
     * Build a task based a data array
     *
     * @param array  $task     The data for the task
     *
     * @return array  The converted data array representing the task
     */
    function _buildTask($task)
    {
        $task['task_id'] = $task['uid'];

        $task['category'] = $task['categories'];
        unset($task['categories']);

        $task['name'] = $task['summary'];
        unset($task['summary']);

        if (isset($task['due-date'])) {
            $task['due'] = $task['due-date'];
            unset($task['due-date']);
        }

        if (isset($task['start-date'])) {
            $task['start'] = $task['start-date'];
            unset($task['start-date']);
        }

        $task['desc'] = $task['body'];
        unset($task['body']);

        if (!empty($task['completed'])) {
            $task['completed'] = 1;
        } else {
            $task['completed'] = 0;
        }

        if ($task['sensitivity'] == 'public') {
            $task['private'] = false;
        } else {
            $task['private'] = true;
        }
        unset($task['sensitivity']);

        $share = $GLOBALS['nag_shares']->getShare($this->_tasklist);
        $task['owner'] = $share->get('owner');

        return $task;
    }


    /**
     * Retrieves one task from the database by UID.
     *
     * @param string $uid  The UID of the task to retrieve.
     *
     * @return array  The array of task attributes.
     */
    public function getByUID($uid)
    {
        return $this->_wrapper->getByUID($uid);
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
     * @throws Nag_Exception
     */
    protected function _add(
        $name, $desc, $start = 0, $due = 0, $priority = 0,
        $estimate = 0.0, $completed = 0, $category = '', $alarm = 0,
        array $methods = null, $uid = null, $parent = '', $private = false,
        $owner = null, $assignee = null
    ) {
        $object = $this->_getObject(
            $name,
            $desc,
            $start,
            $due,
            $priority,
            $estimate,
            $completed,
            $category,
            $alarm,
            $methods,
            $parent,
            $private,
            $owner,
            $assignee
        );
        if (is_null($uid)) {
            $object['uid'] = $this->_getData()->generateUid();
        } else {
            $object['uid'] = $uid;
        }
        $this->_getData()->create($object);
        return $object['uid'] = $uid;
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
     *
     * @return boolean  Indicates if the modification was successfull.
     */
    protected function _modify($taskId, $name, $desc, $start = 0, $due = 0,
                               $priority = 0, $estimate = 0.0, $completed = 0,
                               $category = '', $alarm = 0, $methods = null,
                               $parent = null, $private = false, $owner = null,
                               $assignee = null, $completed_date = null)
    {
        $object = $this->_getObject(
            $name,
            $desc,
            $start,
            $due,
            $priority,
            $estimate,
            $completed,
            $category,
            $alarm,
            $methods,
            $parent,
            $private,
            $owner,
            $assignee,
            $completed_date
        );
        $object['uid'] = $taskId;
        $this->_getData()->modify($object);
        return true;
    }

    /**
     * Retrieve the Kolab object representations for the task.
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
     * @param string $parent      The parent task id.
     * @param boolean $private    Whether the task is private.
     * @param string $owner       The owner of the event.
     * @param string $assignee    The assignee of the event.
     * @param integer $completed_date  The task's completion date.
     *
     * @return array The Kolab object.
     *
     * @throws Nag_Exception
     */
    private function _getObject(
        $name, $desc, $start = 0, $due = 0, $priority = 0,
        $estimate = 0.0, $completed = 0, $category = '', $alarm = 0,
        array $methods = null, $parent = '', $private = false, $owner = null,
        $assignee = null, $completed_date = null
    ) {
        $object = array(
            'summary' => $name,
            'body' => $desc,
            //@todo: Match Horde/Kolab priority values
            'priority' => $priority,
            //@todo: Extend to Kolab multiple categories (tagger)
            'categories' => $category,
            'parent' => $parent,
        );
        if ($start !== 0) {
            $object['start-date'] = $start;
        }
        if ($due !== 0) {
            $object['due-date'] = $due;
        }
        if ($completed) {
            $object['completed'] = 100;
            $object['status'] = 'completed';
        } else {
            $object['completed'] = 0;
            $object['status'] = 'not-started';
        }
        if ($alarm !== 0) {
            $object['alarm'] = $alarm;
        }
        if ($private) {
            $object['sensitivity'] = 'private';
        } else {
            $object['sensitivity'] = 'public';
        }
        if ($completed_date !== null) {
            $object['completed_date'] = $completed_date;
        }
        if ($estimate !== 0.0) {
            $object['estimate'] = number_format($estimate, 2);
        }
        if ($methods !== null) {
            $object['horde-alarm-methods'] = serialize($methods);
        }
        if ($owner !== null) {
            //@todo: Display name
            $object['creator'] = array(
                'smtp-address' => $owner,
            );
        }
        if ($assignee !== null) {
            //@todo: Display name
            $object['organizer'] = array(
                'smtp-address' => $assignee,
            );
        }
        return $object;
    }

    /**
     * Moves a task to a different tasklist.
     *
     * @param string $taskId       The task to move.
     * @param string $newTasklist  The new tasklist.
     */
    protected function _move($taskId, $newTasklist)
    {
        return $this->_getData()->move(
            $taskId,
            $GLOBALS['nag_shares']->getShare($newTasklist)->get('folder')
        );
    }

    /**
     * Deletes a task from the backend.
     *
     * @param string $taskId  The task to delete.
     */
    protected function _delete($taskId)
    {
        $this->_getData()->delete($taskId);
    }

    /**
     * Deletes all tasks from the backend.
     */
    public function deleteAll()
    {
        $this->_getData()->deleteAll();
    }

    /**
     * Retrieves tasks from the Kolab server.
     *
     * @param integer $completed  Which tasks to retrieve (1 = all tasks,
     *                            0 = incomplete tasks, 2 = complete tasks).
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function retrieve($completed = Nag::VIEW_ALL)
    {
        $dict = array();
        $this->tasks = new Nag_Task();

        $task_list = $this->_getData()->getObjects();
        if (empty($task_list)) {
            return true;
        }

        foreach ($task_list as $task) {
            $tuid = $task['uid'];
            $nag_task = $this->_buildTask($task);
            $nag_task['tasklist_id'] = $this->_tasklist;
            $t = new Nag_Task($nag_task);
            $complete = $t->completed;
            if (empty($t->start)) {
                $start = null;
            } else {
                $start = $t->start;
            }

            if (($completed == Nag::VIEW_INCOMPLETE && ($complete || $start > time())) ||
                ($completed == Nag::VIEW_COMPLETE && !$complete) ||
                ($completed == Nag::VIEW_FUTURE &&
                 ($complete || $start == 0 || $start < time())) ||
                ($completed == Nag::VIEW_FUTURE_INCOMPLETE && $complete)) {
                continue;
            }
            if (empty($t->parent_id)) {
                $this->tasks->add($t);
            } else {
                $dict[$tuid] = $t;
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
     * Lists all alarms near $date.
     *
     * @param integer $date  The unix epoch time to check for alarms.
     *
     * @return array  An array of tasks that have alarms that match.
     */
    public function listAlarms($date)
    {
        $task_list = $this->_getData()->getObjects();
        if (empty($task_list)) {
            return array();
        }

        $tasks = array();
        foreach ($task_list as $task) {
            $tuid = $task['uid'];
            $t = new Nag_Task($this->_buildTask($task));
            if ($t->alarm && $t->due &&
                $t->due - $t->alarm * 60 < $date) {
                $tasks[] = $t;
            }
        }

        return $tasks;
    }

    /**
     * Retrieves sub-tasks from the database.
     *
     * @param string $parentId  The parent id for the sub-tasks to retrieve.
     *
     * @return array  List of sub-tasks.
     */
    public function getChildren($parentId)
    {
        $task_list = $this->_getData()->getObjects();
        if (empty($task_list)) {
            return array();
        }

        $tasks = array();

        foreach ($task_list as $task) {
            if ($task['parent'] != $parentId) {
                continue;
            }
            $t = new Nag_Task($this->_buildTask($task));
            $children = $this->getChildren($t->id);
            $t->mergeChildren($children);
            $tasks[] = $t;
        }

        return $tasks;
    }
}
