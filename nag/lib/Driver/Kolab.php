<?php
/**
 * Nag driver classes for the Kolab IMAP server.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
    protected $_kolab = null;

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
            throw new Mnemo_Exception(
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

        $task['desc'] = $task['body'];
        unset($task['body']);

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
        return $this->_wrapper->modify($taskId, $name, $desc, $start, $due,
                                       $priority, $estimate, $completed,
                                       $category, $alarm, $parent, $private,
                                       $owner, $assignee, $completed_date);
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
            'percentage' => $completed,
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
        if ($completed == 100) {
            $object['completed'] = 1;
        } else {
            $object['completed'] = 0;
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
            $object['completed-date'] = $completed_date;
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
        return $this->_wrapper->move($taskId, $newTasklist);
    }

    /**
     * Deletes a task from the backend.
     *
     * @param string $taskId  The task to delete.
     */
    protected function _delete($taskId)
    {
        return $this->_wrapper->delete($taskId);
    }

    /**
     * Deletes all tasks from the backend.
     */
    public function deleteAll()
    {
        return $this->_wrapper->deleteAll();
    }

    /**
     * Retrieves tasks from the Kolab server.
     *
     * @param integer $completed  Which tasks to retrieve (1 = all tasks,
     *                            0 = incomplete tasks, 2 = complete tasks).
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    public function retrieve($completed = 1)
    {
        $tasks = $this->_wrapper->retrieve($completed);
        if (is_a($tasks, 'PEAR_Error')) {
            return $tasks;
        }

        $this->tasks = $tasks;

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
        return $this->_wrapper->listAlarms($date);
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
        return $this->_wrapper->getChildren($parentId);
    }
}

/**
 * Horde Nag wrapper to distinguish between both Kolab driver implementations.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Nag
 */

class Nag_Driver_kolab_wrapper {

    /**
     * Indicates if the wrapper has connected or not
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * String containing the current tasklist name.
     *
     * @var string
     */
    var $_tasklist = '';

    /**
     * Our Kolab server connection.
     *
     * @var Kolab
     */
    var $_kolab = null;

    /**
     * Constructor
     *
     * @param string      $tasklist  The tasklist to load.
     * @param Horde_Kolab $kolab     The Kolab connection object
     */
    function Nag_Driver_kolab_wrapper($tasklist, $kolab)
    {
        $this->_tasklist = $tasklist;
        $this->_kolab = $kolab;
    }

    /**
     * Connect to the Kolab backend
     *
     * @param int    $loader         The version of the XML
     *                               loader
     *
     * @return mixed True on success, a PEAR error otherwise
     */
    function connect($loader = 0)
    {
        if ($this->_connected) {
            return true;
        }

        $result = $this->_kolab->open($this->_tasklist, $loader);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->_connected = true;

        return true;
    }
}

/**
 * New Nag driver for the Kolab IMAP server.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Nag
 */
class Nag_Driver_kolab_wrapper_new
{

    /**
     * Shortcut to the imap connection
     *
     * @var Kolab_IMAP
     */
    var $_store = null;

    /**
     * Connect to the Kolab backend
     *
     * @return mixed True on success, a PEAR error otherwise
     */
    function connect()
    {
        if ($this->_connected) {
            return true;
        }

        $result = parent::connect(1);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->_store = $this->_kolab->_storage;

        return true;
    }

    /**
     * Split the tasklist name of the id. We use this to make ids
     * unique across folders.
     *
     * @param string $id The ID of the task appended with the tasklist
     *                   name.
     *
     * @return array  The task id and tasklist name
     */
    function _splitId($id)
    {
        $split = explode('@', $id, 2);
        if (count($split) == 2) {
            list($id, $tasklist) = $split;
        } else if (count($split) == 1) {
            $tasklist = $GLOBALS['registry']->getAuth();
        }
        return array($id, $tasklist);
    }

    /**
     * Append the tasklist name to the id. We use this to make ids
     * unique across folders.
     *
     * @param string $id The ID of the task
     *
     * @return string  The task id appended with the tasklist
     *                 name.
     */
    function _uniqueId($id)
    {
        if ($this->_tasklist == $GLOBALS['registry']->getAuth()) {
            return $id;
        }
        return $id . '@' . $this->_tasklist;
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
        list($taskId, $tasklist) = $this->_splitId($uid);

        if ($this->_tasklist != $tasklist) {
            $this->_tasklist = $tasklist;
            $this->_connected = false;
            $this->connect();
        }

        return $this->get($taskId);
    }

    /**
     * Add or modify a task.
     *
     * @param string $name             The name (short) of the task.
     * @param string $desc             The description (long) of the task.
     * @param integer $start           The start date of the task.
     * @param integer $due             The due date of the task.
     * @param integer $priority        The priority of the task.
     * @param float $estimate          The estimated time to complete the task.
     * @param integer $completed       The completion state of the task.
     * @param string $category         The category of the task.
     * @param integer $alarm           The alarm associated with the task.
     * @param string $uid              A Unique Identifier for the task.
     * @param string $parent           The parent task id.
     * @param boolean $private         Whether the task is private.
     * @param string $owner            The owner of the event.
     * @param string $assignee         The assignee of the event.
     * @param integer $completed_date  The task's completion date.
     *
     * @return mixed The id of the task if successful, a PEAR error
     * otherwise
     */
    function _setObject($name, $desc, $start = 0, $due = 0, $priority = 0,
                        $estimate = 0.0, $completed = 0, $category = '',
                        $alarm = 0, $uid = null, $parent = null,
                        $private = false, $owner = null, $assignee = null,
                        $completed_date = null)
    {
        if (empty($uid)) {
            $task_uid = strval(new Horde_Support_Guid());
            $old_uid = null;
        } else {
            list($task_uid, $tasklist) = $this->_splitId($uid);
            $old_uid = $task_uid;
        }

        if ($parent) {
            list($parent,) = $this->_splitId($parent);
        }

        if ($private) {
            $sensitivity = 'private';
        } else {
            $sensitivity = 'public';
        }

        $result = $this->_store->save(array(
                                          'uid' => $task_uid,
                                          'name' => $name,
                                          'body' => $desc,
                                          'start' => $start,
                                          'due' => $due,
                                          'priority' => $priority,
                                          'completed' => $completed,
                                          'categories' => $category,
                                          'alarm' => $alarm,
                                          'parent' => $parent,
                                          'sensitivity' => $sensitivity,
                                          'estimate' => $estimate,
                                          'completed_date' => $completed_date,
                                          'creator' => array(
                                              'smtp-address' => $owner,
                                          ),
                                          'organizer' => array(
                                              'smtp-address' => $assignee,
                                          ),
                                      ),
                                      $old_uid);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $task_uid;
    }

