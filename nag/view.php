<?php
/**
 * Copyright 2001-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

/* We can either have a UID or a taskId and a tasklist. Check for
 * UID first. */
if ($uid = Horde_Util::getFormData('uid')) {
    $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create('');
    try {
        $task = $storage->getByUID($uid);
    } catch (Nag_Exception $e) {
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
$task->loadChildren(false);

/* Check permissions on $tasklist_id. */
$share = $GLOBALS['nag_shares']->getShare($tasklist_id);
if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(_("You do not have permission to view this tasklist."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

$links = array();
$page_output->addScriptFile('stripe.js', 'horde');

$taskurl = Horde::url('task.php')
    ->add(array('task' => $task_id,
                'tasklist' => $tasklist_id));
try {
    $share = $GLOBALS['nag_shares']->getShare($tasklist_id);
} catch (Horde_Share_Exception $e) {
    Horde::logMessage($e->getMessage(), 'ERR');
    throw new Nag_Exception($e);
}

/* Set up alarm units and value. */
$task_alarm = $task->alarm;
if (!$task->due) {
    $task_alarm = 0;
}
$alarm_text = Nag::formatAlarm($task_alarm);

$page_output->header(array(
    'title' => $task->name
));
Nag::status();
require NAG_TEMPLATES . '/view/task.inc';
$page_output->footer();
