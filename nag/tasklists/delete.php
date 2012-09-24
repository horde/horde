<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('nag');

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::url('list.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$tasklist_id = $vars->get('t');
try {
    $tasklist = $nag_shares->getShare($tasklist_id);
} catch (Horde_Share_Exception $e) {
    $notification->push($e);
    Horde::url('list.php', true)->redirect();
}
if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
    (!is_null($tasklist->get('owner')) || !$GLOBALS['registry']->isAdmin())) {
    $notification->push(_("You are not allowed to delete this task list."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

$form = new Nag_Form_DeleteTaskList($vars, $tasklist);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The task list \"%s\" has been deleted."), $tasklist->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    Horde::url('list.php', true)->redirect();
}

$page_output->header(array(
    'title' => $form->getTitle()
));
Nag::status();
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('tasklists/delete.php'), 'post');
$page_output->footer();
