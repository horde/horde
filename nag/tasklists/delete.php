<?php
/**
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/base.php';
require_once NAG_BASE . '/lib/Forms/DeleteTaskList.php';

// Exit if this isn't an authenticated user.
if (!Horde_Auth::getAuth()) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$tasklist_id = $vars->get('t');
if ($tasklist_id == Horde_Auth::getAuth()) {
    $notification->push(_("This task list cannot be deleted."), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}
$tasklist = $nag_shares->getShare($tasklist_id);
if (is_a($tasklist, 'PEAR_Error')) {
    $notification->push($tasklist, 'horde.error');
    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
} elseif ($tasklist->get('owner') != Horde_Auth::getAuth() &&
          (!is_null($tasklist->get('owner')) || !Horde_Auth::isAdmin())) {
    $notification->push(_("You are not allowed to delete this task list."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}

$form = new Nag_DeleteTaskListForm($vars, $tasklist);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The task list \"%s\" has been deleted."), $tasklist->get('name')), 'horde.success');
    }

    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}

$title = $form->getTitle();
require NAG_TEMPLATES . '/common-header.inc';
require NAG_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
