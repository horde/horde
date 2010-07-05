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
if (!$GLOBALS['registry']->getAuth()) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $tasklist = $nag_shares->getShare($vars->get('t'));
} catch (Horde_Share_Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}
if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
    (!is_null($tasklist->get('owner')) || !$GLOBALS['registry']->isAdmin())) {
    $notification->push(_("You are not allowed to change this task list."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('tasklists/', true));
    exit;
}
$form = new Nag_EditTaskListForm($vars, $tasklist);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $tasklist->get('name');
    try {
        $result = $form->execute();
        if ($tasklist->get('name') != $original_name) {
            $notification->push(sprintf(_("The task list \"%s\" has been renamed to \"%s\"."), $original_name, $tasklist->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The task list \"%s\" has been saved."), $original_name), 'horde.success');
        }
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
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
