<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('nag');

require_once NAG_BASE . '/lib/Forms/EditTaskList.php';

// Exit if this isn't an authenticated user.
if (!Horde_Auth::getAuth()) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$tasklist = $nag_shares->getShare($vars->get('t'));
if (is_a($tasklist, 'PEAR_Error')) {
    $notification->push($tasklist, 'horde.error');
    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
} elseif ($tasklist->get('owner') != Horde_Auth::getAuth() &&
          (!is_null($tasklist->get('owner')) || !Horde_Auth::isAdmin())) {
    $notification->push(_("You are not allowed to change this task list."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}
$form = new Nag_EditTaskListForm($vars, $tasklist);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $tasklist->get('name');
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        if ($tasklist->get('name') != $original_name) {
            $notification->push(sprintf(_("The task list \"%s\" has been renamed to \"%s\"."), $original_name, $tasklist->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The task list \"%s\" has been saved."), $original_name), 'horde.success');
        }
    }

    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}

$vars->set('name', $tasklist->get('name'));
$vars->set('description', $tasklist->get('desc'));
$vars->set('system', is_null($tasklist->get('owner')));
$title = $form->getTitle();
require NAG_TEMPLATES . '/common-header.inc';
require NAG_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