    /**
     * Adds a task to the backend storage.
     *
     * @param string $name             The name (short) of the task.
     * @param string $desc             The description (long) of the task.
     * @param integer $start           The start date of the task.
     * @param integer $due             The due date of the task.
     * @param integer $priority        The priority of the task.
     * @param float $estimate          The estimated time to complete the task.
     * @param integer $completed       The completion state of the task.
     * @param string $category         The category of the task.
     * @param integer $alarm           The alarm associated with the task.
     * @param string $uid              A Unique Identifier for the task.
     * @param string $parent           The parent task id.
     * @param boolean $private         Whether the task is private.
     * @param string $owner            The owner of the event.
     * @param string $assignee         The assignee of the event.
     *
     * @return mixed The id of the task if successful, a PEAR error
     * otherwise
     */
    function add($name, $desc, $start = 0, $due = 0, $priority = 0,
                 $estimate = 0.0, $completed = 0, $category = '', $alarm = 0,
                 $uid = null, $parent = null, $private = false, $owner = null,
                 $assignee = null)
    {
        return $this->_setObject($name, $desc, $start, $due, $priority,
                                 $estimate, $completed, $category, $alarm,
                                 null, $parent, $private, $owner, $assignee);
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
     * @param string $parent           The parent task id.
     * @param boolean $private         Whether the task is private.
     * @param string $owner            The owner of the event.
     * @param string $assignee         The assignee of the event.
     * @param integer $completed_date  The task's completion date.
     *
     * @return mixed The id of the task if successful, a PEAR error
     * otherwise
     */
    function modify($taskId, $name, $desc, $start = 0, $due = 0, $priority = 0,
                    $estimate = 0.0, $completed = 0, $category = '',
                    $alarm = 0, $parent = null, $private = false,
                    $owner = null, $assignee = null, $completed_date = null)
    {
        $result = $this->_setObject($name, $desc, $start, $due, $priority,
                                    $estimate, $completed, $category, $alarm,
                                    $taskId, $parent, $private, $owner, $assignee,
                                    $completed_date);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $result == $taskId;
    }

    /**
     * Moves a task to a different tasklist.
     *
     * @param string $taskId       The task to move.
     * @param string $newTasklist  The new tasklist.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function move($taskId, $newTasklist)
    {
        list($taskId, $tasklist) = $this->_splitId($taskId);

        return $this->_store->move($taskId, $newTasklist);
    }

    /**
     * Deletes a task from the backend.
     *
     * @param string $taskId  The task to delete.
     */
    function delete($taskId)
    {
        list($taskId, $tasklist) = $this->_splitId($taskId);

        return $this->_store->delete($taskId);
    }

    /**
     * Deletes all tasks from the backend.
     */
    function deleteAll()
    {
        return $this->_store->deleteAll();
    }

    /**
     * Retrieves tasks from the Kolab server.
     *
     * @param integer $completed  Which tasks to retrieve (1 = all tasks,
     *                            0 = incomplete tasks, 2 = complete tasks,
     *                            3 = future tasks, 4 = future and incomplete
     *                            tasks).
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function retrieve($completed = 1)
    {
        $dict = array();
        $tasks = new Nag_Task();

        $task_list = $this->_store->getObjects();
        if (is_a($task_list, 'PEAR_Error')) {
            return $task_list;
        }

        if (empty($task_list)) {
            return $tasks;
        }

        foreach ($task_list as $task) {
            $tuid = $this->_uniqueId($task['uid']);
            $t = new Nag_Task($this->_buildTask($task));
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
                $tasks->add($t);
            } else {
                $dict[$tuid] = $t;
            }
        }

        /* Build a tree from the subtasks. */
        foreach (array_keys($dict) as $key) {
            $task = $tasks->get($dict[$key]->parent_id);
            if ($task) {
                $task->add($dict[$key]);
            } elseif (isset($dict[$dict[$key]->parent_id])) {
                $dict[$dict[$key]->parent_id]->add($dict[$key]);
            } else {
                $tasks->add($dict[$key]);
            }
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
        $task_list = $this->_store->getObjects();
        if ($task_list instanceof PEAR_Error) {
            return $task_list;
        }

        if (empty($task_list)) {
            return array();
        }

        $tasks = array();
        foreach ($task_list as $task) {
            $tuid = $this->_uniqueId($task['uid']);
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
    function getChildren($parentId)
    {
        list($parentId, $tasklist) = $this->_splitId($parentId);

        $task_list = $this->_store->getObjects();
        if (is_a($task_list, 'PEAR_Error')) {
            return $task_list;
        }

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
            if (is_a($children, 'PEAR_Error')) {
                return $children;
            }
            $t->mergeChildren($children);
            $tasks[] = $t;
        }

        return $tasks;
    }
}
