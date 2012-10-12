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
            $tasklist  = new Nag_Tasklist($list);
            $results[$name] = $tasklist->toHash();
        }
        $return = new stdClass;
        $return->tasklists = $results;

        return $return;
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

    public function deleteTask()
    {
        if (!$this->vars->task_id) {
            $GLOBALS['notification']->push(_("Missing required task id"), 'horde.error');
            return;
        }

        $results = new stdClass();
        $storage = $GLOBALS['injector']
            ->getInstance('Nag_Factory_Driver')
            ->create();
        $task = $storage->get($this->vars->task_id);
        $share = $GLOBALS['nag_shares']->getShare($task->tasklist);
        if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
            $GLOBALS['notification']->push(_("You are not allowed to delete this task."), 'horde.error');
            return;
        }
        $storage->delete($this->vars->task_id);
        $GLOBALS['notification']->push(_("Successfully deleted"), 'horde.success');
        $results->deleted = $this->vars->task_id;
        return $results;
    }

    public function saveTask()
    {
        $results = new stdClass();
        $task = array(
            'name' => $this->vars->task_title,
            'desc' => $this->vars->task_desc,
            'assignee' => $this->vars->task_assignee,
            'priority' => $this->vars->task_priority,
            'owner' => $GLOBALS['registry']->getAuth()
        );

        if ($this->vars->task_private) {
            $task['private'] = true;
        }

        if ($this->vars->task_start) {
            $date = new Horde_Date($this->vars->task_start);
            $task['start'] = $date->timestamp();
        }

        if ($this->vars->task_due) {
            $date = new Horde_Date($this->vars->task_due);
            $task['due'] = $date->timestamp();
        }

        if ($this->vars->task_estimate) {
            $task['estimate'] = $this->vars->task_estimate;
        }

        if ($this->vars->task_completed) {
            $task['completed'] = true;
        }

        if ($this->vars->tasklist) {
            $tasklist = $this->vars->tasklist;
        } else {
            $tasklist = $GLOBALS['prefs']->getValue('default_tasklist');
        }
        try {
            $storage = $GLOBALS['injector']
                ->getInstance('Nag_Factory_Driver')
                ->create($tasklist);
        } catch (Nag_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return $results;
        }

        if ($this->vars->task_id) {
            // Existing task
            try {
                $existing_task = $storage->get($this->vars->task_id);
                $existing_task->merge($task);
                $existing_task->save();
                $results->task = $existing_task->toJson(true);
            } catch (Nag_Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $results;
            }
        } else {
            try {
                $ids = $storage->add($task);
                $results->task = $storage->get($ids[0])->toJson(true);
            } catch (Nag_Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $results;
            }
        }

        $GLOBALS['notification']->push(sprintf(_("Saved %s"), $task['name']), 'horde.success');
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
