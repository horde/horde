<?php
/**
 * The Horde_LoginTasks_Tasklist:: class is used to store the list of
 * login tasks that need to be run during this login.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  LoginTasks
 */
class Horde_LoginTasks_Tasklist
{
    /**
     * The URL of the web page to load after login tasks are complete.
     *
     * @var string
     */
    public $target;

    /**
     * Has this tasklist been processed yet?
     *
     * @var boolean
     */
    public $processed = false;

    /**
     * The list of tasks to run during this login.
     *
     * KEY: Task name
     * VALUE: array => (
     *   'display' => boolean,
     *   'task' => integer
     * )
     *
     * @var array
     */
    protected $_tasks = array();

    /**
     * The list of system tasks to run during this login.
     *
     * @see $_tasks
     *
     * @var array
     */
    protected $_stasks = array();

    /**
     * Current task location pointer.
     *
     * @var integer
     */
    protected $_ptr = 0;

    /**
     * Adds a task to the tasklist.
     *
     * @param Horde_LoginTasks_Task $task  The task to execute.
     */
    public function addTask($task)
    {
        if ($task instanceof Horde_LoginTasks_SystemTask) {
            $this->_stasks[] = $task;
        } else {
            switch ($task->priority) {
            case Horde_LoginTasks::PRIORITY_HIGH:
                array_unshift($this->_tasks, $task);
                break;

            case Horde_LoginTasks::PRIORITY_NORMAL:
                $this->_tasks[] = $task;
                break;
            }
        }
    }

    /**
     * Returns the list of tasks to perform.
     *
     * @param boolean $advance  If true, mark ready tasks as completed.
     *
     * @return array  The list of tasks to perform.
     */
    public function ready($advance = false)
    {
        $stasks = $tasks = array();

        /* Always loop through system tasks first. */
        foreach ($this->_stasks as $key => $val) {
            if (!$val->skip()) {
                $stasks[] = $val;
                unset($this->_stasks[$key]);
            }
        }

        reset($this->_tasks);
        while (list($k, $v) = each($this->_tasks)) {
            if ($v->needsDisplay() && ($k >= $this->_ptr)) {
                break;
            }
            $tasks[] = $v;
        }

        if ($advance) {
            $this->_tasks = array_slice($this->_tasks, count($tasks));
            $this->_ptr = 0;
        }

        return array_merge($stasks, $tasks);
    }

    /**
     * Returns the next batch of tasks that need display.
     *
     * @param boolean $advance  If true, advance the internal pointer.
     *
     * @return array  The list of tasks to display.
     */
    public function needDisplay($advance = false)
    {
        $tmp = array();
        $previous = null;

        reset($this->_tasks);
        while (list(, $v) = each($this->_tasks)) {
            if (!$v->needsDisplay() ||
                (!is_null($previous) && !$v->joinDisplayWith($previous))) {
                break;
            }
            $tmp[] = $v;
            $previous = $v;
        }

        if ($advance) {
            $this->_ptr = count($tmp);
        }

        return $tmp;
    }

    /**
     * Are all tasks complete?
     *
     * @return boolean  True if all tasks are complete.
     */
    public function isDone()
    {
        return (empty($this->_stasks) &&
                ($this->_ptr == count($this->_tasks)));
    }

}
