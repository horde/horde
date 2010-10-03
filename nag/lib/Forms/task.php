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
class Nag_TaskForm extends Horde_Form {

    var $delete;

    function Nag_TaskForm(&$vars, $title = '', $delete = false)
    {
        parent::Horde_Form($vars, $title);
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
            $task_enums[htmlspecialchars($task->id)] = str_repeat('&nbsp;', $task->indent * 4) . htmlentities($task->name, ENT_COMPAT, 'UTF-8');
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
                $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($user);
                $fullname = $identity->getValue('fullname');
                $users[$user] = strlen($fullname) ? $fullname : $user;
            }
        }
        $priorities = array(1 => '1 ' . _("(highest)"), 2 => 2, 3 => 3,
                            4 => 4, 5 => '5 ' . _("(lowest)"));

        $this->addHidden('', 'actionID', 'text', true);
        $this->addHidden('', 'task_id', 'text', false);
        $this->addHidden('', 'old_tasklist', 'text', false);
        $this->addHidden('', 'url', 'text', false);

        $this->addVariable(_("Name"), 'name', 'text', true);
        if (!$GLOBALS['prefs']->isLocked('default_tasklist') &&
            count($tasklist_enums) > 1) {
            $v = &$this->addVariable(_("Task List"), 'tasklist_id', 'enum', true, false, false, array($tasklist_enums));
            $v->setAction(Horde_Form_Action::factory('reload'));
        }

        $v = &$this->addVariable(_("Parent task"), 'parent', 'enum', false, false, false, array($task_enums));
        $v->setOption('htmlchars', true);

        if (class_exists('Horde_Form_Type_category')) {
            $this->addVariable(_("Category"), 'category', 'category', false);
        } else {
            $values = Horde_Array::valuesToKeys(Horde_Prefs_CategoryManager::get());
            $this->addVariable(_("Category"), 'category', 'enum', false, false, false, array($values, _("Unfiled")));
        }

        $this->addVariable(_("Assignee"), 'assignee', 'enum', false, false,
                           null, array($users, _("None")));
        $this->addVariable(_("Private?"), 'private', 'boolean', false);
        $this->addVariable(_("Due By"), 'due', 'nag_due', false);
        $this->addVariable(_("Delay Start Until"), 'start', 'nag_start', false);
        $this->addVariable(_("Alarm"), 'alarm', 'nag_alarm', false);
        $v = &$this->addVariable(_("Notification"), 'methods', 'nag_method', false);
        $v->setAction(Horde_Form_Action::factory('reload'));

        $v = &$this->addVariable(_("Priority"), 'priority', 'enum', false, false, false, array($priorities));
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

    function renderActive()
    {
        return parent::renderActive(new Nag_TaskForm_Renderer(array('varrenderer_driver' => array('nag', 'nag')), $this->delete), $this->_vars, 'task.php', 'post');
    }

}

class Nag_TaskForm_Renderer extends Horde_Form_Renderer {

    var $delete;

    function Nag_TaskForm_Renderer($params = array(), $delete = false)
    {
        parent::Horde_Form_Renderer($params);
        $this->delete = $delete;
    }

    function _renderSubmit($submit, $reset)
    {
?><div class="control" style="padding:1em;">
    <input class="button leftFloat" name="submitbutton" type="submit" value="<?php echo _("Save") ?>" />
<?php if ($this->delete): ?>
    <input class="button rightFloat" name="submitbutton" type="submit" value="<?php echo _("Delete this task") ?>" />
<?php endif; ?>
    <div class="clear"></div>
</div>
<?php
    }

}
/**
 * The Horde_Form_Type_nag_method class provides a form field for editing
 * notification methods for a task alarm.
 *
 * @author  Alfonso Marin <almarin@um.es>
 * @package Nag
 */
class Horde_Form_Type_nag_method extends Horde_Form_Type {

    function getInfo(&$vars, &$var, &$info)
    {
        $info = $var->getValue($vars);
        if (empty($info['on'])) {
            $info = array();
            return;
        }

        $types = $vars->get('task_alarms');
        $info = array();
        if (!empty($types)) {
            foreach ($types as $type) {
                $info[$type] = array();
                switch ($type){
                    case 'notify':
                        $info[$type]['sound'] = $vars->get('task_alarms_sound');
                        break;
                    case 'mail':
                        $info[$type]['email'] = $vars->get('task_alarms_email');
                        break;
                    case 'popup':
                        break;
                }
            }
        }
    }

    function isValid(&$var, &$vars, $value, &$message)
    {
        $alarm = $vars->get('alarm');
        if ($value['on'] && !$alarm['on']){
            $message = _("An alarm must be set to specify a notification method");
            return false;
        }
        return true;
    }

}

/**
 * The Horde_Form_Type_nag_alarm class provides a form field for editing task
 * alarms.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Horde_Form_Type_nag_alarm extends Horde_Form_Type {

    function getInfo(&$vars, &$var, &$info)
    {
        $info = $var->getValue($vars);
        if (!$info['on']) {
            $info = 0;
        } else {
            $value = $info['value'];
            $unit = $info['unit'];
            if ($value == 0) {
                $value = $unit = 1;
            }
            $info = $value * $unit;
        }
    }

    function isValid(&$var, &$vars, $value, &$message)
    {
        if ($value['on']) {
            if ($vars->get('due_type') == 'none') {
                $message = _("A due date must be set to enable alarms.");
                return false;
            }
        }

        return true;
    }

}

/**
 * The Horde_Form_Type_nag_due class provides a form field for editing
 * task due dates.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Horde_Form_Type_nag_due extends Horde_Form_Type {

    function getInfo(&$vars, &$var, &$info)
    {
        $due_type = $vars->get('due_type');
        $due = $vars->get('due');
        if (is_array($due)) {
            $due_day = !empty($due['day']) ? $due['day'] : null;
            $due_month = !empty($due['month']) ? $due['month'] : null;
            $due_year = !empty($due['year']) ? $due['year'] : null;
            $due_hour = Horde_Util::getFormData('due_hour');
            $due_minute = Horde_Util::getFormData('due_minute');
            if (!$GLOBALS['prefs']->getValue('twentyFour')) {
                $due_am_pm = Horde_Util::getFormData('due_am_pm');
                if ($due_am_pm == 'pm') {
                    if ($due_hour < 12) {
                        $due_hour = $due_hour + 12;
                    }
                } else {
                    // Adjust 12:xx AM times.
                    if ($due_hour == 12) {
                        $due_hour = 0;
                    }
                }
            }

            $due = (int)strtotime("$due_month/$due_day/$due_year $due_hour:$due_minute");
        }

        $info = strcasecmp($due_type, 'none') ? $due : 0;
    }

    function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

}

/**
 * The Horde_Form_Type_nag_start class provides a form field for editing
 * task delayed start dates.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Horde_Form_Type_nag_start extends Horde_Form_Type {

    function getInfo(&$vars, &$var, &$info)
    {
        $start_type = $vars->get('start_date');
        $start = $vars->get('start');
        if (is_array($start)) {
            $start_day = !empty($start['day']) ? $start['day'] : null;
            $start_month = !empty($start['month']) ? $start['month'] : null;
            $start_year = !empty($start['year']) ? $start['year'] : null;
            $start = (int)strtotime("$start_month/$start_day/$start_year");
        }

        $info = strcasecmp($start_type, 'none') ? $start : 0;
    }

    function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

}
