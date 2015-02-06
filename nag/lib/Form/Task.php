<?php
/**
 * This file contains all Horde_Form extensions required for editing tasks.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Nag
 */

/**
 * The Nag_Form_Task class provides the form for adding and editing a task.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag_Form_Task extends Horde_Form
{
    const SECTION_GENERAL = 1;
    const SECTION_RECUR   = 2;
    const SECTION_DESC    = 3;

    /**
     * Const'r
     *
     * @param Horde_Form_Variables $vars  The form variables.
     * @param string $title               The form title.
     *
     * @return Nag_Form_Task
     */
    public function __construct($vars, $title = '')
    {
        global $injector, $nag_shares, $prefs, $registry;

        parent::__construct($vars, $title);

        $user = $registry->getAuth();
        $tasklist_enums = array();
        foreach (Nag::listTasklists(false, Horde_Perms::SHOW, false) as $tl_id => $tl) {
            if (!$tl->hasPermission($user, Horde_Perms::EDIT)) {
                continue;
            }
            $tasklist_enums[$tl_id] = Nag::getLabel($tl);
        }
        $tasklist = $vars->get('tasklist_id');
        if (empty($tasklist)) {
            reset($tasklist_enums);
            $tasklist = key($tasklist_enums);
        }

        $priorities = array(
            1 => '1 ' . _("(highest)"),
            2 => 2,
            3 => 3,
            4 => 4,
            5 => '5 ' . _("(lowest)")
        );
        $this->addHidden('', 'mobile', 'boolean', false);
        $this->addHidden('', 'task_id', 'text', false);
        $this->addHidden('', 'old_tasklist', 'text', false);
        $this->addHidden('', 'url', 'text', false);
        $this->addHidden('', 'uid', 'text', false);
        $this->addHidden('', 'owner', 'text', false);
        $this->addHidden('', 'list', 'text', false);
        $this->addHidden('', 'tab_name', 'text', false);

        $this->setSection(self::SECTION_GENERAL, _("General"));
        $this->addVariable(_("Name"), 'name', 'text', true);
        if (!$prefs->isLocked('default_tasklist') &&
            count($tasklist_enums) > 1) {
            $v = $this->addVariable(
                _("Task List"), 'tasklist_id', 'enum', true, false, false,
                array($tasklist_enums));
            if (!$vars->get('mobile')) {
                $v->setAction(Horde_Form_Action::factory('reload'));
            }
        }

        if (!$vars->get('mobile')) {
            $tasks = Nag::listTasks(array(
                'tasklists' => array($tasklist),
                'complete' => Nag::VIEW_FUTURE_INCOMPLETE,
                'include_history' => false)
            );
            $task_enums = array('' => _("No parent task"));
            $tasks->reset();
            while ($task = $tasks->each()) {
                if ($vars->get('task_id') == $task->id) {
                    continue;
                }
                $task_enums[htmlspecialchars($task->id)] = str_repeat('&nbsp;', $task->indent * 4) . htmlspecialchars($task->name);
            }

            $v = $this->addVariable(
                _("Parent task"), 'parent', 'enum', false, false, false, array($task_enums));
            $v->setOption('htmlchars', true);
        }

        $this->addVariable(_("Tags"), 'tags', 'Nag:NagTags', false);

        // Only display the delete button if this is an existing task and the
        // user has HORDE_PERMS::DELETE
        $share = $nag_shares->getShare($tasklist);
        $delete = $share->hasPermission($registry->getAuth(), Horde_Perms::DELETE) && $vars->get('task_id');

        if (!$vars->get('mobile')) {
            $users = $share->listUsers(Horde_Perms::READ);
            $groups = $share->listGroups(Horde_Perms::READ);
            if (count($groups)) {
                $horde_group = $injector->getInstance('Horde_Group');
                foreach ($groups as $group) {
                    $users = array_merge($users,
                                         $horde_group->listUsers($group));
                }
            }
            $users = array_flip($users);
            if (count($users)) {
                foreach (array_keys($users) as $user) {
                    $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create($user);
                    $fullname = $identity->getValue('fullname');
                    $users[$user] = strlen($fullname) ? $fullname : $user;
                }
            }
            $this->addVariable(_("Assignee"), 'assignee', 'enum', false, false,
                               null, array($users, _("None")));
        }

        $this->addVariable(_("Private?"), 'private', 'boolean', false);
        $this->addVariable(_("Due By"), 'due', 'Nag:NagDue', false);
        if (!$vars->get('mobile')) {
            $this->addVariable(_("Delay Start Until"), 'start', 'Nag:NagStart', false);
        }
        $this->addVariable(_("Alarm"), 'alarm', 'Nag:NagAlarm', false);

        if (!$vars->get('mobile')) {
            $v = $this->addVariable(_("Notification"), 'methods', 'Nag:NagMethod', false);
            $v->setAction(Horde_Form_Action::factory('reload'));

            $v = $this->addVariable(_("Priority"), 'priority', 'enum', false, false, false, array($priorities));
            $v->setDefault(3);
            $this->addVariable(_("Estimated Time"), 'estimate', 'number', false);
            $this->addVariable(_("Completed?"), 'completed', 'boolean', false);

            $this->setSection(self::SECTION_RECUR, _("Recurrence"));
            $this->addVariable(_("Recurrence"), 'recurrence', 'Nag:NagRecurrence', false);
        }

        $this->setSection(self::SECTION_DESC, _("Description"));
        try {
            $description = Horde::callHook('description_help', array(), 'nag');
        } catch (Horde_Exception_HookNotSet $e) {
            $description = '';
        }
        $this->addVariable(_("Description"), 'desc', 'longtext', false, false, $description);

        $buttons = array(array('value' => _("Save")));
        if ($delete) {
            $buttons[] = array('value' => _("Delete"), 'name' => 'deletebutton', 'class' => 'horde-delete');
        }
        if (!$vars->get('task_id')) {
            $buttons[] = array('value' => _("Save and New"), 'name' => 'savenewbutton', 'class' => 'horde-create');
        }
        if (Horde_Util::getFormData('have_search')) {
            $buttons[] = array('value' => _("Return to Search Results"), 'name' => 'search_return', 'class' => 'horde-button');
        }
        $this->setButtons($buttons);
    }

    public function renderActive()
    {
        global $conf;

        return parent::renderActive(
            $this->getRenderer(array('varrenderer_driver' => array('nag', 'nag'))),
            $this->_vars,
            Horde::url(
                $conf['urls']['pretty'] == 'rewrite'
                    ? 't/save'
                    : 'task/save.php'
            ),
            'post'
        );
    }

}