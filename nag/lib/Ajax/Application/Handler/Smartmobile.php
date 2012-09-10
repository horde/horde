<?php
/**
 * Defines AJAX calls used exclusively in the smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class Nag_Ajax_Application_Handler_Smartmobile extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Toggle the completed flag.
     *
     * Variables used:
     *   - task: TODO
     *   - tasklist: TODO
     *
     * @return array  TODO
     */
    public function smartmobileToggle()
    {
        $out = new stdClass;

        if (!isset($this->vars->task) || !isset($this->vars->tasklist)) {
            $out->error = 'missing parameters';
        } else {
            $nag_task = new Nag_CompleteTask();
            $out = (object)$nag_task->result($this->vars->task, $this->vars->tasklist);
        }

        return $out;
    }

    public function getTaskLists()
    {
        $lists = Nag::listTasklists();
        $results = array();
        foreach ($lists as $name => $list) {
            $results[$name] = $this->_listToHash($list);
        }
        $return = new stdClass;
        $return->tasklists = $results;

        return $return;
    }

    /**
     * @TODO: Should probably refactor to encapsulate the share object with a
     * Nag_Tasklist object in the future.
     *
     */
    protected function _listToHash($list)
    {
        $tasks = Nag::listTasks(array('tasklists' => $list->getName()));

        $hash = array(
            'name' => $list->get('name'),
            'desc' => $list->get('desc'),
            'owner' => $list->get('owner'),
            'id' => $list->getName(),
            'count' => $tasks->count(),
            'overdue' => $tasks->childrenOverdue());

        return $hash;
    }

    /**
     * AJAX action: Return a task list.
     *
     * @return stdClass  An object containing a tasklist in the tasks property.
     */
    public function listTasks()
    {
        if ($this->vars->tasklist) {
            $options = array('tasklists' => array($this->vars->tasklist));
        } else {
            $options = array();
        }

        $tasks = Nag::listTasks($options);
        $list = array();
        $tasks->reset();
        while ($task = $tasks->each()) {
            $list[] = $task->toJson(true);
        }
        $results = new stdClass;
        $results->tasks = $list;

        return $results;
    }

    public function getTask()
    {
        $out = new StdClass;
        if (!isset($this->vars->task) || !isset($this->vars->tasklist)) {
            $out->error = 'Missing Parameters';
        } else {
            $task = Nag::getTask($this->vars->tasklist, $this->vars->task);
            $out->task = $task->toJson(true);
        }

        return $out;
    }
}
