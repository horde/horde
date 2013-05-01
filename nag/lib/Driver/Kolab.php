<?php
/**
 * Nag driver classes for the Kolab IMAP server.
 *
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
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
    protected $_data;

    /**
     * Constructs a new Kolab storage object.
     *
     * @param string $tasklist  The tasklist to load.
     * @param array $params     A hash containing connection parameters.
     */
    public function __construct($tasklist, $params = array())
    {
        $this->_tasklist = $tasklist;
        $this->_kolab = $params['kolab'];
    }

    /**
     * Return the Kolab data handler for the current tasklist.
     *
     * @return Horde_Kolab_Storage_Data The data handler.
     */
    protected function _getData()
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
    protected function _getDataForTasklist($tasklist)
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
        $uid = Horde_Url::uriB64Decode($taskId);
        if (!$this->_getData()->objectIdExists($uid)) {
            throw new Horde_Exception_NotFound();
        }
        $task = $this->_getData()->getObject($uid)->getData();
        $nag_task = $this->_buildTask($task);
        $nag_task['tasklist_id'] = $this->_tasklist;
        return new Nag_Task($this, $nag_task);
    }

    /**
     * Build a task based a data array
     *
     * @param array  $task     The data for the task
     *
     * @return array  The converted data array representing the task
     */
    protected function _buildTask($task)
    {
        $result = array(
            'task_id' => Horde_Url::uriB64Encode($task['uid']),
            'uid' => $task['uid'],
            'name' => $task['summary'],
            'desc' => $task['body'],
            'priority' => $task['priority'],
            'parent' => $task['parent'],
            'alarm' => $task['alarm'],
            'completed' => !empty($task['completed']),
            'completed_date' => $task['completed_date'],
            'private' => $task['sensitivity'] != 'public',
            'owner' => $GLOBALS['nag_shares']->getShare($this->_tasklist)->get('owner'),
        );

        if (isset($task['categories'])) {
            $result['internaltags'] = $task['categories'];
        }

        if (!empty($task['start-date'])) {
            $result['start'] = $task['start-date']->format('U');
        }
        if (!empty($task['due-date'])) {
            $result['due'] = $task['due-date']->format('U');
        }
        if (!empty($task['creation-date'])) {
            $result['created'] = new Horde_Date($task['creation-date']);
        }
        if (!empty($task['last-modification-date'])) {
            $result['modified'] = new Horde_Date($task['last-modification-date']);
        }

        if (isset($task['recurrence']) && isset($task['due-date'])) {
            $recurrence = new Horde_Date_Recurrence($task['due-date']);
            $recurrence->fromKolab($task['recurrence']);
            $result['recurrence'] = $recurrence;
        }

        if (isset($task['organizer'])) {
            $result['assignee'] = $task['organizer']['smtp-address'];
        }

        if (isset($task['horde-estimate'])) {
            $result['estimate'] = $task['horde-estimate'];
        }
        if (isset($task['horde-alarm-methods'])) {
            $result['methods'] = @unserialize($task['horde-alarm-methods']);
        }

        return $result;
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
        foreach (array_keys(Nag::listTasklists(false, Horde_Perms::READ, false)) as $tasklist) {
            $this->_tasklist = $tasklist;
            try {
                return $this->get(Horde_Url::uriB64Encode($uid));
            } catch (Horde_Exception_NotFound $e) {
            }
        }
        throw new Horde_Exception_NotFound();
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
     *     - tags: (OPTIONAL, string) The tags of the task.
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
        $object = $this->_getObject($task);
        $object['uid'] = $this->_getData()->generateUid(); 
        try {
            $this->_getData()->create($object);
            $this->_addTags($task);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Nag_Exception($e);
        }
        return Horde_Url::uriB64Encode($object['uid']);
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
     *     - recurrence: (OPTIONAL, Horde_Date_Recurrence|array) Recurrence
     *                   information.
     */
    protected function _modify($taskId, array $task)
    {
        $object = $this->_getObject($task);
        $object['uid'] = Horde_Url::uriB64Decode($taskId);
        try {
            $this->_getData()->modify($object);
            $this->_updateTags($task);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Nag_Exception($e);
        }
    }

    /**
     * Retrieve the Kolab object representations for the task.
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
     *     - recurrence: (OPTIONAL, Horde_Date_Recurrence|array) Recurrence
     *                   information.
     *
     * @return array The Kolab object.
     */
    protected function _getObject($task)
    {
        $object = array(
            'summary' => $task['name'],
            'body' => $task['desc'],
            //@todo: Match Horde/Kolab priority values
            'priority' => $task['priority'],
            'parent' => $task['parent'],
        );
        if (!empty($task['start'])) {
            $object['start-date'] = new DateTime('@' . $task['start']);
        }
        if (!empty($task['due'])) {
            $object['due-date'] = new DateTime('@' . $task['due']);
        }
        if ($task['recurrence']) {
            $object['recurrence'] = $task['recurrence']->toKolab();
        }
        if ($task['completed']) {
            $object['completed'] = 100;
            $object['status'] = 'completed';
        } else {
            $object['completed'] = 0;
            $object['status'] = 'not-started';
        }
        if ($task['alarm'] !== 0) {
            $object['alarm'] = (int)$task['alarm'];
        }
        if ($task['private']) {
            $object['sensitivity'] = 'private';
        } else {
            $object['sensitivity'] = 'public';
        }
        if (isset($task['completed_date'])) {
            $object['completed_date'] = $task['completed_date'];
        }
        if ($task['estimate'] !== 0.0) {
            $object['horde-estimate'] = number_format((float)$task['estimate'], 2);
        }
        if ($task['methods'] !== null) {
            $object['horde-alarm-methods'] = serialize($task['methods']);
        }
        if ($task['owner'] !== null) {
            //@todo: Display name
            $object['creator'] = array(
                'smtp-address' => $task['owner'],
            );
        }
        if ($task['assignee'] !== null) {
            //@todo: Display name
            $object['organizer'] = array(
                'smtp-address' => $task['assignee'],
            );
        }
        if ($task['tags'] && !is_array($task['tags'])) {
            $object['categories'] = $GLOBALS['injector']->getInstance('Nag_Tagger')->split($task['tags']);
            usort($object['categories'], 'strcoll');
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
        $this->_getData()->move(
            Horde_Url::uriB64Decode($taskId),
            $GLOBALS['nag_shares']->getShare($newTasklist)->get('folder')
        );
        $this->_getDataForTasklist($newTasklist)->synchronize();
    }

    /**
     * Deletes a task from the backend.
     *
     * @param string $taskId  The task to delete.
     */
    protected function _delete($taskId)
    {
        $this->_getData()->delete(Horde_Url::uriB64Decode($taskId));
    }

    /**
     * Deletes all tasks from the backend.
     *
     * @return array  An array of ids that have been deleted.
     */
    protected function _deleteAll()
    {
        $this->retrieve();
        $this->tasks->reset();
        $ids = array();
        while ($task = $this->tasks->each()) {
            $ids[] = $task->id;
        }
        $this->_getData()->deleteAll();

        return $ids;
    }

    /**
     * Retrieves tasks from the Kolab server.
     *
     * @param integer $completed  Which tasks to retrieve (1 = all tasks,
     *                            0 = incomplete tasks, 2 = complete tasks).
     */
    public function retrieve($completed = Nag::VIEW_ALL)
    {
        $dict = array();
        $this->tasks = new Nag_Task();

        $task_list = $this->_getData()->getObjects();
        if (empty($task_list)) {
            return;
        }

        foreach ($task_list as $task) {
            $tid = Horde_Url::uriB64Encode($task['uid']);
            $nag_task = $this->_buildTask($task);
            $nag_task['tasklist_id'] = $this->_tasklist;
            $t = new Nag_Task($this, $nag_task);
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
                $dict[$tid] = $t;
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
     * Lists all alarms near $date.
     *
     * @param integer $date  The unix epoch time to check for alarms.
     *
     * @return array  An array of Nag_Task objects that have alarms that match.
     */
    public function listAlarms($date)
    {
        $task_list = $this->_getData()->getObjects();
        if (empty($task_list)) {
            return array();
        }

        $tasks = array();
        foreach ($task_list as $task) {
            $t = new Nag_Task($this, $this->_buildTask($task));
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
          if (Horde_Url::uriB64Encode($task['parent']) != $parentId) {
                continue;
            }
            $t = new Nag_Task($this, $this->_buildTask($task));
            $children = $this->getChildren($t->id);
            $t->mergeChildren($children);
            $tasks[] = $t;
        }

        return $tasks;
    }
}
