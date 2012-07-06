<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jon Parise <jon@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

function _delete($task_id, $tasklist_id)
{
    if (!empty($task_id)) {
        try {
            $task = Nag::getTask($tasklist_id, $task_id);
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
        } catch (Nag_Exception $e) {
            $GLOBALS['notification']->push(
                sprintf(_("Error deleting task: %s"),
                        $e->getMessage()), 'horde.error');
        }
    }

    /* Return to the last page or to the task list. */
    if ($url = Horde_Util::getFormData('url')) {
        header('Location: ' . $url);
        exit;
    }
    Horde::url('list.php', true)->redirect();
}

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

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
    $perms = $injector->getInstance('Horde_Core_Perms');
    if ($perms->hasAppPermission('max_tasks') !== true &&
        $perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
        Horde::permissionDeniedError(
            'nag',
            'max_tasks',
            sprintf(_("You are not allowed to create more than %d tasks."), $perms->hasAppPermission('max_tasks'))
        );
        Horde::url('list.php', true)->redirect();
    }

    if (!$vars->exists('tasklist_id')) {
        $vars->set('tasklist_id', Nag::getDefaultTasklist(Horde_Perms::EDIT));
    }
    $form = new Nag_Form_Task($vars, _("New Task"));
    break;

case 'modify_task':
    $task_id = $vars->get('task');
    $tasklist_id = $vars->get('tasklist');
    try {
        $share = $nag_shares->getShare($tasklist_id);
    } catch (Horde_Share_Exception $e) {
        $notification->push(sprintf(_("Access denied editing task: %s"), $e->getMessage()), 'horde.error');
    }
    if (!$share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("Access denied editing task."), 'horde.error');
    } else {
        $task = Nag::getTask($tasklist_id, $task_id);
        if (!isset($task) || !isset($task->id)) {
            $notification->push(_("Task not found."), 'horde.error');
        } elseif ($task->private && $task->owner != $registry->getAuth()) {
            $notification->push(_("Access denied editing task."), 'horde.error');
        } else {
            $vars = new Horde_Variables($task->toHash());
            $vars->set('old_tasklist', $task->tasklist);
            $vars->set('url', Horde_Util::getFormData('url'));
            $form = new Nag_Form_Task($vars, sprintf(_("Edit: %s"), $task->name), $share->hasPermission($registry->getAuth(), Horde_Perms::DELETE));
            break;
        }
    }

    /* Return to the task list. */
    Horde::url('list.php', true)->redirect();

case 'delete_task':
    /* Delete the task if we're provided with a valid task ID. */
    _delete(Horde_Util::getFormData('task'), Horde_Util::getFormData('tasklist'));
    break;

case 'task_form':
    break;

default:
    Horde::url('list.php', true)->redirect();
}

$datejs = str_replace('_', '-', $language) . '.js';
if (!file_exists($registry->get('jsfs', 'horde') . '/date/' . $datejs)) {
    $datejs = 'en-US.js';
}

$page_output->addScriptFile('date/' . $datejs, 'horde');
$page_output->addScriptFile('date/date.js', 'horde');
$page_output->addScriptFile('task.js');
$page_output->addScriptPackage('Keynavlist');

$page_output->header(array(
    'title' => $form->getTitle()
));
require NAG_TEMPLATES . '/javascript_defs.php';
echo Nag::menu();
Nag::status();
$form->renderActive();
$page_output->footer();
