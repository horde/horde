<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jon Parise <jon@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

function _delete($task_id, $tasklist_id)
{
    if (!empty($task_id)) {
        $task = Nag::getTask($tasklist_id, $task_id);
        if ($task instanceof PEAR_Error) {
            $GLOBALS['notification']->push(
                sprintf(_("Error deleting task: %s"),
                        $task->getMessage()), 'horde.error');
        } else {
            try {
                $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
            } catch (Horde_Share_Exception $e) {
                throw new Nag_Exception($e);
            }
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                $GLOBALS['notification']->push(_("Access denied deleting task."), 'horde.error');
            } else {
                $storage = Nag_Driver::singleton($tasklist_id);
                try {
                    $storage->delete($task_id);
                } catch (Horde_Share_Exception $e) {
                    $GLOBALS['notification']->push(
                        sprintf(_("There was a problem deleting %s: %s"),
                                $task->name, $e->getMessage()),
                        'horde.error');
                }
                $GLOBALS['notification']->push(sprintf(_("Deleted %s."), $task->name),
                                               'horde.success');
            }
        }
    }

    /* Return to the last page or to the task list. */
    if ($url = Horde_Util::getFormData('url')) {
        header('Location: ' . $url);
        exit;
    }
    Horde::url('list.php', true)->redirect();
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

require_once NAG_BASE . '/lib/Forms/task.php';
$vars = Horde_Variables::getDefaultVariables();

/* Redirect to the task list if no action has been requested. */
$actionID = $vars->get('actionID');
if (is_null($actionID)) {
    Horde::url('list.php', true)->redirect();
}

/* Run through the action handlers. */
switch ($actionID) {
case 'add_task':
    /* Check permissions. */
    $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
    if ($perms->hasAppPermission('max_tasks') !== true &&
        $perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
        try {
            $message = Horde::callHook('perms_denied', array('nag:max_tasks'));
        } catch (Horde_Exception_HookNotSet $e) {
            $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d tasks."), $perms->hasAppPermission('max_tasks')), ENT_COMPAT, 'UTF-8');
        }
        $notification->push($message, 'horde.error', array('content.raw'));
        Horde::url('list.php', true)->redirect();
    }

    $vars->set('actionID', 'save_task');
    if (!$vars->exists('tasklist_id')) {
        $vars->set('tasklist_id', Nag::getDefaultTasklist(Horde_Perms::EDIT));
    }
    $form = new Nag_TaskForm($vars, _("New Task"));
    break;

case 'modify_task':
    $task_id = $vars->get('task');
    $tasklist_id = $vars->get('tasklist');
    try {
        $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
    } catch (Horde_Share_Exception $e) {
        $notification->push(sprintf(_("Access denied editing task: %s"), $e->getMessage()), 'horde.error');
    }
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("Access denied editing task."), 'horde.error');
    } else {
        $task = Nag::getTask($tasklist_id, $task_id);
        if (!isset($task) || !isset($task->id)) {
            $notification->push(_("Task not found."), 'horde.error');
        } elseif ($task->private && $task->owner != $GLOBALS['registry']->getAuth()) {
            $notification->push(_("Access denied editing task."), 'horde.error');
        } else {
            $vars = new Horde_Variables($task->toHash());
            $vars->set('actionID', 'save_task');
            $vars->set('old_tasklist', $task->tasklist);
            $vars->set('url', Horde_Util::getFormData('url'));
            $form = new Nag_TaskForm($vars, sprintf(_("Edit: %s"), $task->name), $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE));
            break;
        }
    }

    /* Return to the task list. */
    Horde::url('list.php', true)->redirect();

