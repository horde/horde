<?php
/**
 * The Horde_LoginTasks_Tasklist:: class is used to store the list of
 * login tasks that need to be run during this login.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
        $tmp = array();

        /* Always loop through system tasks first. */
        foreach ($this->_stasks as $key => $val) {
            if (!$val->skip()) {
                $tmp[] = $val;
                unset($this->_stasks[$key]);
            }
        }

        reset($this->_tasks);
        while (list($k, $v) = each($this->_tasks)) {
            if ($v->needsDisplay() && ($k >= $this->_ptr)) {
                break;
            }
            $tmp[] = $v;
        }

        if ($advance) {
            $this->_tasks = array_slice($this->_tasks, count($tmp));
            $this->_ptr = 0;
        }

        return $tmp;
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
        while (list($k, $v) = each($this->_tasks)) {
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
