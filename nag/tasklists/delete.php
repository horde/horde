<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
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
    $notification->push($tasklist, 'horde.error');
    Horde::url('tasklists/', true)->redirect();
}
if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
    (!is_null($tasklist->get('owner')) || !$GLOBALS['registry']->isAdmin())) {
    $notification->push(_("You are not allowed to delete this task list."), 'horde.error');
    Horde::url('tasklists/', true)->redirect();
}

$form = new Nag_DeleteTaskListForm($vars, $tasklist);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $result = $form->execute();
        $notification->push(sprintf(_("The task list \"%s\" has been deleted."), $tasklist->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }

    Horde::url('tasklists/', true)->redirect();
}

$title = $form->getTitle();
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Nag::menu();
Nag::status();
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