case 'save_task':
    if ($vars->get('submitbutton') == _("Delete this task")) {
        _delete($vars->get('task_id'), $vars->get('old_tasklist'));
    }

    $form = new Nag_TaskForm($vars, $vars->get('task_id') ? sprintf(_("Edit: %s"), $vars->get('name')) : _("New Task"));
    if (!$form->validate($vars)) {
        break;
    }

    $form->getInfo($vars, $info);
    if ($prefs->isLocked('default_tasklist') ||
        count(Nag::listTasklists(false, Horde_Perms::EDIT)) <= 1) {
        $info['tasklist_id'] = $info['old_tasklist'] = Nag::getDefaultTasklist(Horde_Perms::EDIT);
    }
    try {
        $share = $GLOBALS['nag_shares']->getShare($info['tasklist_id']);
    } catch (Horde_Share_Exception $e) {
        $notification->push(sprintf(_("Access denied saving task: %s"), $e->getMessage()), 'horde.error');
        Horde::url('list.php', true)->redirect();
    }
    if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(sprintf(_("Access denied saving task to %s."), $share->get('name')), 'horde.error');
        Horde::url('list.php', true)->redirect();
    }

    /* Add new category. */
    if ($info['category']['new']) {
        $cManager = new Horde_Prefs_CategoryManager();
        $cManager->add($info['category']['value']);
    }

    /* If a task id is set, we're modifying an existing task.
     * Otherwise, we're adding a new task with the provided
     * attributes. */
    if (!empty($info['task_id']) && !empty($info['old_tasklist'])) {
        $storage = Nag_Driver::singleton($info['old_tasklist']);
        $result = $storage->modify($info['task_id'], $info['name'],
                                   $info['desc'], $info['start'],
                                   $info['due'], $info['priority'],
                                   (float)$info['estimate'],
                                   (int)$info['completed'],
                                   $info['category']['value'],
                                   $info['alarm'], $info['methods'],
                                   $info['parent'], (int)$info['private'],
                                   $GLOBALS['registry']->getAuth(), $info['assignee'], null,
                                   $info['tasklist_id']);
    } else {
        /* Check permissions. */
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if ($perms->hasAppPermission('max_tasks') !== true &&
            $perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
            Horde::url('list.php', true)->redirect();
        }

        /* Creating a new task. */
        $storage = Nag_Driver::singleton($info['tasklist_id']);
        $result = $storage->add($info['name'], $info['desc'], $info['start'],
                                $info['due'], $info['priority'],
                                (float)$info['estimate'],
                                (int)$info['completed'],
                                $info['category']['value'],
                                $info['alarm'], $info['methods'], null,
                                $info['parent'], (int)$info['private'],
                                $GLOBALS['registry']->getAuth(), $info['assignee']);
    }

    /* Check our results. */
    if ($result instanceof PEAR_Error) {
        $notification->push(sprintf(_("There was a problem saving the task: %s."), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("Saved %s."), $info['name']), 'horde.success');
        /* Return to the last page or to the task list. */
        if ($url = Horde_Util::getFormData('url')) {
            header('Location: ' . $url);
            exit;
        }
        Horde::url('list.php', true)->redirect();
    }

    break;

case 'delete_task':
    /* Delete the task if we're provided with a valid task ID. */
    _delete(Horde_Util::getFormData('task'), Horde_Util::getFormData('tasklist'));

case 'complete_task':
    /* Toggle the task's completion status if we're provided with a
     * valid task ID. */
    $task_id = Horde_Util::getFormData('task');
    $tasklist_id = Horde_Util::getFormData('tasklist');
    if (isset($task_id)) {
        $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
        $task = Nag::getTask($tasklist_id, $task_id);
        if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $notification->push(sprintf(_("Access denied completing task %s."), $task->name), 'horde.error');
        } else {
            $task->completed = !$task->completed;
            if ($task->completed) {
                $task->completed_date = time();
            } else {
                $task->completed_date = null;
            }
            $result = $task->save();
            if ($result instanceof PEAR_Error) {
                $notification->push(sprintf(_("There was a problem completing %s: %s"),
                                            $task->name, $result->getMessage()), 'horde.error');
            } else {
                if ($task->completed) {
                    $notification->push(sprintf(_("Completed %s."), $task->name), 'horde.success');
                } else {
                    $notification->push(sprintf(_("%s is now incomplete."), $task->name), 'horde.success');
                }
            }
        }
    }

    $url = $vars->get('url');
    if (!empty($url)) {
        header('Location: ' . $url);
        exit;
    }
    Horde::url('list.php', true)->redirect();

default:
    Horde::url('list.php', true)->redirect();
}

$title = $form->getTitle();
require NAG_TEMPLATES . '/common-header.inc';
echo Horde::menu();
Nag::status();
$form->renderActive();
require $registry->get('templates', 'horde') . '/common-footer.inc';
