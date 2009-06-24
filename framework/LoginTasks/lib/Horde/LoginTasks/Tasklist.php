<?php
/**
 * The Horde_LoginTasks_Tasklist:: class is used to store the list of
 * login tasks that need to be run during this login.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_LoginTasks
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
     * Internal flag for addTask().
     *
     * @var boolean
     */
    protected $_addFlag = false;

    /**
     * Current task location pointer.
     *
     * @var integer
     */
    protected $_ptr = null;

    /**
     * Constructor.
     *
     * @param string $url  The target URL to redirect to when finished.
     */
    public function __construct($url)
    {
        $this->target = $url;
    }

    /**
     * Adds a task to the tasklist.
     *
     * @param Horde_LoginTasks_Task $task  The task to execute.
     */
    public function addTask($task)
    {
        $tmp = array(
            'display' => false,
            'task' => $task
        );

        if (($task->display == Horde_LoginTasks::DISPLAY_AGREE) ||
            ($task->display == Horde_LoginTasks::DISPLAY_NOTICE)) {
            $tmp['display'] = true;
            $this->_addFlag = false;
        } elseif (($task->display != Horde_LoginTasks::DISPLAY_NONE) &&
                  !$this->_addFlag) {
            $tmp['display'] = true;
            $this->_addFlag = true;
        }

        switch ($task->priority) {
        case Horde_LoginTasks::PRIORITY_HIGH:
            array_unshift($this->_tasks, $tmp);
            break;

        case Horde_LoginTasks::PRIORITY_NORMAL:
            $this->_tasks[] = $tmp;
            break;
        }
    }

    /**
     * Returns the list of tasks to perform.
     *
     * @return array  The list of tasks to perform.
     */
    public function ready()
    {
        $tmp = array();

        reset($this->_tasks);
        while (list($k, $v) = each($this->_tasks)) {
            if ($v['display'] &&
                (is_null($this->_ptr) || ($k > $this->_ptr))) {
                break;
            }
            $tmp[] = $v['task'];
        }

        if (!is_null($this->_ptr)) {
            $this->_tasks = array_slice($this->_tasks, $this->_ptr);
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
        $display = null;

        reset($this->_tasks);
        while (list($k, $v) = each($this->_tasks)) {
            if (!$v['display'] ||
                (!is_null($display) && ($v['task']->display != $display))) {
                break;
            }
            $tmp[] = $v['task'];
            $display = $v['task']->display;
        }

        if ($advance) {
            $this->_ptr = count($tmp);
        }

        return $tmp;
    }

}
