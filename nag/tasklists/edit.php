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
try {
    $tasklist = $nag_shares->getShare($vars->get('t'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e);
    Horde::url('list.php', true)->redirect();
}
$owner = $tasklist->get('owner') == $GLOBALS['registry']->getAuth() ||
    (is_null($tasklist->get('owner')) && $GLOBALS['registry']->isAdmin());
if (!$owner &&
    !$tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(_("You are not allowed to see this task list."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}
$form = new Nag_Form_EditTaskList($vars, $tasklist);

// Execute if the form is valid.
if ($owner && $form->validate($vars)) {
    $original_name = $tasklist->get('name');
    try {
        $form->execute();
        if ($tasklist->get('name') != $original_name) {
            $notification->push(sprintf(_("The task list \"%s\" has been renamed to \"%s\"."), $original_name, $tasklist->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The task list \"%s\" has been saved."), $original_name), 'horde.success');
        }
        Horde::url('list.php', true)->redirect();
    } catch (Exception $e) {
        $notification->push($e);
    }
}

$vars->set('name', $tasklist->get('name'));
$vars->set('color', $tasklist->get('color'));
$vars->set('system', is_null($tasklist->get('owner')));
$vars->set('description', $tasklist->get('desc'));

$page_output->header(array(
    'title' => $form->getTitle()
));
Nag::status();
if ($owner) {
    echo $form->renderActive($form->getRenderer(), $vars,
                             Horde::url('tasklists/edit.php'), 'post');
} else {
    echo $form->renderInactive($form->getRenderer(), $vars);
}
$page_output->footer();
