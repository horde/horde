<?php
/**
 * This file contains all Horde_Form extensions required for editing tasks.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Nag
 */

/**
 * The Nag_TaskForm class provides the form for adding and editing a task.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag_Form_Task extends Horde_Form
{
    public $delete;

    public function __construct($vars, $title = '', $delete = false)
    {
        parent::__construct($vars, $title);
        $this->delete = $delete;
        $tasklists = Nag::listTasklists(false, Horde_Perms::EDIT);
        $tasklist_enums = array();
        foreach ($tasklists as $tl_id => $tl) {
            if ($tl->get('owner') != $GLOBALS['registry']->getAuth() &&
                !empty($GLOBALS['conf']['share']['hidden']) &&
                !in_array($tl->getName(), $GLOBALS['display_tasklists'])) {
                continue;
            }
            $tasklist_enums[$tl_id] = $tl->get('name');
        }
        $tasklist = $vars->get('tasklist_id');
        if (empty($tasklist)) {
            reset($tasklist_enums);
            $tasklist = key($tasklist_enums);
        }
        $tasks = Nag::listTasks(null, null, null, array($tasklist), Nag::VIEW_FUTURE_INCOMPLETE);
        $task_enums = array('' => _("No parent task"));
        $tasks->reset();
        while ($task = $tasks->each()) {
            if ($vars->get('task_id') == $task->id) {
                continue;
            }
            $task_enums[htmlspecialchars($task->id)] = str_repeat('&nbsp;', $task->indent * 4) . htmlentities($task->name);
        }
        $users = array();
        $share = $GLOBALS['nag_shares']->getShare($tasklist);
        $users = $share->listUsers(Horde_Perms::READ);
        $groups = $share->listGroups(Horde_Perms::READ);
        if (count($groups)) {
            $horde_group = $GLOBALS['injector']->getInstance('Horde_Group');
            foreach ($groups as $group) {
                $users = array_merge($users,
                                     $horde_group->listAllUsers($group));
            }
        }
        $users = array_flip($users);

        if (count($users)) {
            foreach (array_keys($users) as $user) {
                $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);
                $fullname = $identity->getValue('fullname');
                $users[$user] = strlen($fullname) ? $fullname : $user;
            }
        }
        $priorities = array(
            1 => '1 ' . _("(highest)"),
            2 => 2,
            3 => 3,
            4 => 4,
            5 => '5 ' . _("(lowest)")
        );
        $this->addHidden('', 'actionID', 'text', true);
        $this->addHidden('', 'task_id', 'text', false);
        $this->addHidden('', 'old_tasklist', 'text', false);
        $this->addHidden('', 'url', 'text', false);
        $this->addVariable(_("Name"), 'name', 'text', true);
        if (!$GLOBALS['prefs']->isLocked('default_tasklist') &&
            count($tasklist_enums) > 1) {
            $v = $this->addVariable(
                _("Task List"), 'tasklist_id', 'enum', true, false, false,
                array($tasklist_enums));
            $v->setAction(Horde_Form_Action::factory('reload'));
        }
        $v = $this->addVariable(
            _("Parent task"), 'parent', 'enum', false, false, false, array($task_enums));
        $v->setOption('htmlchars', true);

        if (class_exists('Horde_Form_Type_category')) {
            $this->addVariable(_("Category"), 'category', 'category', false);
        } else {
            $values = Horde_Array::valuesToKeys(Horde_Prefs_CategoryManager::get());
            $this->addVariable(
                _("Category"), 'category', 'enum', false, false, false,
                array($values, _("Unfiled")));
        }

        $this->addVariable(_("Assignee"), 'assignee', 'enum', false, false,
                           null, array($users, _("None")));
        $this->addVariable(_("Private?"), 'private', 'boolean', false);
        $this->addVariable(_("Due By"), 'due', 'Nag:NagDue', false);
        $this->addVariable(_("Delay Start Until"), 'start', 'Nag:NagStart', false);
        $this->addVariable(_("Alarm"), 'alarm', 'Nag:NagAlarm', false);

        $v = $this->addVariable(_("Notification"), 'methods', 'Nag:NagMethod', false);
        $v->setAction(Horde_Form_Action::factory('reload'));

        $v = $this->addVariable(_("Priority"), 'priority', 'enum', false, false, false, array($priorities));
        $v->setDefault(3);

        $this->addVariable(_("Estimated Time"), 'estimate', 'number', false);
        $this->addVariable(_("Completed?"), 'completed', 'boolean', false);

        try {
            $description = Horde::callHook('description_help', array(), 'nag');
        } catch (Horde_Exception_HookNotSet $e) {
            $description = '';
        }
        $this->addVariable(_("Description"), 'desc', 'longtext', false, false, $description);

        $buttons = array(_("Save"));
        if ($delete) {
            $buttons[] = _("Delete this task");
        }
        $this->setButtons($buttons);
    }

    public function renderActive()
    {
        return parent::renderActive(new Nag_Form_Renderer_Task(array('varrenderer_driver' => array('nag', 'nag')), $this->delete), $this->_vars, 'task.php', 'post');
    }

}
