<?php
/**
 * Nag_Driver:: defines an API for implementing storage backends for Nag.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag_Driver
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
     * Attempts to return a concrete Nag_Driver instance based on $driver.
     *
     * @param string    $tasklist   The name of the tasklist to load.
     *
     * @param string    $driver     The type of concrete Nag_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Nag_Driver instance, or
     *                  false on an error.
     */
    static public function factory($tasklist = '', $driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = ucfirst(basename($driver));
        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }
        $class = 'Nag_Driver_' . $driver;
        if (class_exists($class)) {
            try {
                $nag = new $class($tasklist, $params);
            } catch (Nag_Exception $e) {
                $nag = new Nag_Driver($params, sprintf(_("The Tasks backend is not currently available: %s"), $e->getMessage()));
            }
        } else {
            $nag = new Nag_Driver($params, sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $nag;
    }

    /**
     * Attempts to return a reference to a concrete Nag_Driver
     * instance based on $driver. It will only create a new instance
     * if no Nag_Driver instance with the same parameters currently
     * exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var =& Nag_Driver::singleton()
     *
     * @param string    $tasklist   The name of the tasklist to load.
     *
     * @param string    $driver     The type of concrete Nag_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The created concrete Nag_Driver instance, or false
     *                  on error.
     */
    static public function &singleton($tasklist = '', $driver = null, array $params = null)
    {
        static $instances = array();

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $signature = serialize(array($tasklist, $driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] =& Nag_Driver::factory($tasklist, $driver, $params);
        }

        return $instances[$signature];
    }

    /**
     * Adds a task and handles notification.
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
     * @param string $parent      The parent task.
     * @param boolean $private    Whether the task is private.
     * @param string $owner       The owner of the event.
     * @param string $assignee    The assignee of the event.
     *
     * @return array  array(ID,UID) of new task
     */
    public function add($name, $desc, $start = 0, $due = 0, $priority = 0,
                 $estimate = 0.0, $completed = 0, $category = '', $alarm = 0,
                 array $methods = null, $uid = null, $parent = '', $private = false,
                 $owner = null, $assignee = null)
    {
        if (is_null($uid)) {
            $uid = strval(new Horde_Support_Guid());
        }
        if (is_null($owner)) {
            $owner = $GLOBALS['registry']->getAuth();
        }

        $taskId = $this->_add($name, $desc, $start, $due, $priority, $estimate,
                              $completed, $category, $alarm, $methods, $uid,
                              $parent, $private, $owner, $assignee);

        $task = $this->get($taskId);
        $task->process();

        /* Log the creation of this item in the history log. */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        try {
            $history->log('nag:' . $this->_tasklist . ':' . $uid, array('action' => 'add'), true);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        /* Log completion status changes. */
        if ($completed) {
            try {
                $history->log('nag:' . $this->_tasklist . ':' . $uid, array('action' => 'complete'), true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Notify users about the new event. */
        $result = Nag::sendNotification('add', $task);

        /* Add an alarm if necessary. */
        if (!empty($alarm) &&
            ($alarm = $task->toAlarm())) {
            $alarm['start'] = new Horde_Date($alarm['start']);
            $GLOBALS['injector']->getInstance('Horde_Alarm')->set($alarm);
        }

        return array($taskId, $uid);
    }

    /**
     * Modifies an existing task and handles notification.
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
     * @param string $parent           The parent task.
     * @param boolean $private         Whether the task is private.
     * @param string $owner            The owner of the event.
     * @param string $assignee         The assignee of the event.
     * @param integer $completed_date  The task's completion date.
     * @param string $tasklist         The new tasklist.
     *
     * @throws Nag_Exception
     */
    public function modify($taskId, $name, $desc, $start = 0, $due = 0, $priority = 0,
                    $estimate = 0.0, $completed = 0, $category = '',
                    $alarm = 0, array $methods = null, $parent = '', $private = false,
                    $owner = null, $assignee = null, $completed_date = null,
                    $tasklist = null)
    {
        /* Retrieve unmodified task. */
        $task = $this->get($taskId);

        /* Avoid circular reference. */
        if ($parent == $taskId) {
            $parent = '';
        }
        $modify = $this->_modify($taskId, $name, $desc, $start, $due,
                                 $priority, $estimate, $completed, $category,
                                 $alarm, $methods, $parent, $private, $owner,
                                 $assignee, $completed_date);

        $new_task = $this->get($task->id);
        $log_tasklist = $this->_tasklist;
        if (!is_null($tasklist) && $task->tasklist != $tasklist) {
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
                $share = $GLOBALS['nag_shares']->getShare($tasklist);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e->getMessage(), 'ERR');
                throw new Nag_Exception($e);
            }

            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $GLOBALS['notification']->push(sprintf(_("Access denied moving the task to %s."), $share->get('name')), 'horde.error');
            }

            $moved = $this->_move($task->id, $tasklist);
            $new_storage = Nag_Driver::singleton($tasklist);
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
                    $history->log('nag:' . $tasklist . ':' . $task->uid, array('action' => 'add'), true);
                } catch (Exception $e) {
                    Horde::logMessage($e, 'ERR');
                }
                $log_tasklist = $tasklist;
            }
        }

        /* Update alarm if necessary. */
        $horde_alarm = $GLOBALS['injector']->getInstance('Horde_Alarm');
        if (empty($alarm) || $completed) {
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
                $GLOBALS['injector']->getInstance('Horde_History')->log('nag:' . $log_tasklist . ':' . $task->uid, array('action' => 'modify'), true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Log completion status changes. */
        if ($task->completed != $completed) {
            $attributes = array('action' => 'complete');
            if (!$completed) {
                $attributes['ts'] = 0;
            }
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('nag:' . $log_tasklist . ':' . $task->uid, $attributes, true);
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

        return true;
    }

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

}
