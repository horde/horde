<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

/* We can either have a UID or a taskId and a tasklist. Check for
 * UID first. */
if ($uid = Horde_Util::getFormData('uid')) {
    $storage = Nag_Driver::singleton();
    $task = $storage->getByUID($uid);
    if ($task instanceof PEAR_Error) {
        Horde::url('list.php', true)->redirect();
    }

    $task_id = $task->id;
    $tasklist_id = $task->tasklist;
} else {
    /* If we aren't provided with a task and tasklist, redirect to
     * list.php. */
    $task_id = Horde_Util::getFormData('task');
    $tasklist_id = Horde_Util::getFormData('tasklist');
    if (!isset($task_id) || !$tasklist_id) {
        Horde::url('list.php', true)->redirect();
    }

    /* Get the current task. */
    $task = Nag::getTask($tasklist_id, $task_id);
}

/* If the requested task doesn't exist, display an error message. */
if (!isset($task) || !isset($task->id)) {
    $notification->push(_("Task not found."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* Load child tasks */
$task->loadChildren();

/* Check permissions on $tasklist_id. */
$share = $GLOBALS['nag_shares']->getShare($tasklist_id);
if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(_("You do not have permission to view this tasklist."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* Get the task's history. */
$created = null;
$modified = null;
$completed = null;
$userId = $GLOBALS['registry']->getAuth();
$createdby = '';
$modifiedby = '';
if (!empty($task->uid)) {
    try {
        $log = $GLOBALS['injector']->getInstance('Horde_History')->getHistory('nag:' . $tasklist_id . ':' . $task->uid);
        foreach ($log as $entry) {
            switch ($entry['action']) {
            case 'add':
                $created = $entry['ts'];
                if ($userId != $entry['who']) {
                    $createdby = sprintf(_("by %s"), Nag::getUserName($entry['who']));
                } else {
                    $createdby = _("by me");
                }
                break;

            case 'modify':
                $modified = $entry['ts'];
                if ($userId != $entry['who']) {
                    $modifiedby = sprintf(_("by %s"), Nag::getUserName($entry['who']));
                } else {
                    $modifiedby = _("by me");
                }
                break;

            case 'complete':
                if (!empty($entry['ts'])) {
                    $completed = $entry['ts'];
                }
            }
        }
    } catch (Exception $e) {}
}

$title = $task->name;
$links = array();
Horde::addScriptFile('stripe.js', 'horde');

$taskurl = Horde_Util::addParameter('task.php',
                              array('task' => $task_id,
                                    'tasklist' => $tasklist_id));
try {
    $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
} catch (Horde_Share_Exception $e) {
    Horde::logMessage($e->getMessage(), 'ERR');
    throw new Nag_Exception($e);
}
if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    if (!$task->completed) {
        $links[] = Horde::widget(Horde::url(Horde_Util::addParameter($taskurl, 'actionID', 'complete_task')), _("Complete"), 'smallheader', '', '', _("_Complete"));
    }
    if (!$task->private || $task->owner == $GLOBALS['registry']->getAuth()) {
        $links[] = Horde::widget(Horde::url(Horde_Util::addParameter($taskurl, 'actionID', 'modify_task')), _("Edit"), 'smallheader', '', '', _("_Edit"));
    }
}
if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
    $links[] = Horde::widget(Horde::url(Horde_Util::addParameter($taskurl, 'actionID', 'delete_task')), _("Delete"), 'smallheader', '', $prefs->getValue('delete_opt') ? 'return window.confirm(\'' . addslashes(_("Really delete this task?")) . '\');' : '', _("_Delete"));
}

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Nag::menu();
Nag::status();

/* Set up alarm units and value. */
$task_alarm = $task->alarm;
if (!$task->due) {
    $task_alarm = 0;
}
$alarm_text = Nag::formatAlarm($task_alarm);
require NAG_TEMPLATES . '/view/task.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
